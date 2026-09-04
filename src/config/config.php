<?php
/**
 * Logi-Prime — Configuracao central
 */

// Carregar .env se existir (fora do container Docker)
$envFile = dirname(__DIR__, 2) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            if (!isset($_ENV[$key]) && !getenv($key)) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
            }
        }
    }
}

function env(string $key, $default = null) {
    $val = $_ENV[$key] ?? getenv($key);
    return ($val !== false && $val !== null && $val !== '') ? $val : $default;
}

define('APP_ENV',    env('APP_ENV', 'production'));
define('APP_SECRET', env('APP_SECRET', 'changeme_32chars_minimum_please!!'));
define('APP_URL',    env('APP_URL', 'http://localhost:8080'));
define('APP_DEBUG',  APP_ENV === 'development');

define('DB_HOST', env('DB_HOST', 'db'));
define('DB_PORT', (int) env('DB_PORT', 3306));
define('DB_NAME', env('DB_NAME', 'logiprime'));
define('DB_USER', env('DB_USER', 'logiprime'));
define('DB_PASS', env('DB_PASS', 'logiprime123'));

// Fuso horario de Brasilia
date_default_timezone_set('America/Sao_Paulo');

// Sessao segura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 3600);

if (APP_ENV !== 'development') {
    ini_set('session.cookie_secure', 1);
    ini_set('display_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Paths
define('ROOT_PATH',  dirname(__DIR__));
define('VIEWS_PATH', ROOT_PATH . '/views');
define('LIB_PATH',   ROOT_PATH . '/lib');
