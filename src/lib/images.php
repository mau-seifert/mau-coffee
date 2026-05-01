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
