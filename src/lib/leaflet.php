<?php

/**
 * Leaflet.pub adapter
 *
 * Fetches pub.leaflet.document records from the author's AT Protocol PDS,
 * normalises them into the post shape that templates expect, and caches every
 * remote call so the blog stays fast and resilient when the API is slow.
 *
 * Data flow per request:
 *   handle  →  com.atproto.identity.resolveHandle  →  DID         (24 h cache)
 *   DID     →  plc.directory                       →  PDS URL     (24 h cache)
 *   PDS URL →  com.atproto.repo.listRecords         →  raw records (10 min cache)
 *   raw records → normalise → post[]
 *
 * All HTTP calls are guarded by a stale-while-revalidate pattern: if the
 * upstream is unreachable and a cached (but expired) entry exists, the stale
 * entry is returned rather than failing with a fatal error.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/cache.php';

define('LEAFLET_COLLECTION',   'pub.leaflet.document');
define('LEAFLET_PLC_DIR',      'https://plc.directory');
define('BSKY_API_BASE',        $_ENV['BSKY_API_HOST'] ?? 'https://public.api.bsky.app');
define('LEAFLET_FEED_TTL',     (int)($_ENV['LEAFLET_CACHE_TTL'] ?? 600));
define('LEAFLET_IDENTITY_TTL', 86400);

/**
 * Return the absolute path to the leaflet cache directory, creating it first
 * if it does not already exist.
 */
function leaflet_cache_dir(): string
{
    $dir = __DIR__ . '/../../cache/leaflet';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

/**
 * Read a cache entry.
 *
 * Returns the stored payload on a fresh hit.  When the entry is expired it is
 * returned with an extra 'stale' => true key so callers can use it as a
 * graceful fallback.  Returns null only when the file does not exist at all.
 */
function leaflet_cache_read(string $key): ?array
{
    $apcuKey = 'leaflet:' . $key;
    $hit = apcu_helper_fetch($apcuKey);
    if (is_array($hit)) {
        return $hit;
    }

    $file = leaflet_cache_dir() . '/' . preg_replace('/[^a-z0-9_\-]/i', '_', $key) . '.json';
    if (!is_file($file)) {
        return null;
    }
    $raw = @file_get_contents($file);
    if ($raw === false) {
        return null;
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return null;
    }
    if (isset($data['expires_at']) && time() > (int)$data['expires_at']) {
        return $data + ['stale' => true];
    }
    if (isset($data['expires_at'])) {
        $ttl = max(0, (int)$data['expires_at'] - time());
        apcu_helper_store($apcuKey, $data, $ttl);
    }

    return $data;
}

/**
 * Write a cache entry atomically (tmp-file + rename) with the given TTL.
 */
function leaflet_cache_write(string $key, array $payload, int $ttl): void
{
    $data    = array_merge($payload, ['expires_at' => time() + $ttl]);
    $apcuKey = 'leaflet:' . $key;

    apcu_helper_store($apcuKey, $data, $ttl);

    $dir  = leaflet_cache_dir();
    $file = $dir . '/' . preg_replace('/[^a-z0-9_\-]/i', '_', $key) . '.json';
    $tmp  = $file . '.tmp.' . getmypid() . '.' . uniqid('', true);
    @file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    @rename($tmp, $file);
}

/**
 * Perform a GET request and return the decoded JSON body, or null on failure.
 * Falls back silently when the curl extension is unavailable.
 */
function leaflet_http_get(string $url, int $timeout = 10): ?array
{
    if (!function_exists('curl_init')) {
        return null;
    }
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'User-Agent: mau.coffee/1.0 (+https://mau.coffee)',
        ],
    ]);
    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($response === false || $code < 200 || $code >= 300) {
        return null;
    }
    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : null;
}

/**
 * Resolve an AT Protocol handle to its DID (cached 24 h).
 * Returns null only when both the live lookup and any stale cache entry fail.
 */
function leaflet_resolve_did(string $handle): ?string
{
    $cacheKey = 'identity_did_' . preg_replace('/[^a-z0-9.\-]/i', '_', $handle);
    $cached   = leaflet_cache_read($cacheKey);

    if ($cached !== null && empty($cached['stale'])) {
        return $cached['did'] ?? null;
    }

    $data = leaflet_http_get(
        BSKY_API_BASE . '/xrpc/com.atproto.identity.resolveHandle?handle=' . rawurlencode($handle)
    );
    $did = $data['did'] ?? null;

    if ($did !== null) {
        leaflet_cache_write($cacheKey, ['did' => $did], LEAFLET_IDENTITY_TTL);
        return $did;
    }

    return $cached['did'] ?? null;
}

/**
 * Resolve a DID to its PDS service endpoint URL (cached 24 h).
 */
function leaflet_resolve_pds(string $did): ?string
{
    $cacheKey = 'identity_pds_' . preg_replace('/[^a-z0-9.\-:]/i', '_', $did);
    $cached   = leaflet_cache_read($cacheKey);

    if ($cached !== null && empty($cached['stale'])) {
        return $cached['pds'] ?? null;
    }

    $doc = leaflet_http_get(LEAFLET_PLC_DIR . '/' . rawurlencode($did));
    $pds = null;
    foreach ($doc['service'] ?? [] as $svc) {
        if (($svc['type'] ?? '') === 'AtprotoPersonalDataServer') {
            $pds = rtrim($svc['serviceEndpoint'] ?? '', '/');
            break;
        }
    }

    if ($pds !== null) {
        leaflet_cache_write($cacheKey, ['pds' => $pds], LEAFLET_IDENTITY_TTL);
        return $pds;
    }

    return $cached['pds'] ?? null;
}

/**
 * Fetch all pub.leaflet.document records from the configured author's PDS.
 * Results are cached for LEAFLET_FEED_TTL seconds; stale entries are used if
 * the upstream call fails, so the site stays up during brief API outages.
 *
 * @return array<int, array>  Raw AT Protocol record objects.
 */
function leaflet_fetch_documents(): array
{
    $cacheKey = 'feed_documents_' . preg_replace('/[^a-z0-9.\-]/i', '_', BSKY_HANDLE);
    $cached   = leaflet_cache_read($cacheKey);

    if ($cached !== null && empty($cached['stale'])) {
        return $cached['records'] ?? [];
    }

    $did = leaflet_resolve_did(BSKY_HANDLE);
    if ($did === null) {
        return $cached['records'] ?? [];
    }

    $pds = leaflet_resolve_pds($did);
    if ($pds === null) {
        return $cached['records'] ?? [];
    }

    $url  = $pds . '/xrpc/com.atproto.repo.listRecords?' . http_build_query([
        'repo'       => $did,
        'collection' => LEAFLET_COLLECTION,
        'limit'      => 100,
    ]);
    $data = leaflet_http_get($url);

    if ($data === null || !array_key_exists('records', $data)) {
        return $cached['records'] ?? [];
    }

    $records = $data['records'];
    leaflet_cache_write($cacheKey, ['records' => $records], LEAFLET_FEED_TTL);
    return $records;
}

/**
 * Apply ATProto / Leaflet richtext facets to a plaintext string, converting
 * link facets to Markdown link syntax.  Facets are applied right-to-left
 * (descending byte offset) so earlier replacements do not shift later ones.
 *
 * @param string $plaintext  Raw UTF-8 text.
 * @param array  $facets     Facet objects from the record.
 */
function leaflet_apply_facets(string $plaintext, array $facets): string
{
    if (empty($facets)) {
        return $plaintext;
    }

    usort(
        $facets,
        static fn($a, $b) =>
        (int)($b['index']['byteStart'] ?? 0) - (int)($a['index']['byteStart'] ?? 0)
    );

    foreach ($facets as $facet) {
        $start = $facet['index']['byteStart'] ?? null;
        $end   = $facet['index']['byteEnd']   ?? null;
        if ($start === null || $end === null || $start >= $end) {
            continue;
        }

        foreach ($facet['features'] ?? [] as $feature) {
            $ftype = $feature['$type'] ?? '';
            if (
                $ftype === 'pub.leaflet.richtext.facet#link'
                || $ftype === 'app.bsky.richtext.facet#link'
            ) {
                $uri = filter_var($feature['uri'] ?? '', FILTER_VALIDATE_URL) ?: '';
                if ($uri === '') {
                    continue;
                }
                $snippet   = substr($plaintext, $start, $end - $start);
                $plaintext = substr($plaintext, 0, $start)
                    . '[' . $snippet . '](' . $uri . ')'
                    . substr($plaintext, $end);
                break;
            }
        }
    }

    return $plaintext;
}

/**
 * Convert a pub.leaflet.document pages array to a single Markdown string
 * compatible with render_basic_markdown().
 *
 * Text blocks become paragraphs; image blocks become Markdown image syntax
 * pointing at the PDS blob endpoint.
 *
 * @param array  $pages  Value of the 'pages' key on a pub.leaflet.document record.
 * @param string $did    Author DID – used to construct blob URLs.
 * @param string $pds    Author PDS base URL.
 */
function leaflet_blocks_to_markdown(array $pages, string $did, string $pds): string
{
    $parts = [];

    foreach ($pages as $page) {
        foreach ($page['blocks'] ?? [] as $wrapper) {
            $block = $wrapper['block'] ?? $wrapper;
            $type  = $block['$type'] ?? '';

            if ($type === 'pub.leaflet.blocks.text') {
                $text = leaflet_apply_facets(
                    $block['plaintext'] ?? '',
                    $block['facets']    ?? []
                );
                if ($text !== '') {
                    $parts[] = $text;
                }
            } elseif ($type === 'pub.leaflet.blocks.image') {
                $cid = $block['image']['ref']['$link'] ?? '';
                if ($cid !== '') {
                    $imgUrl = $pds . '/xrpc/com.atproto.sync.getBlob?'
                        . http_build_query(['did' => $did, 'cid' => $cid]);
                    $alt    = preg_replace('/[\[\]()!]/', '', $block['alt'] ?? '');
                    $parts[] = '![' . $alt . '](' . $imgUrl . ')';
                }
            }
        }
    }

    return implode("\n\n", $parts);
}

/**
 * Normalise a raw pub.leaflet.document record to the post shape that templates
 * and sitemap.php expect.
 *
 * @return array{id:int,slug:string,title:string,summary:string,body:string,published:int,created_at:string,updated_at:string}
 */
function leaflet_normalize_record(array $record, string $did, string $pds): array
{
    $uri   = (string)($record['uri']   ?? '');
    $value = (array) ($record['value'] ?? []);

    $rkey = (string)basename(str_replace('at://', '', $uri));

    $title     = (string)($value['title']       ?? '');
    $desc      = (string)($value['description'] ?? '');
    $published = (string)($value['publishedAt'] ?? '');
    $pages     = (array) ($value['pages']        ?? []);

    $body = leaflet_blocks_to_markdown($pages, $did, $pds);

    if ($desc === '') {
        foreach ($pages as $page) {
            foreach ($page['blocks'] ?? [] as $wrapper) {
                $block = $wrapper['block'] ?? $wrapper;
                if (($block['$type'] ?? '') === 'pub.leaflet.blocks.text') {
                    $plain = $block['plaintext'] ?? '';
                    if ($plain !== '') {
                        $desc = mb_strlen($plain) > 200
                            ? mb_substr($plain, 0, 200) . '…'
                            : $plain;
                        break 2;
                    }
                }
            }
        }
    }

    return [
        'id'         => abs(crc32($uri)),
        'slug'       => $rkey,
        'title'      => $title,
        'summary'    => $desc,
        'body'       => $body,
        'published'  => 1,
        'created_at' => $published,
        'updated_at' => $published,
    ];
}

/**
 * Return all published posts sorted newest-first.
 * Results are memoised in a static variable for the duration of the request
 * so sitemap.php and the router can call this function freely without
 * repeating the (possibly cached) file reads.
 *
 * @return array<int, array>
 */
function leaflet_get_all_posts(): array
{
    static $posts = null;
    if ($posts !== null) {
        return $posts;
    }

    $records = leaflet_fetch_documents();
    if (empty($records)) {
        $posts = [];
        return $posts;
    }

    $did = leaflet_resolve_did(BSKY_HANDLE) ?? '';
    $pds = $did !== '' ? (leaflet_resolve_pds($did) ?? '') : '';

    $normalized = array_map(
        static fn(array $r) => leaflet_normalize_record($r, $did, $pds),
        $records
    );

    usort(
        $normalized,
        static fn($a, $b) =>
        strcmp($b['created_at'], $a['created_at'])
    );

    $posts = array_values($normalized);
    return $posts;
}

/**
 * Find a single post by its slug (rkey).  Returns null when not found.
 */
function leaflet_get_post_by_slug(string $slug): ?array
{
    foreach (leaflet_get_all_posts() as $post) {
        if ($post['slug'] === $slug) {
            return $post;
        }
    }
    return null;
}
