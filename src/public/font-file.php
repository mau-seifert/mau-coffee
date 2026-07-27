<?php

/**
 * Proxy individual font files from fonts.gstatic.com.
 * Only requests to that exact host are allowed to prevent SSRF.
 * Files are cached on disk (src/cache/fonts/) since they are immutable.
 */

$url = (string) ($_GET['url'] ?? '');

const FONT_CACHE_CONTROL = 'public, max-age=31536000, immutable';

if (!preg_match('#^https://fonts\.gstatic\.com/[a-zA-Z0-9/_.\-]+$#', $url)) {
    http_response_code(400);
    exit;
}

$cacheDir  = __DIR__ . '/../cache/fonts';
$cacheFile = $cacheDir . '/' . hash('sha256', $url);

if (file_exists($cacheFile)) {
    $data = unserialize(file_get_contents($cacheFile), ['allowed_classes' => false]);
    if (is_array($data) && isset($data['type'], $data['body'])) {
        header('Content-Type: ' . $data['type']);
        header('Cache-Control: ' . FONT_CACHE_CONTROL);
        echo $data['body'];
        exit;
    }
}

$context = stream_context_create([
    'http' => [
        'method'  => 'GET',
        'timeout' => 10,
    ],
    'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
]);

$body = @file_get_contents($url, false, $context);

if ($body === false) {
    http_response_code(502);
    exit;
}

$ext  = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
$type = match ($ext) {
    'woff2' => 'font/woff2',
    'woff'  => 'font/woff',
    'ttf'   => 'font/ttf',
    default => 'application/octet-stream',
};

if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

file_put_contents($cacheFile, serialize(['type' => $type, 'body' => $body]));

header('Content-Type: ' . $type);
header('Cache-Control: ' . FONT_CACHE_CONTROL);
echo $body;
