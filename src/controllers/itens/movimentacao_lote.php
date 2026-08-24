<?php
requer_login();
$u = usuario_atual();
if ($u['perfil'] === 'analista') { flash('Analists nao podem registrar movimentacoes.','danger'); redirect('/'); }

$ids = almoxarifados_permitidos_ids();
if ($u['perfil'] === 'admin') {
    $almoxarifados = db()->query('SELECT * FROM almoxarifado ORDER BY nome')->fetchAll();
} else {
    if ($ids) {
        $ph = implode(',', array_fill(0,count($ids),'?'));
        $stmt = db()->prepare("SELECT * FROM almoxarifado WHERE id IN ($ph) ORDER BY nome");
        $stmt->execute($ids);
        $almoxarifados = $stmt->fetchAll();
    } else { $almoxarifados = []; }
}

// Itens por almoxarifado (para JS)
$itensJson = [];
foreach ($almoxarifados as $alm) {
    $st = db()->prepare('SELECT id, nome, quantidade, unidade, categoria, ca FROM item WHERE almoxarifado_id=? AND ativo=1 ORDER BY nome');
    $st->execute([$alm['id']]);
    $itensJson[$alm['id']] = $st->fetchAll();
}

// Historico recente
$idsStr = $ids ? implode(',', array_map('intval', $ids)) : '0';
$historico = db()->query(
    "SELECT m.*, i.nome AS item_nome, i.unidade, a.nome AS alm_nome
     FROM movimentacao m JOIN item i ON i.id=m.item_id JOIN almoxarifado a ON a.id=i.almoxarifado_id
     WHERE i.almoxarifado_id IN ($idsStr)
     ORDER BY m.data DESC LIMIT 20"
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $almId      = (int)($_POST['almoxarifado_id'] ?? 0);
    $tipo       = $_POST['tipo'] ?? 'saida';
    $responsavel = trim($_POST['responsavel'] ?? '');
    $observacao  = trim($_POST['observacao'] ?? '');

    if (!usuario_tem_acesso_almoxarifado($almId)) { flash('Acesso negado.','danger'); redirect('/movimentacao/lote'); }

    $indices = [];
    foreach ($_POST as $k => $_) {
        if (preg_match('/^item_id_(\d+)$/', $k, $m)) $indices[] = (int)$m[1];
    }
    sort($indices);

    $movs = []; $erros = [];
    foreach ($indices as $i) {
        $itemId  = (int)($_POST["item_id_$i"] ?? 0);
        $qtdStr  = $_POST["quantidade_$i"] ?? '';
        $colab   = trim($_POST["colaborador_$i"] ?? '');
        $respL   = trim($_POST["responsavel_$i"] ?? '') ?: $responsavel;
        if (!$itemId || $qtdStr === '') continue;

        $stmtIt = db()->prepare('SELECT * FROM item WHERE id=? AND ativo=1');
        $stmtIt->execute([$itemId]);
        $it = $stmtIt->fetch();
        $qtd = (float)$qtdStr;
        if (!$it || $qtd <= 0) continue;

        if (in_array($tipo, ['saida']) && $qtd > (float)$it['quantidade']) {
            $erros[] = "\"{$it['nome']}\": estoque insuficiente ({$it['quantidade']} {$it['unidade']})";
            continue;
        }

        $tipoReal = in_array($tipo, ['devolucao_epi','devolucao_ferramenta']) ? 'entrada' : $tipo;
        $novaQtd  = $tipoReal === 'entrada'
            ? round((float)$it['quantidade'] + $qtd, 4)
            : round((float)$it['quantidade'] - $qtd, 4);

        db()->prepare('UPDATE item SET quantidade=? WHERE id=?')->execute([$novaQtd, $itemId]);

        $obs = $observacao;
        if ($tipo === 'saida' && $colab)                  $obs = "liberado P/ $colab" . ($observacao ? " | $observacao" : '');
        if ($tipo === 'devolucao_epi')                    $obs = "Devolucao EPI — $colab" . ($observacao ? " | $observacao" : '');
        if ($tipo === 'devolucao_ferramenta')             $obs = "Devolucao Ferramenta — $colab" . ($observacao ? " | $observacao" : '');

        $stmtM = db()->prepare('INSERT INTO movimentacao (tipo,quantidade,responsavel,observacao,item_id) VALUES (?,?,?,?,?)');
        $stmtM->execute([$tipoReal, $qtd, $respL, $obs, $itemId]);
        $movs[] = $itemId;
    }

    if ($movs) flash(count($movs) . ' item(ns) movimentado(s).', 'success');
    elseif (empty($erros)) flash('Adicione pelo menos um item.', 'warning');
    foreach ($erros as $e) flash("Estoque insuficiente: $e", 'danger');
    redirect('/movimentacao/lote');
}

$pageTitle  = 'Movimentação';
$activeMenu = 'movimentacao';
ob_start(); require VIEWS_PATH . '/itens/movimentacao_lote.php';
$content = ob_get_clean(); require VIEWS_PATH . '/layouts/base.php';
