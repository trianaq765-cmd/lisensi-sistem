<?php
$database_url = getenv('DATABASE_URL');

if ($database_url) {
    $db = parse_url($database_url);
    define('DB_HOST', $db['host']);
    define('DB_PORT', $db['port'] ?? 5432);
    define('DB_NAME', ltrim($db['path'], '/'));
    define('DB_USER', $db['user']);
    define('DB_PASS', $db['pass']);
} else {
    define('DB_HOST', 'localhost');
    define('DB_PORT', 5432);
    define('DB_NAME', 'license_system');
    define('DB_USER', 'postgres');
    define('DB_PASS', '');
}

define('SECRET_KEY', getenv('SECRET_KEY') ?: 'secret123');
define('API_KEY', getenv('API_KEY') ?: 'apikey123');
