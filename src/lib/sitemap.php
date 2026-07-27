<?php
/**
 * Dynamic sitemap generator (sitemap.xml)
 *
 * - Generates an up-to-date sitemap on each request (no deployment required).
 * - Includes published blog posts and the showcase images (attached to the
 *   /showcase page via the image sitemap extension).
 */

require_once __DIR__ . '/posts.php';
require_once __DIR__ . '/images.php';
require_once __DIR__ . '/site.php';

function sitemap_last_modified(mixed $value): ?string
{
    if (is_int($value) || (is_string($value) && ctype_digit($value))) {
        $timestamp = (int) $value;
    } else {
        $timestamp = strtotime((string) $value) ?: 0;
    }

    return $timestamp > 0 ? gmdate('c', $timestamp) : null;
}

/**
 * Serve XML sitemap and exit.
 */
function serve_sitemap(): void
{
    header('Content-Type: application/xml; charset=utf-8');

    $base = SITE_ORIGIN;

    $urls = [];

    $posts = get_published_posts();

    $latestPostTs = null;
    foreach ($posts as $p) {
        $cand = $p['updated_at'] ?? $p['created_at'] ?? null;
        if ($cand && ($latestPostTs === null || strtotime($cand) > strtotime($latestPostTs))) {
            $latestPostTs = $cand;
        }
    }

    $urls[] = [
        'loc' => $base . '/',
        'lastmod' => $latestPostTs,
        'images' => [],
    ];

    $urls[] = [
        'loc' => $base . '/blog',
        'lastmod' => $latestPostTs,
        'images' => [],
    ];

    foreach ($posts as $p) {
        $lastmod = $p['updated_at'] ?? $p['created_at'] ?? null;

        $images = [];
        if (!empty($p['body'])) {
            if (preg_match_all('/!\[[^\]]*\]\(([^)]+)\)/', $p['body'], $m)) {
                foreach ($m[1] as $img) {
                    if (count($images) >= 5) break;
                    $img = trim($img);
                    if ($img === '') continue;
                    $schemePart = parse_url($img, PHP_URL_SCHEME);
                    if ($schemePart === null) {
                        if (str_starts_with($img, '/')) {
                            $images[] = $base . $img;
                        } else {
                            $images[] = $base . '/' . ltrim($img, '/');
                        }
                    } else {
                        $images[] = $img;
                    }
                }
            }
        }

        $urls[] = [
            'loc' => $base . '/blog/' . rawurlencode($p['slug']),
            'lastmod' => $lastmod,
            'images' => $images,
        ];
    }

    $showcaseImageFiles = get_showcase_image_filenames();
    $showcaseImages = array_map(fn($f) => $base . '/img/showcase/' . rawurlencode($f), $showcaseImageFiles);
    $showcaseBaseDir = showcase_image_base_dir();
    $showcaseLast = $showcaseBaseDir !== '' ? (filemtime($showcaseBaseDir) ?: null) : null;

    $urls[] = [
        'loc' => $base . '/showcase',
        'lastmod' => $showcaseLast ? gmdate('c', $showcaseLast) : null,
        'images' => $showcaseImages,
    ];

    $urls[] = [
        'loc' => $base . '/webrings',
        'lastmod' => null,
        'images' => [],
    ];

    $urls[] = [
        'loc' => $base . '/privacy',
        'lastmod' => null,
        'images' => [],
    ];

    $out = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $out .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:image=\"http://www.google.com/schemas/sitemap-image/1.1\">\n";

    foreach ($urls as $u) {
        $out .= "  <url>\n";
        $out .= '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
        if (!empty($u['lastmod'])) {
            $last = sitemap_last_modified($u['lastmod']);
            if ($last !== null) {
                $out .= '    <lastmod>' . htmlspecialchars($last, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</lastmod>\n";
            }
        }

        if (!empty($u['images'])) {
            foreach ($u['images'] as $img) {
                $out .= "    <image:image>\n";
                $out .= '      <image:loc>' . htmlspecialchars($img, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</image:loc>\n";
                $title = pathinfo(parse_url($img, PHP_URL_PATH) ?? $img, PATHINFO_FILENAME);
                if ($title) {
                    $out .= '      <image:caption>' . htmlspecialchars($title, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</image:caption>\n";
                }
                $out .= "    </image:image>\n";
            }
        }

        $out .= "  </url>\n";
    }

    $out .= "</urlset>\n";

    header('Cache-Control: public, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    echo $out;
    exit;
}
