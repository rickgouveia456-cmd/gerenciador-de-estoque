<?php
requer_admin();
csrf_check();

// Busca todos os itens do catálogo com valor_unitario > 0
$catalogo = db()->query(
    "SELECT nome, valor_unitario FROM catalogo WHERE valor_unitario > 0"
)->fetchAll();

if (empty($catalogo)) {
    if (is_ajax()) {
        json_response(['ok' => true, 'atualizados' => 0, 'mensagem' => 'Nenhum item no catálogo com valor definido.']);
    }
    flash('Nenhum item no catálogo com valor definido.', 'warning');
    redirect('/admin');
}

$totalAtualizados = 0;

$stUpd = db()->prepare(
    "UPDATE item SET valor_unitario = ? WHERE LOWER(nome) = LOWER(?)"
);

foreach ($catalogo as $cat) {
    $stUpd->execute([(float)$cat['valor_unitario'], $cat['nome']]);
    $totalAtualizados += $stUpd->rowCount();
}

// Retorna JSON se for chamada AJAX
if (is_ajax()) {
    json_response([
        'ok'          => true,
        'atualizados' => $totalAtualizados,
        'mensagem'    => "$totalAtualizados item(s) atualizado(s) com sucesso.",
    ]);
}

flash("$totalAtualizados item(s) atualizado(s) com o valor do catálogo.", 'success');
redirect('/admin');
