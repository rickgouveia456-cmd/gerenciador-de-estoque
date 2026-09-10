<?php
/**
 * API v1 — Almoxarifados
 * GET /api/v1/almoxarifados
 */
require_once HELPERS_PATH . '/api_auth.php';
api_autenticar();
api_metodo('GET');

$alms = db()->query(
    "SELECT a.id, a.nome, a.obra, a.cidade, a.regiao,
            COUNT(i.id) AS total_itens,
            COALESCE(SUM(CASE WHEN i.quantidade <= 0 THEN 1 ELSE 0 END),0) AS itens_zerados,
            COALESCE(SUM(CASE WHEN i.quantidade > 0 AND i.quantidade <= i.estoque_minimo THEN 1 ELSE 0 END),0) AS itens_alerta
     FROM almoxarifado a
     LEFT JOIN item i ON i.almoxarifado_id = a.id AND i.ativo = 1
     GROUP BY a.id ORDER BY a.cidade, a.nome"
)->fetchAll();

api_resposta([
    'total' => count($alms),
    'almoxarifados' => array_map(fn($a) => [
        'id'            => (int)$a['id'],
        'nome'          => $a['nome'],
        'obra'          => $a['obra'],
        'cidade'        => $a['cidade'],
        'regiao'        => $a['regiao'],
        'total_itens'   => (int)$a['total_itens'],
        'itens_zerados' => (int)$a['itens_zerados'],
        'itens_alerta'  => (int)$a['itens_alerta'],
    ], $alms),
]);
