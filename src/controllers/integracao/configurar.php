<?php
/**
 * Salva configuração da integração Sienge
 */
requer_login();
$u = usuario_atual();
if ($u['perfil'] !== 'admin') {
    flash('Acesso restrito a administradores.', 'danger');
    redirect('/');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/integracao');
}

csrf_check();
require_once HELPERS_PATH . '/sienge.php';

$base_url   = trim($_POST['base_url']   ?? '');
$token      = trim($_POST['token']      ?? '');
$empresa_id = trim($_POST['empresa_id'] ?? '');
$ativo      = isset($_POST['ativo']) ? 1 : 0;

$sync_materiais   = isset($_POST['sync_materiais'])   ? true : false;
$sync_requisicoes = isset($_POST['sync_requisicoes']) ? true : false;
$sync_estoques    = isset($_POST['sync_estoques'])    ? true : false;

$config_json = json_encode([
    'base_url'          => $base_url,
    'token'             => $token,
    'empresa_id'        => $empresa_id,
    'sync_materiais'    => $sync_materiais,
    'sync_requisicoes'  => $sync_requisicoes,
    'sync_estoques'     => $sync_estoques,
], JSON_UNESCAPED_UNICODE);

// Upsert na tabela de configuração
$stmt = db()->prepare("
    INSERT INTO integracao_config (sistema, ativo, config_json)
    VALUES ('sienge', ?, ?)
    ON DUPLICATE KEY UPDATE ativo = VALUES(ativo), config_json = VALUES(config_json)
");
$stmt->execute([$ativo, $config_json]);

flash('Configuração do Sienge salva!', 'success');
redirect('/integracao');
