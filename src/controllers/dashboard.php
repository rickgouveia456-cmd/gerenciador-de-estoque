<?php
requer_login();
$u = usuario_atual();

// Redirecionar mestre/tecnico/colaborador com requisitar para tela de requisicoes
if (in_array($u['perfil'], ['mestre', 'tecnico_seguranca'])) {
    redirect('/requisicoes/mestre');
}
if ($u['perfil'] === 'colaborador') {
    $stmt = db()->prepare("SELECT COUNT(*) FROM permissao_extra WHERE usuario_id=? AND permissao='fazer_requisicao'");
    $stmt->execute([$u['id']]);
    if ($u['pode_requisitar'] || $stmt->fetchColumn() > 0) {
        redirect('/requisicoes/mestre');
    }
}

$ids = almoxarifados_permitidos_ids();
$idsStr = $ids ? implode(',', array_map('intval', $ids)) : '0';

// Almoxarifados visiveis
if ($u['perfil'] === 'admin') {
    $almoxarifados = db()->query('SELECT * FROM almoxarifado ORDER BY cidade, obra, nome')->fetchAll();
} elseif ($u['perfil'] === 'analista' && $u['almoxarifado_id']) {
    $stmtA = db()->prepare('SELECT * FROM almoxarifado WHERE id=?');
    $stmtA->execute([$u['almoxarifado_id']]);
    $refAlm = $stmtA->fetch();
    if ($refAlm && $refAlm['cidade']) {
        $stmtA2 = db()->prepare('SELECT * FROM almoxarifado WHERE cidade=? ORDER BY nome');
        $stmtA2->execute([$refAlm['cidade']]);
        $almoxarifados = $stmtA2->fetchAll();
    } else {
        $almoxarifados = $refAlm ? [$refAlm] : [];
    }
} else {
    if ($ids) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmtA = db()->prepare("SELECT * FROM almoxarifado WHERE id IN ($ph) ORDER BY cidade, obra, nome");
        $stmtA->execute($ids);
        $almoxarifados = $stmtA->fetchAll();
    } else {
        $almoxarifados = [];
    }
}

// IDs dos almoxarifados visiveis
$almIds = array_column($almoxarifados, 'id');
$almIdsStr = $almIds ? implode(',', array_map('intval', $almIds)) : '0';

// Alertas (quantidade <= estoque_minimo)
$alertas = db()->query(
    "SELECT i.*, a.nome AS alm_nome
     FROM item i
     JOIN almoxarifado a ON a.id = i.almoxarifado_id
     WHERE i.quantidade <= i.estoque_minimo AND i.ativo = 1 AND i.almoxarifado_id IN ($almIdsStr)
     ORDER BY i.quantidade ASC
     LIMIT 50"
)->fetchAll();

// Stats
$totalItens   = (int)db()->query("SELECT COUNT(*) FROM item WHERE ativo=1 AND almoxarifado_id IN ($almIdsStr)")->fetchColumn();
$itensAlerta  = count(array_filter($alertas, fn($a) => $a['quantidade'] > 0));
$itensCritico = count(array_filter($alertas, fn($a) => $a['quantidade'] <= 0));

// Movimentacoes recentes (ultimas 10)
$movRecentes = db()->query(
    "SELECT m.*, i.nome AS item_nome, i.unidade, a.nome AS alm_nome
     FROM movimentacao m
     JOIN item i ON i.id = m.item_id
     JOIN almoxarifado a ON a.id = i.almoxarifado_id
     WHERE i.almoxarifado_id IN ($almIdsStr)
     ORDER BY m.data DESC
     LIMIT 10"
)->fetchAll();

// Requisicoes mestre pendentes
$reqPendentes = (int)db()->query(
    "SELECT COUNT(*) FROM requisicao_mestre
     WHERE status='pendente' AND almoxarifado_id IN ($almIdsStr)"
)->fetchColumn();

$stats = [
    'total_almoxarifados' => count($almoxarifados),
    'total_itens'         => $totalItens,
    'itens_alerta'        => $itensAlerta,
    'itens_criticos'      => $itensCritico,
    'req_pendentes'       => $reqPendentes,
];

$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';
ob_start();
require VIEWS_PATH . '/dashboard/index.php';
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/base.php';
