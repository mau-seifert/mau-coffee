<?php

/**
 * Canonical public site details shared by HTML, feeds, and the sitemap.
 */
const SITE_ORIGIN = 'https://mau.coffee';
const SITE_NAME = 'Notes by mau';
const SITE_DESCRIPTION = 'Make yourself at home, pour a cup, and linger for a moment.';

function site_url(string $path = '/'): string
{
    return SITE_ORIGIN . '/' . ltrim($path, '/');
}
