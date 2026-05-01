<?php
/**
 * Proxy the Google Fonts CSS and rewrite font-file URLs so the browser never
 * connects directly to Google's servers.  The CSS is cached in APCu for 24 h.
 */
require_once __DIR__ . '/../lib/cache.php';

const GFONTS_URL = 'https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap';
const GFONTS_CACHE_KEY = 'gfonts_css_v1';
const GFONTS_CACHE_TTL = 86400; // 24 h

$css = apcu_helper_fetch(GFONTS_CACHE_KEY);

if ($css === null) {
    $context = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/124 Safari/537.36\r\n",
            'timeout' => 5,
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);

    $css = @file_get_contents(GFONTS_URL, false, $context);

    if ($css === false) {
        http_response_code(502);
        exit;
    }

    $css = preg_replace_callback(
        '/url\((https:\/\/fonts\.gstatic\.com\/[^)]+)\)/',
        static fn(array $m): string => 'url(/font-file.php?url=' . rawurlencode($m[1]) . ')',
        $css
    ) ?? $css;

    apcu_helper_store(GFONTS_CACHE_KEY, $css, GFONTS_CACHE_TTL);
}

header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: public, max-age=' . GFONTS_CACHE_TTL);
echo $css;
