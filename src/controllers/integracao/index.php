<?php
/**
 * Painel de Integrações — lista sistemas configurados e status
 */
requer_login();
$u = usuario_atual();
if ($u['perfil'] !== 'admin') {
    flash('Acesso restrito a administradores.', 'danger');
    redirect('/');
}

require_once HELPERS_PATH . '/sienge.php';

// Configuração atual do Sienge
$cfg    = sienge_config();
$ativo  = sienge_ativo();

// Últimos 20 logs de integração
$stmt = db()->query("
    SELECT * FROM integracao_log
    ORDER BY criado_em DESC
    LIMIT 20
");
$logs = $stmt->fetchAll();

// Contagem por status
$stmt2 = db()->query("
    SELECT status, COUNT(*) as total
    FROM integracao_log
    WHERE sistema = 'sienge'
    GROUP BY status
");
$contagens = [];
foreach ($stmt2->fetchAll() as $r) {
    $contagens[$r['status']] = $r['total'];
}

$pageTitle  = 'Integrações';
$activeMenu = 'integracao';
ob_start();
require VIEWS_PATH . '/integracao/index.php';
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/base.php';
