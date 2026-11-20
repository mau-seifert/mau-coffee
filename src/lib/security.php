<?php

/** Send only for rendered HTML, never for cached assets, feeds or images. */
function send_html_security_headers(): string
{
    $nonce = base64_encode(random_bytes(32));
    $cloudflareSources = 'https://cloudflare.com https://*.cloudflare.com https://cloudflareinsights.com https://*.cloudflareinsights.com';
    header('Content-Type: text/html; charset=utf-8');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}' {$cloudflareSources}; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self' {$cloudflareSources}; object-src 'none'; base-uri 'self';");
    header('Cache-Control: private, no-store');
    header_remove('ETag');
    header_remove('Last-Modified');

    return $nonce;
}
