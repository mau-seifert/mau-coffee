<?php
require_once __DIR__ . '/env.php';
load_env(__DIR__ . '/../../.env');

define('BSKY_HANDLE', $_ENV['BSKY_HANDLE'] ?? 'mau.coffee');
