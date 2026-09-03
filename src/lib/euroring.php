<?php

require_once __DIR__ . '/cache.php';

function normalizeWebringUrl(string $url): ?string
{
    $parts = parse_url(trim($url));

    if (
        $parts === false
        || !isset($parts['scheme'], $parts['host'])
        || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
    ) {
        return null;
    }

    $host = strtolower($parts['host']);
    $path = $parts['path'] ?? '/';
    $path = '/' . ltrim($path, '/');

    if ($path !== '/') {
        $path = rtrim($path, '/');
    }

    return $host . $path;
}

function parseEuroringSites(string $javascript): ?array
{
    if (
        !preg_match(
            '/\b(?:var|let|const)\s+sites\s*=\s*\[(.*?)\]\s*;/s',
            $javascript,
            $siteList
        )
    ) {
        return null;
    }

    $siteListSource = preg_replace(
        ['~/\*.*?\*/~s', '~^[ \t]*//[^\r\n]*~m'],
        '',
        $siteList[1]
    );

    if (!is_string($siteListSource)) {
        return null;
    }

    preg_match_all(
        '~(["\'])(.*?)\1~s',
        $siteListSource,
        $matches
    );

    $sites = [];

    foreach ($matches[2] ?? [] as $url) {
        if (
            strlen($url) > 2048
            || normalizeWebringUrl($url) === null
        ) {
            return null;
        }

        $sites[] = $url;
    }

    $sites = array_values(array_unique($sites));

    return count($sites) >= 2 ? $sites : null;
}

function loadEuroringSites(): array
{
    $cacheKey = 'mau_euroring_sites_v1';
    $staleCacheKey = 'mau_euroring_sites_stale_v1';
    $cacheLifetime = 86400;

    $cachedSites = apcu_helper_fetch($cacheKey);
    if (is_array($cachedSites)) {
        return $cachedSites;
    }

    $sourceUrl = 'https://euroring.neocities.org/scripts/onionring-variables.js';

    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'follow_location' => 0,
            'user_agent' => 'mau.coffee EuroRing widget/1.0',
        ],
        'https' => [
            'timeout' => 5,
            'follow_location' => 0,
            'user_agent' => 'mau.coffee EuroRing widget/1.0',
        ],
    ]);

    $javascript = @file_get_contents(
        $sourceUrl,
        false,
        $context,
        offset: 0,
        length: 250000
    );

    if (!is_string($javascript) || $javascript === '') {
        $staleSites = apcu_helper_fetch($staleCacheKey);
        if (is_array($staleSites)) {
            return $staleSites;
        }

        return [];
    }

    $sites = parseEuroringSites($javascript);
    if ($sites === null) {
        $staleSites = apcu_helper_fetch($staleCacheKey);
        return is_array($staleSites) ? $staleSites : [];
    }

    apcu_helper_store($cacheKey, $sites, $cacheLifetime);
    apcu_helper_store($staleCacheKey, $sites, 604800);

    return $sites;
}

function getEuroringNavigation(string $registeredUrl): ?array
{
    $sites = loadEuroringSites();
    $currentSite = normalizeWebringUrl($registeredUrl);

    if ($currentSite === null || count($sites) < 2) {
        return null;
    }

    foreach ($sites as $index => $site) {
        if (normalizeWebringUrl($site) !== $currentSite) {
            continue;
        }

        $siteCount = count($sites);

        return [
            'previous' => $sites[($index - 1 + $siteCount) % $siteCount],
            'next' => $sites[($index + 1) % $siteCount],
        ];
    }

    return null;
}

$euroringNavigation = getEuroringNavigation('https://mau.coffee');
