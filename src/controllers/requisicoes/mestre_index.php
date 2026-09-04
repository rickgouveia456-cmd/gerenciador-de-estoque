<?php
requer_login();
$u = usuario_atual();

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$status  = $_GET['status'] ?? '';
$busca   = trim($_GET['busca'] ?? '');

$sql   = 'SELECT rm.*,
                 u2.nome AS mestre_nome,
                 a.nome  AS alm_nome,
                 (SELECT COUNT(*) FROM requisicao_mestre_item rmi WHERE rmi.requisicao_id = rm.id) AS total_itens
          FROM requisicao_mestre rm
          JOIN usuario u2 ON u2.id = rm.mestre_id
          JOIN almoxarifado a ON a.id = rm.almoxarifado_id
          WHERE 1=1';
$binds = [];

// Filtro por perfil
if (in_array($u['perfil'], ['mestre', 'tecnico_seguranca'])) {
    // mestre e técnico de segurança veem SOMENTE as próprias requisições
    $sql    .= ' AND rm.mestre_id=?';
    $binds[] = $u['id'];
} elseif ($u['perfil'] !== 'admin') {
    $ids = almoxarifados_permitidos_ids();
    if ($ids) {
        $ph     = implode(',', array_fill(0, count($ids), '?'));
        $sql   .= " AND rm.almoxarifado_id IN ($ph)";
        $binds  = array_merge($binds, $ids);
    } else {
        $sql .= ' AND 1=0';
    }
}

if ($status) { $sql .= ' AND rm.status=?'; $binds[] = $status; }
if ($busca)  { $sql .= ' AND (rm.colaborador LIKE ? OR rm.protocolo LIKE ? OR u2.nome LIKE ?)'; $binds[] = "%$busca%"; $binds[] = "%$busca%"; $binds[] = "%$busca%"; }

// Stats (sem filtro de status para contar todos)
$sqlStats = str_replace(
    'SELECT rm.*, u2.nome AS mestre_nome, a.nome  AS alm_nome, (SELECT COUNT(*) FROM requisicao_mestre_item rmi WHERE rmi.requisicao_id = rm.id) AS total_itens',
    'SELECT rm.status, COUNT(*) AS n',
    $sql
);
// Remove filtro de status do stats
$sqlStatsBase = preg_replace('/ AND rm\.status=\?/', '', $sqlStats);
$bindsStats   = array_values(array_filter($binds, function($v) use (&$binds, $status) {
    static $skip = false;
    if (!$skip && $v === $status && $status !== '') { $skip = true; return false; }
    return true;
}));

// Abordagem mais simples: contar separado
$sqlBase = 'SELECT rm.status, COUNT(*) AS n FROM requisicao_mestre rm
            JOIN usuario u2 ON u2.id = rm.mestre_id
            JOIN almoxarifado a ON a.id = rm.almoxarifado_id
            WHERE 1=1';
$bindsBase = [];
if (in_array($u['perfil'], ['mestre', 'tecnico_seguranca'])) {
    // mestre e técnico de segurança veem SOMENTE as próprias requisições
    $sqlBase .= ' AND rm.mestre_id=?'; $bindsBase[] = $u['id'];
} elseif ($u['perfil'] !== 'admin') {
    $ids = almoxarifados_permitidos_ids();
    if ($ids) { $ph = implode(',', array_fill(0,count($ids),'?')); $sqlBase .= " AND rm.almoxarifado_id IN ($ph)"; $bindsBase = array_merge($bindsBase, $ids); }
    else { $sqlBase .= ' AND 1=0'; }
}
$sqlBase .= ' GROUP BY rm.status';
$stStats = db()->prepare($sqlBase);
$stStats->execute($bindsBase);
$statsRows = $stStats->fetchAll();
$statsMap  = ['pendente' => 0, 'aprovada' => 0, 'entregue' => 0, 'recusada' => 0, 'cancelada' => 0];
foreach ($statsRows as $row) $statsMap[$row['status']] = (int)$row['n'];
$stats = [
    'pendentes'     => $statsMap['pendente'],
    'em_separacao'  => $statsMap['aprovada'],
    'entregues'     => $statsMap['entregue'],
    'total'         => array_sum($statsMap),
];

// Count paginação
$stmtC = db()->prepare(str_replace(
    'rm.*, u2.nome AS mestre_nome, a.nome  AS alm_nome, (SELECT COUNT(*) FROM requisicao_mestre_item rmi WHERE rmi.requisicao_id = rm.id) AS total_itens',
    'COUNT(*)',
    $sql
));
$stmtC->execute($binds);
$total = (int)$stmtC->fetchColumn();

$pag   = paginate($total, $page, $perPage);
$sql  .= ' ORDER BY rm.data_criacao DESC LIMIT ' . $pag['per_page'] . ' OFFSET ' . $pag['offset'];
$stmt  = db()->prepare($sql);
$stmt->execute($binds);
$requisicoes = $stmt->fetchAll();

$pageTitle  = 'Requisições de Materiais';
$activeMenu = 'req_mestre';
ob_start();
require VIEWS_PATH . '/requisicoes/mestre_index.php';
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/base.php';
