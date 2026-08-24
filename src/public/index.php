<?php
/**
 * Logi-Prime PHP — Front Controller
 * Ponto de entrada unico de todas as requisicoes
 */

// Autoload / bootstrap
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/functions.php';
require_once dirname(__DIR__) . '/helpers/auth.php';

// Iniciar sessao
if (session_status() === PHP_SESSION_NONE) {
    session_name('LOGIPRIME_SESS');
    session_start();
}

// Renovar sessao ativa
if (isset($_SESSION['usuario_id'])) {
    $_SESSION['last_activity'] = time();
}

// Headers de seguranca
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (APP_ENV !== 'development') {
    header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
}

// Roteamento
require_once dirname(__DIR__) . '/router.php';
