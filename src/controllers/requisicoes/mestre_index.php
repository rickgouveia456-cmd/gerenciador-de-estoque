<?php
requer_login();
$u = usuario_atual();

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$status  = $_GET['status'] ?? '';
$busca   = trim($_GET['busca'] ?? '');

$sql   = 'SELECT rm.*, u.nome AS mestre_nome, a.nome AS alm_nome FROM requisicao_mestre rm JOIN usuario u ON u.id=rm.mestre_id JOIN almoxarifado a ON a.id=rm.almoxarifado_id WHERE 1=1';
$binds = [];

// Filtro por perfil
if ($u['perfil'] === 'mestre') {
    $sql .= ' AND rm.mestre_id=?';
    $binds[] = $u['id'];
} elseif ($u['perfil'] === 'tecnico_seguranca') {
    $ids = almoxarifados_permitidos_ids();
    if ($ids) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $sql .= " AND rm.almoxarifado_id IN ($ph)";
        $binds = array_merge($binds, $ids);
    } else {
        $sql .= ' AND 1=0';
    }
} elseif ($u['perfil'] !== 'admin') {
    $ids = almoxarifados_permitidos_ids();
    if ($ids) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $sql .= " AND rm.almoxarifado_id IN ($ph)";
        $binds = array_merge($binds, $ids);
    } else {
        $sql .= ' AND 1=0';
    }
}

if ($status) { $sql .= ' AND rm.status=?'; $binds[] = $status; }
if ($busca)  { $sql .= ' AND (rm.colaborador LIKE ? OR rm.protocolo LIKE ?)'; $binds[] = "%$busca%"; $binds[] = "%$busca%"; }

// Count
$stmtC = db()->prepare(str_replace('rm.*, u.nome AS mestre_nome, a.nome AS alm_nome', 'COUNT(*)', $sql));
$stmtC->execute($binds);
$total = (int)$stmtC->fetchColumn();

$pag  = paginate($total, $page, $perPage);
$sql .= ' ORDER BY rm.data_criacao DESC LIMIT ' . $pag['per_page'] . ' OFFSET ' . $pag['offset'];
$stmt = db()->prepare($sql);
$stmt->execute($binds);
$requisicoes = $stmt->fetchAll();

$pageTitle  = 'Requisições';
$activeMenu = 'req_mestre';
ob_start();
require VIEWS_PATH . '/requisicoes/mestre_index.php';
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/base.php';
