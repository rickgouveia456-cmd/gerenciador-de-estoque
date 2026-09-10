<?php
/**
 * Logi-Prime — Autenticação da API REST externa
 *
 * Qualquer sistema externo (Sienge, ERP, app mobile) precisa enviar:
 *   Header: Authorization: Bearer SEU_TOKEN_AQUI
 *
 * O token é configurado no banco em configuracao_sistema chave='api_token'
 * ou via variável de ambiente API_TOKEN no .env
 */

function api_autenticar(): void {
    header('Content-Type: application/json; charset=utf-8');

    // Pega o token do header Authorization
    $authHeader = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? apache_request_headers()['Authorization']
        ?? '';

    $token = '';
    if (preg_match('/^Bearer\s+(.+)$/i', trim($authHeader), $m)) {
        $token = trim($m[1]);
    }

    if (!$token) {
        api_erro(401, 'Token de autenticação não fornecido. Use: Authorization: Bearer SEU_TOKEN');
    }

    // Token configurado: .env tem prioridade sobre o banco
    $tokenEsperado = env('API_TOKEN', '') ?: cfg('api_token', '');

    if (!$tokenEsperado) {
        api_erro(503, 'API não configurada. Defina API_TOKEN no .env ou em Admin → Configurações.');
    }

    if (!hash_equals($tokenEsperado, $token)) {
        api_erro(401, 'Token inválido.');
    }
}

function api_resposta(array $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function api_erro(int $status, string $mensagem): never {
    http_response_code($status);
    echo json_encode(['erro' => true, 'mensagem' => $mensagem, 'status' => $status], JSON_UNESCAPED_UNICODE);
    exit;
}

function api_metodo(string ...$permitidos): void {
    if (!in_array($_SERVER['REQUEST_METHOD'], $permitidos)) {
        api_erro(405, 'Método não permitido. Use: ' . implode(', ', $permitidos));
    }
}

function api_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        api_erro(400, 'Body inválido. Envie JSON válido.');
    }
    return $data ?? [];
}
