<?php
requer_login();
$id = (int)($params['id'] ?? 0);
$u  = usuario_atual();

if (in_array($u['perfil'], ['mestre','tecnico_seguranca'])) {
    flash('Acesso restrito. Use a tela de requisicoes.', 'warning');
    redirect('/requisicoes/mestre');
}

$stmt = db()->prepare('SELECT * FROM almoxarifado WHERE id=?');
$stmt->execute([$id]);
$alm = $stmt->fetch();
if (!$alm) { http_response_code(404); require VIEWS_PATH . '/layouts/404.php'; exit; }

if (!usuario_tem_acesso_almoxarifado($id)) {
    flash('Acesso negado.', 'danger');
    redirect('/');
}

// Itens (ativos e desativados para reativacao)
$filtro    = $_GET['filtro'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$status    = $_GET['status'] ?? '';

$sql = 'SELECT * FROM item WHERE almoxarifado_id=?';
$binds = [$id];

if ($filtro) {
    $sql .= ' AND (nome LIKE ? OR codigo LIKE ?)';
    $binds[] = "%$filtro%";
    $binds[] = "%$filtro%";
}
if ($categoria) {
    $sql .= ' AND categoria=?';
    $binds[] = $categoria;
}
if ($status === 'alerta') {
    $sql .= ' AND quantidade > 0 AND quantidade <= estoque_minimo AND ativo=1';
} elseif ($status === 'critico') {
    $sql .= ' AND quantidade <= 0 AND ativo=1';
} elseif ($status === 'ok') {
    $sql .= ' AND quantidade > estoque_minimo AND ativo=1';
}

$sql .= ' ORDER BY ativo DESC, fixado DESC, nome ASC';
$stmtI = db()->prepare($sql);
$stmtI->execute($binds);
$itens = $stmtI->fetchAll();

// Valor total do estoque
$valorTotal = 0;
foreach ($itens as $it) {
    if ($it['ativo'] && $it['valor_unitario']) {
        $valorTotal += (float)$it['quantidade'] * (float)$it['valor_unitario'];
    }
}

$pageTitle  = $alm['nome'];
$activeMenu = 'almoxarifado';
$activeAlmId = $id;
ob_start();
require VIEWS_PATH . '/almoxarifado/show.php';
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/base.php';
