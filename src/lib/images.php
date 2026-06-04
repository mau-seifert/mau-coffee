<?php

function showcase_image_base_dir(): string
{
    static $dir = null;
    if ($dir !== null) {
        return $dir;
    }

    $resolved = realpath(__DIR__ . '/../public/showcase');
    $dir = $resolved !== false ? $resolved : '';

    return $dir;
}

function showcase_cache_dir(): string
{
    return __DIR__ . '/../cache/showcase';
}

function showcase_home_thumbnail_or_original(string $filename): string
{
    $basename = showcase_image_basename_or_empty($filename);
    if ($basename === '') {
        return '';
    }

    $info = pathinfo($basename);
    $name = (string) ($info['filename'] ?? '');
    $ext = (string) ($info['extension'] ?? '');
    if ($name === '' || $ext === '') {
        return $basename;
    }

    $candidate = $name . '_thumbnail.' . $ext;

    return showcase_image_real_path($candidate) !== '' ? $candidate : $basename;
}

function showcase_image_basename_or_empty(string $filename): string
{
    $basename = basename(rawurldecode($filename));
    if (!preg_match('/\.(jpe?g)$/i', $basename)) {
        return '';
    }

    return $basename;
}

function showcase_image_real_path(string $basename): string
{
    $showcaseDir = showcase_image_base_dir();
    if ($showcaseDir === '') {
        return '';
    }

    $fullPath = $showcaseDir . DIRECTORY_SEPARATOR . $basename;
    if (!is_file($fullPath)) {
        return '';
    }

    return $fullPath;
}

function showcase_image_mime_from_ext(string $ext): string
{
    return 'image/jpeg';
}

function og_clean_text(string $text): string
{
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

function og_truncate_text(string $text, int $maxLength): string
{
    $text = og_clean_text($text);
    if ($maxLength <= 3) {
        return '...';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text, 'UTF-8') <= $maxLength) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $maxLength - 3, 'UTF-8')) . '...';
    }

    if (strlen($text) <= $maxLength) {
        return $text;
    }

    return rtrim(substr($text, 0, $maxLength - 3)) . '...';
}

function og_svg_text(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function og_cache_dir(): string
{
    return __DIR__ . '/../cache/og';
}

function og_send_svg(string $etag, int $mtime, string $svg = '', string $file = ''): void
{
    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Cache-Control: public, max-age=21600');
    header('ETag: ' . $etag);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');

    $size = $file !== '' ? filesize($file) : false;
    if ($size !== false) {
        header('Content-Length: ' . $size);
    }

    $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
    $ifModSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
    if ($ifNoneMatch === $etag || ($ifModSince && strtotime($ifModSince) >= $mtime)) {
        http_response_code(304);
        exit;
    }

    if ($file !== '') {
        readfile($file);
    } else {
        echo $svg;
    }
    exit;
}

/**
 * Serve the generated share image used by Open Graph and Twitter cards.
 */
function serve_open_graph_image(): void
{
    $type = (string) ($_GET['type'] ?? 'page');
    $title = og_truncate_text((string) ($_GET['title'] ?? 'Notes by mau'), 32);
    $description = og_truncate_text((string) ($_GET['description'] ?? 'Make yourself at home, pour a cup, and linger for a moment.'), 72);

    $imagePath = '';
    $imageCachePart = '';
    if ($type === 'showcase') {
        $title = 'Photos';
        $description = 'A selection of photos taken with a Canon AE-1 film camera.';

        $imageName = showcase_home_thumbnail_or_original((string) ($_GET['image'] ?? ''));
        $imagePath = $imageName !== '' ? showcase_image_real_path($imageName) : '';
        if ($imagePath !== '') {
            $imageCachePart = $imageName . '|' . (int) (filemtime($imagePath) ?: 0) . '|' . (int) (filesize($imagePath) ?: 0);
        }
    }

    $cacheDir = og_cache_dir();
    $cacheFile = '';
    $etag = '"' . sha1('og-svg-v2|' . $type . '|' . $title . '|' . $description . '|' . $imageCachePart) . '"';
    if ($type === 'showcase' && $imagePath !== '') {
        $cacheKey = sha1('og-svg-v2|showcase|' . $imageCachePart);
        $cacheFile = $cacheDir . '/' . $cacheKey . '.svg';
        $etag = '"' . $cacheKey . '"';

        if (is_file($cacheFile)) {
            og_send_svg($etag, (int) (filemtime($cacheFile) ?: time()), '', $cacheFile);
        }
    }

    $safeTitle = og_svg_text($title);
    $safeDescription = og_svg_text($description);
    $imageBytes = $imagePath !== '' ? @file_get_contents($imagePath) : false;
    $safeImage = $imageBytes !== false ? og_svg_text('data:image/jpeg;base64,' . base64_encode($imageBytes)) : '';

    if ($type === 'showcase' && $safeImage !== '') {
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630" role="img" aria-label="{$safeTitle}">
  <defs>
    <filter id="text-shadow" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="7" stdDeviation="9" flood-color="#140f0b" flood-opacity="0.72"/>
    </filter>
    <linearGradient id="shade" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#000" stop-opacity="0.04"/>
      <stop offset="1" stop-color="#000" stop-opacity="0.66"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="630" fill="#261d16"/>
  <image href="{$safeImage}" width="1200" height="630" preserveAspectRatio="xMidYMid slice"/>
  <rect width="1200" height="630" fill="url(#shade)"/>
  <g filter="url(#text-shadow)" font-family="Sora, Arial, sans-serif" fill="#fff">
    <text x="76" y="488" font-size="112" font-weight="700" letter-spacing="-4">{$safeTitle}</text>
    <text x="82" y="548" font-size="30" font-weight="500" opacity="0.9">{$safeDescription}</text>
  </g>
</svg>
SVG;
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        if ($cacheFile !== '') {
            @file_put_contents($cacheFile, $svg);
        }
        $mtime = $cacheFile !== '' && is_file($cacheFile) ? (int) (filemtime($cacheFile) ?: time()) : time();
        og_send_svg($etag, $mtime, $svg);
    }

    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630" role="img" aria-label="{$safeTitle}">
  <defs>
    <radialGradient id="warm-a" cx="14%" cy="20%" r="58%">
      <stop offset="0" stop-color="#fff8ec"/>
      <stop offset="1" stop-color="#f3f1f1" stop-opacity="0"/>
    </radialGradient>
    <radialGradient id="warm-b" cx="88%" cy="8%" r="46%">
      <stop offset="0" stop-color="#d9c7ad" stop-opacity="0.55"/>
      <stop offset="1" stop-color="#f3f1f1" stop-opacity="0"/>
    </radialGradient>
    <filter id="soft-shadow" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="18" stdDeviation="18" flood-color="#261d16" flood-opacity="0.12"/>
    </filter>
  </defs>
  <rect width="1200" height="630" fill="#f3f1f1"/>
  <rect width="1200" height="630" fill="url(#warm-a)"/>
  <rect width="1200" height="630" fill="url(#warm-b)"/>
  <circle cx="1056" cy="134" r="178" fill="#261d16" opacity="0.055"/>
  <circle cx="1012" cy="160" r="94" fill="none" stroke="#261d16" stroke-width="2" opacity="0.14"/>
  <path d="M72 95 C210 54 342 98 452 62 C560 27 656 48 742 82" fill="none" stroke="#261d16" stroke-width="3" opacity="0.14"/>
  <g font-family="Sora, Arial, sans-serif" fill="#261d16" filter="url(#soft-shadow)">
    <text x="76" y="440" font-size="82" font-weight="700" letter-spacing="-3">{$safeTitle}</text>
    <text x="82" y="504" font-size="30" font-weight="500" opacity="0.74">{$safeDescription}</text>
  </g>
  <text x="82" y="558" font-family="Sora, Arial, sans-serif" font-size="22" font-weight="600" fill="#261d16" opacity="0.48">mau.coffee</text>
</svg>
SVG;
    og_send_svg($etag, time(), $svg);
}

if (!function_exists('showcase_use_x_accel_redirect')) {
    function showcase_use_x_accel_redirect(): bool
    {
        return false;
    }
}

if (!function_exists('showcase_x_accel_internal_prefix')) {
    function showcase_x_accel_internal_prefix(): string
    {
        return '';
    }
}

if (!function_exists('showcase_use_x_sendfile')) {
    function showcase_use_x_sendfile(): bool
    {
        return false;
    }
}

function showcase_stream_file(string $path, string $mimeType, ?string $cacheStatus = null): void
{
    if ($cacheStatus !== null) {
        header('X-Cache: ' . $cacheStatus);
    }
    header('Content-Type: ' . $mimeType);

    $len = filesize($path);
    if ($len !== false) {
        header('Content-Length: ' . $len);
    }

    $useXAccel = showcase_use_x_accel_redirect();
    $useXSendfile = showcase_use_x_sendfile();

    if ($useXAccel) {
        $internalPrefix = showcase_x_accel_internal_prefix();
        $internal = rtrim($internalPrefix, '/') . '/' . basename($path);
        header('X-Accel-Redirect: ' . $internal);
        return;
    }

    if ($useXSendfile) {
        header('X-Sendfile: ' . $path);
        return;
    }

    $fp = @fopen($path, 'rb');
    if (!$fp) {
        http_response_code(500);
        exit;
    }

    while (ob_get_level()) {
        ob_end_flush();
    }
    fpassthru($fp);
    fclose($fp);
}

/**
 * Return sorted showcase image filenames.
 *
 * @return array<int, string>
 */
function get_showcase_image_filenames(): array
{
    $showcaseDir = showcase_image_base_dir();
    if ($showcaseDir === '') {
        return [];
    }

    /** @var array<string, array<int, string>> */
    static $requestCache = [];
    if (isset($requestCache[$showcaseDir])) {
        return $requestCache[$showcaseDir];
    }

    $files = array_filter(
        scandir($showcaseDir) ?: [],
        static fn($f) => is_file($showcaseDir . DIRECTORY_SEPARATOR . $f)
            && in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), ['jpg', 'jpeg'], true)
            && !preg_match('/_thumbnail\.jpe?g$/i', $f)
    );
    $files = array_values($files);
    sort($files);

    $requestCache[$showcaseDir] = $files;
    return $files;
}

/**
 * Serve a showcase image without server-side processing.
 */
function serve_showcase_image(string $filename, string $forceFormat = ''): void
{
    $basename = showcase_image_basename_or_empty($filename);
    if ($basename === '') {
        http_response_code(404);
        exit;
    }

    $realPath = showcase_image_real_path($basename);
    if ($realPath === '') {
        http_response_code(404);
        exit;
    }

    $mtime = (int) (filemtime($realPath) ?: 0);

    if ($forceFormat !== '') {
        http_response_code(404);
        exit;
    }

    $ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
    $mimeType = showcase_image_mime_from_ext($ext);
    $size = (int) (filesize($realPath) ?: 0);
    $etag = '"' . sha1($realPath . '|' . $mtime . '|' . $size) . '-orig"';

    header('Cache-Control: public, max-age=31536000, immutable');
    header('ETag: ' . $etag);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');

    $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
    $ifModSince  = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
    if ($ifNoneMatch === $etag || ($ifModSince && strtotime($ifModSince) >= $mtime)) {
        http_response_code(304);
        exit;
    }

    showcase_stream_file($realPath, $mimeType, 'ORIG');

    exit;
}
