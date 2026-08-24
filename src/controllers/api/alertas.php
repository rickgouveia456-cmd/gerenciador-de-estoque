<?php
requer_login();
$u  = usuario_atual();
$ids = almoxarifados_permitidos_ids();

if (!$ids) { json_response(['alertas' => []]); }

$ph    = implode(',', array_fill(0, count($ids), '?'));
$stmt  = db()->prepare(
    "SELECT i.id, i.nome, i.quantidade, i.estoque_minimo, i.unidade, a.nome AS alm_nome
     FROM item i
     JOIN almoxarifado a ON a.id = i.almoxarifado_id
     WHERE i.quantidade <= i.estoque_minimo AND i.ativo=1 AND i.almoxarifado_id IN ($ph)
     ORDER BY i.quantidade ASC
     LIMIT 50"
);
$stmt->execute($ids);
$rows = $stmt->fetchAll();

$alertas = array_map(function($r) {
    return [
        'id'        => $r['id'],
        'nome'      => $r['nome'],
        'alm'       => $r['alm_nome'],
        'qtd'       => (float)$r['quantidade'],
        'minimo'    => (float)$r['estoque_minimo'],
        'unidade'   => $r['unidade'],
        'status'    => $r['quantidade'] <= 0 ? 'critico' : 'alerta',
    ];
}, $rows);

json_response(['alertas' => $alertas, 'total' => count($alertas)]);
