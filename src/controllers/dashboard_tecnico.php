<?php
requer_login();
$u = usuario_atual();

// Apenas tecnico_seguranca, mestre, admin e almoxarife acessam
if (!in_array($u['perfil'], ['tecnico_seguranca', 'mestre', 'admin', 'almoxarife'])) {
    flash('Acesso não autorizado.', 'danger');
    redirect('/');
}

$usuarioId = (int)$u['id'];
$isAdmin   = $u['perfil'] === 'admin';

// Estatísticas de requisições
if ($isAdmin) {
    $totalReqs     = (int)db()->query("SELECT COUNT(*) FROM requisicao_mestre")->fetchColumn();
    $pendentes     = (int)db()->query("SELECT COUNT(*) FROM requisicao_mestre WHERE status='pendente'")->fetchColumn();
    $aprovadas     = (int)db()->query("SELECT COUNT(*) FROM requisicao_mestre WHERE status='aprovada'")->fetchColumn();
    $entregues     = (int)db()->query("SELECT COUNT(*) FROM requisicao_mestre WHERE status='entregue'")->fetchColumn();
} else {
    $stTotal = db()->prepare("SELECT COUNT(*) FROM requisicao_mestre WHERE solicitante_id=?");
    $stTotal->execute([$usuarioId]);
    $totalReqs = (int)$stTotal->fetchColumn();

    $stPend = db()->prepare("SELECT COUNT(*) FROM requisicao_mestre WHERE solicitante_id=? AND status='pendente'");
    $stPend->execute([$usuarioId]);
    $pendentes = (int)$stPend->fetchColumn();

    $stAprov = db()->prepare("SELECT COUNT(*) FROM requisicao_mestre WHERE solicitante_id=? AND status='aprovada'");
    $stAprov->execute([$usuarioId]);
    $aprovadas = (int)$stAprov->fetchColumn();

    $stEntr = db()->prepare("SELECT COUNT(*) FROM requisicao_mestre WHERE solicitante_id=? AND status='entregue'");
    $stEntr->execute([$usuarioId]);
    $entregues = (int)$stEntr->fetchColumn();
}

// Requisições recentes (últimas 10)
if ($isAdmin) {
    $stRecentes = db()->query(
        "SELECT r.*, u.nome AS solicitante_nome, a.nome AS alm_nome
         FROM requisicao_mestre r
         JOIN usuario u ON u.id = r.solicitante_id
         JOIN almoxarifado a ON a.id = r.almoxarifado_id
         ORDER BY r.criado_em DESC LIMIT 10"
    );
} else {
    $stRecentes = db()->prepare(
        "SELECT r.*, u.nome AS solicitante_nome, a.nome AS alm_nome
         FROM requisicao_mestre r
         JOIN usuario u ON u.id = r.solicitante_id
         JOIN almoxarifado a ON a.id = r.almoxarifado_id
         WHERE r.solicitante_id = ?
         ORDER BY r.criado_em DESC LIMIT 10"
    );
    $stRecentes->execute([$usuarioId]);
}
$reqRecentes = $stRecentes->fetchAll();

// Almoxarifados disponíveis para nova requisição
$ids = almoxarifados_permitidos_ids();
$almoxarifados = [];
if (!empty($ids)) {
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $stA = db()->prepare("SELECT * FROM almoxarifado WHERE id IN ($ph) ORDER BY nome");
    $stA->execute($ids);
    $almoxarifados = $stA->fetchAll();
}

$pageTitle  = 'Dashboard Técnico';
$activeMenu = 'dashboard';
ob_start();
require VIEWS_PATH . '/dashboard/tecnico.php';
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/base.php';
