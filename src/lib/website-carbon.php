<?php

require_once __DIR__ . '/cache.php';

const WEBSITE_CARBON_URL = 'https://mau.coffee/';
const WEBSITE_CARBON_REPORT_URL = 'https://www.websitecarbon.com/website/mau-coffee/';
const WEBSITE_CARBON_CACHE_VERSION = 1;
const WEBSITE_CARBON_CACHE_KEY = 'wcb_https%3A%2F%2Fmau.coffee%2F_v3';
const WEBSITE_CARBON_CACHE_TTL = 86400;

function website_carbon_cache_file(): string
{
    return __DIR__ . '/../cache/website-carbon/' . hash('sha256', WEBSITE_CARBON_URL . '|v' . WEBSITE_CARBON_CACHE_VERSION) . '.json';
}

function website_carbon_fetch(string $endpoint, string $accept): ?string
{
    $headers = implode("\r\n", [
        'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/124 Safari/537.36',
        'Accept: ' . $accept,
        'Accept-Language: en-US,en;q=0.9',
        'Origin: https://mau.coffee',
        'Referer: https://mau.coffee/',
    ]) . "\r\n";

    if (function_exists('curl_init')) {
        $ch = curl_init($endpoint);
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => array_filter(explode("\r\n", trim($headers))),
        ]);

        $body = curl_exec($ch);

        return is_string($body) ? $body : null;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => $headers,
            'ignore_errors' => true,
            'timeout' => 5,
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);

    $body = @file_get_contents($endpoint, false, $context);

    return $body === false ? null : $body;
}

function website_carbon_fetch_json(string $endpoint): ?array
{
    $body = website_carbon_fetch($endpoint, 'application/json');
    if ($body === null) {
        return null;
    }

    $decoded = json_decode($body, true);

    return is_array($decoded) ? $decoded : null;
}

function website_carbon_fetch_text(string $endpoint): ?string
{
    return website_carbon_fetch($endpoint, 'text/html,application/xhtml+xml');
}

function website_carbon_normalize_grams(float $grams): string
{
    return rtrim(rtrim(number_format($grams, 4, '.', ''), '0'), '.');
}

function website_carbon_format_result(float $grams, int|float|string|null $percent = null, ?string $comparison = null): array
{
    $title = 'Website Carbon report for mau.coffee';
    if (is_numeric($percent)) {
        $comparison = $comparison === 'dirtier' ? 'dirtier' : 'cleaner';
        $title = 'Website Carbon report: ' . $comparison . ' than ' . (int) round((float) $percent) . '% of tested pages';
    }

    return [
        'version' => WEBSITE_CARBON_CACHE_VERSION,
        'label' => website_carbon_normalize_grams($grams) . 'g CO2/view',
        'title' => $title,
    ];
}

function website_carbon_fetch_badge_result(): ?array
{
    $endpoint = 'https://api.websitecarbon.com/b?url=' . rawurlencode(WEBSITE_CARBON_URL);
    $result = website_carbon_fetch_json($endpoint);
    if ($result === null || isset($result['error']) || !is_numeric($result['c'] ?? null)) {
        return null;
    }

    return website_carbon_format_result((float) $result['c'], $result['p'] ?? null);
}

function website_carbon_parse_report(string $html): ?array
{
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
    $text = trim((string) preg_replace('/\s+/u', ' ', $text));

    if (!preg_match('/([0-9]+(?:\.[0-9]+)?)\s*g\s+of\s+CO(?:2|₂)e?\s+is\s+produced/iu', $text, $gramsMatch)) {
        return null;
    }

    $percent = null;
    $comparison = null;
    if (preg_match('/This\s+is\s+(cleaner|dirtier)\s+than\s+([0-9]+)\s*%\s+of\s+all\s+web\s+pages/i', $text, $cleanerMatch)) {
        $comparison = strtolower($cleanerMatch[1]);
        $percent = (int) $cleanerMatch[2];
    }

    return website_carbon_format_result((float) $gramsMatch[1], $percent, $comparison);
}

function website_carbon_fetch_report_result(): ?array
{
    $html = website_carbon_fetch_text(WEBSITE_CARBON_REPORT_URL);

    return $html === null ? null : website_carbon_parse_report($html);
}

function website_carbon_read_disk_cache(): ?array
{
    $cacheFile = website_carbon_cache_file();
    if (!is_file($cacheFile) || (time() - (int) filemtime($cacheFile)) > WEBSITE_CARBON_CACHE_TTL) {
        return null;
    }

    $cached = @file_get_contents($cacheFile);
    if ($cached === false) {
        return null;
    }

    $decoded = json_decode($cached, true);

    if (!is_array($decoded) || ($decoded['version'] ?? null) !== WEBSITE_CARBON_CACHE_VERSION) {
        return null;
    }

    return $decoded;
}

function website_carbon_write_disk_cache(array $result): void
{
    $cacheFile = website_carbon_cache_file();
    $cacheDir = dirname($cacheFile);

    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }

    @file_put_contents($cacheFile, json_encode($result, JSON_UNESCAPED_SLASHES));
}

function website_carbon_result(): ?array
{
    $cached = apcu_helper_fetch(WEBSITE_CARBON_CACHE_KEY);
    if (is_array($cached)) {
        return $cached;
    }

    $cached = website_carbon_read_disk_cache();
    if ($cached !== null) {
        apcu_helper_store(WEBSITE_CARBON_CACHE_KEY, $cached, WEBSITE_CARBON_CACHE_TTL);
        return $cached;
    }

    $normalized = website_carbon_fetch_badge_result() ?? website_carbon_fetch_report_result();
    if ($normalized === null) {
        return null;
    }

    apcu_helper_store(WEBSITE_CARBON_CACHE_KEY, $normalized, WEBSITE_CARBON_CACHE_TTL);
    website_carbon_write_disk_cache($normalized);

    return $normalized;
}
