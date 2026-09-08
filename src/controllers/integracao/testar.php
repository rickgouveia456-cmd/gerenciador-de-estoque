<?php
/**
 * Testa a conexão com o Sienge e retorna JSON
 */
requer_login();
$u = usuario_atual();
if ($u['perfil'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Acesso negado.']);
    exit;
}

header('Content-Type: application/json');
require_once HELPERS_PATH . '/sienge.php';

$resultado = sienge_testar_conexao();
echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
