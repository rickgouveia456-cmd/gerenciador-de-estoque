<?php
requer_admin();
csrf_check();

$origemId  = (int)($_POST['origem_id'] ?? 0);
$destinoId = (int)($_POST['destino_id'] ?? 0);
$itemIds   = $_POST['item_ids'] ?? [];

if (!$origemId || !$destinoId || empty($itemIds)) {
    flash('Selecione o destino e pelo menos um item.', 'warning');
    redirect("/almoxarifado/$origemId");
}
if ($origemId === $destinoId) {
    flash('Origem e destino nao podem ser iguais.', 'warning');
    redirect("/almoxarifado/$origemId");
}

// Verificar que destino existe
$stmtD = db()->prepare('SELECT id, nome FROM almoxarifado WHERE id=?');
$stmtD->execute([$destinoId]);
$destino = $stmtD->fetch();
if (!$destino) { flash('Almoxarifado destino nao encontrado.', 'danger'); redirect("/almoxarifado/$origemId"); }

$transferidos = 0;
$erros = [];

foreach ($itemIds as $itemId) {
    $itemId = (int)$itemId;
    $qtdReq = (float)($_POST["qtd_$itemId"] ?? 0);
    if ($qtdReq <= 0) { $erros[] = "Item #$itemId: quantidade invalida."; continue; }

    $stmtIt = db()->prepare('SELECT * FROM item WHERE id=? AND almoxarifado_id=? AND ativo=1');
    $stmtIt->execute([$itemId, $origemId]);
    $it = $stmtIt->fetch();
    if (!$it) { $erros[] = "Item #$itemId nao encontrado na origem."; continue; }
    if ($qtdReq > (float)$it['quantidade']) {
        $erros[] = "'{$it['nome']}': estoque insuficiente ({$it['quantidade']} disponivel).";
        continue;
    }

    // Verificar se existe item igual (mesmo codigo) no destino
    $stmtEx = db()->prepare('SELECT * FROM item WHERE codigo=? AND almoxarifado_id=? AND ativo=1');
    $stmtEx->execute([$it['codigo'], $destinoId]);
    $existing = $stmtEx->fetch();

    if ($existing) {
        // Somar quantidade no destino
        db()->prepare('UPDATE item SET quantidade=quantidade+? WHERE id=?')
            ->execute([$qtdReq, $existing['id']]);
    } else {
        // Criar novo item no destino
        $stmtNew = db()->prepare(
            'INSERT INTO item (nome,codigo,unidade,quantidade,estoque_minimo,almoxarifado_id,categoria,ca,valor_unitario)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $stmtNew->execute([
            $it['nome'], $it['codigo'], $it['unidade'],
            $qtdReq, $it['estoque_minimo'],
            $destinoId, $it['categoria'], $it['ca'], $it['valor_unitario']
        ]);
    }

    // Abater do origem
    db()->prepare('UPDATE item SET quantidade=quantidade-? WHERE id=?')
        ->execute([$qtdReq, $itemId]);

    $transferidos++;
}

if ($transferidos) flash("$transferidos item(ns) transferido(s) para {$destino['nome']}.", 'success');
foreach ($erros as $e) flash($e, 'danger');

redirect("/almoxarifado/$origemId");
