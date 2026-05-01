<?php
/**
 * Shared APCu helpers used by both the Leaflet adapter and the showcase
 * image pipeline.  All functions are intentionally generic — the caller is
 * responsible for choosing a uniquely-namespaced key.
 */

function apcu_available(): bool
{
    return function_exists('apcu_fetch')
        && (PHP_SAPI !== 'cli' || (bool) ini_get('apc.enable_cli'))
        && (bool) ini_get('apc.enabled');
}

/**
 * Fetch a value from APCu.  Returns null on a miss or when APCu is disabled.
 */
function apcu_helper_fetch(string $key): mixed
{
    if (!apcu_available()) {
        return null;
    }
    $ok    = false;
    $value = apcu_fetch($key, $ok);
    return $ok ? $value : null;
}

/**
 * Store a value in APCu.  Silently does nothing when APCu is disabled.
 */
function apcu_helper_store(string $key, mixed $value, int $ttl = 0): void
{
    if (!apcu_available()) {
        return;
    }
    apcu_store($key, $value, $ttl);
}
