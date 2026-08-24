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


// Right panel: Ferramentas e EPIs do almoxarifado
$ferramentasAlm = db()->prepare(
    "SELECT * FROM ferramenta WHERE almoxarifado_id=? AND ativo=1 ORDER BY status ASC, nome ASC LIMIT 20"
);
$ferramentasAlm->execute([$id]);
$ferramentasAlm = $ferramentasAlm->fetchAll();

$episAlm = db()->prepare(
    "SELECT * FROM item_epi WHERE almoxarifado_id=? AND ativo=1 ORDER BY status ASC, nome ASC LIMIT 20"
);
$episAlm->execute([$id]);
$episAlm = $episAlm->fetchAll();

$kitsAlm = db()->prepare(
    "SELECT k.*,COUNT(ki.id) AS total_itens FROM kit k
     LEFT JOIN kit_item ki ON ki.kit_id=k.id
     WHERE k.ativo=1
     GROUP BY k.id ORDER BY k.nome LIMIT 10"
);
$kitsAlm->execute();
$kitsAlm = $kitsAlm->fetchAll();
$pageTitle  = $alm['nome'];
$activeMenu = 'almoxarifado';
$activeAlmId = $id;
ob_start();
require VIEWS_PATH . '/almoxarifado/show.php';
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/base.php';
