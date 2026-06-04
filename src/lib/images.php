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

function og_png_fallback_path(): string
{
    return __DIR__ . '/../public/manifest/web-app-manifest-512x512.png';
}

function og_cache_dir(): string
{
    return __DIR__ . '/../cache/og';
}

function og_send_png_file(string $path, string $etag): void
{
    $mtime = (int) (filemtime($path) ?: time());
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=21600');
    header('ETag: ' . $etag);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');

    $size = filesize($path);
    if ($size !== false) {
        header('Content-Length: ' . $size);
    }

    $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
    $ifModSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
    if ($ifNoneMatch === $etag || ($ifModSince && strtotime($ifModSince) >= $mtime)) {
        http_response_code(304);
        exit;
    }

    readfile($path);
    exit;
}

function og_send_png($image, string $etag, int $mtime): void
{
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=21600');
    header('ETag: ' . $etag);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');

    $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
    $ifModSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
    if ($ifNoneMatch === $etag || ($ifModSince && strtotime($ifModSince) >= $mtime)) {
        http_response_code(304);
        exit;
    }

    imagepng($image);
    exit;
}

function og_font_path(): string
{
    static $font = null;
    if ($font !== null) {
        return $font;
    }

    $candidates = [
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
        '/System/Library/Fonts/Supplemental/Arial.ttf',
        '/Library/Fonts/Arial.ttf',
    ];

    foreach ($candidates as $candidate) {
        if (is_readable($candidate)) {
            $font = $candidate;
            return $font;
        }
    }

    $font = '';
    return $font;
}

function og_draw_text($image, string $text, int $x, int $y, int $size, int $color, ?int $shadow = null): void
{
    $font = og_font_path();
    if ($font !== '' && function_exists('imagettftext')) {
        if ($shadow !== null) {
            imagettftext($image, $size, 0, $x + 4, $y + 5, $shadow, $font, $text);
        }
        imagettftext($image, $size, 0, $x, $y, $color, $font, $text);
        return;
    }

    $fontNumber = 5;
    if ($shadow !== null) {
        imagestring($image, $fontNumber, $x + 2, $y + 2, $text, $shadow);
    }
    imagestring($image, $fontNumber, $x, $y, $text, $color);
}

function og_copy_cover_jpeg($target, string $path): bool
{
    $source = @imagecreatefromjpeg($path);
    if ($source === false) {
        return false;
    }

    $sourceWidth = imagesx($source);
    $sourceHeight = imagesy($source);
    $targetWidth = imagesx($target);
    $targetHeight = imagesy($target);
    $sourceRatio = $sourceWidth / max(1, $sourceHeight);
    $targetRatio = $targetWidth / max(1, $targetHeight);

    if ($sourceRatio > $targetRatio) {
        $cropHeight = $sourceHeight;
        $cropWidth = (int) round($sourceHeight * $targetRatio);
        $cropX = (int) floor(($sourceWidth - $cropWidth) / 2);
        $cropY = 0;
    } else {
        $cropWidth = $sourceWidth;
        $cropHeight = (int) round($sourceWidth / $targetRatio);
        $cropX = 0;
        $cropY = (int) floor(($sourceHeight - $cropHeight) / 2);
    }

    imagecopyresampled($target, $source, 0, 0, $cropX, $cropY, $targetWidth, $targetHeight, $cropWidth, $cropHeight);
    return true;
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
    $cachePart = $type . '|' . $title . '|' . $description;
    if ($type === 'showcase') {
        $title = 'Photos';
        $description = 'A selection of photos taken with a Canon AE-1 film camera.';

        $imageName = showcase_home_thumbnail_or_original((string) ($_GET['image'] ?? ''));
        $imagePath = $imageName !== '' ? showcase_image_real_path($imageName) : '';
        if ($imagePath !== '') {
            $cachePart = 'showcase|' . $imageName . '|' . (int) (filemtime($imagePath) ?: 0) . '|' . (int) (filesize($imagePath) ?: 0);
        }
    }

    $fallback = og_png_fallback_path();
    $etag = '"' . sha1('og-png-v1|' . $cachePart) . '"';
    if (!function_exists('imagecreatetruecolor') || !function_exists('imagepng')) {
        og_send_png_file($fallback, $etag);
    }

    $cacheFile = $type === 'showcase' && $imagePath !== '' ? og_cache_dir() . '/' . trim($etag, '"') . '.png' : '';
    if ($cacheFile !== '' && is_file($cacheFile)) {
        og_send_png_file($cacheFile, $etag);
    }

    $image = imagecreatetruecolor(1200, 630);
    if ($image === false) {
        og_send_png_file($fallback, $etag);
    }

$taupe100 = imagecolorallocate($image, 243, 241, 241);
$taupe900 = imagecolorallocate($image, 38, 29, 22);
$muted = imagecolorallocate($image, 108, 98, 90);
$white = imagecolorallocate($image, 255, 255, 255);
$shadow = imagecolorallocatealpha($image, 0, 0, 0, 96);

if ($type === 'showcase' && $imagePath !== '' && og_copy_cover_jpeg($image, $imagePath)) {
    og_draw_text($image, 'Photos', 76, 500, 82, $white, $shadow);
    og_draw_text($image, $description, 82, 552, 24, $white, $shadow);
} else {
    imagefilledrectangle($image, 0, 0, 1200, 630, $taupe100);
    og_draw_text($image, $title, 76, 440, 58, $taupe900);
    og_draw_text($image, $description, 82, 504, 24, $muted);
    og_draw_text($image, 'mau.coffee', 82, 558, 17, $muted);
}

    if ($cacheFile !== '' && !is_dir(og_cache_dir())) {
        @mkdir(og_cache_dir(), 0755, true);
    }
    if ($cacheFile !== '') {
        @imagepng($image, $cacheFile);
    }
    $mtime = $cacheFile !== '' && is_file($cacheFile) ? (int) (filemtime($cacheFile) ?: time()) : time();
    og_send_png($image, $etag, $mtime);
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
