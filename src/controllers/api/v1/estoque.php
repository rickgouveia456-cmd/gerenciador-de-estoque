<?php
/**
 * API v1 — Estoque
 *
 * GET  /api/v1/estoque              — lista todos os itens
 * GET  /api/v1/estoque?alm=ID       — filtra por almoxarifado
 * GET  /api/v1/estoque?codigo=COD   — busca por código
 */
require_once HELPERS_PATH . '/api_auth.php';
api_autenticar();
api_metodo('GET');

$almId  = (int)($_GET['alm']    ?? 0);
$codigo = trim($_GET['codigo']  ?? '');
$nome   = trim($_GET['nome']    ?? '');

$sql    = "SELECT i.id, i.codigo, i.nome, i.unidade, i.quantidade,
                  i.estoque_minimo, i.categoria, i.ca, i.valor_unitario, i.ativo,
                  a.id AS almoxarifado_id, a.nome AS almoxarifado_nome, a.obra, a.cidade
           FROM item i
           JOIN almoxarifado a ON a.id = i.almoxarifado_id
           WHERE i.ativo = 1";
$params = [];

if ($almId)  { $sql .= " AND i.almoxarifado_id = ?"; $params[] = $almId; }
if ($codigo) { $sql .= " AND i.codigo LIKE ?";        $params[] = "%$codigo%"; }
if ($nome)   { $sql .= " AND i.nome LIKE ?";          $params[] = "%$nome%"; }

$sql .= " ORDER BY a.nome, i.nome LIMIT 500";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$itens = $stmt->fetchAll();

api_resposta([
    'total' => count($itens),
    'itens' => array_map(fn($i) => [
        'id'               => (int)$i['id'],
        'codigo'           => $i['codigo'],
        'nome'             => $i['nome'],
        'unidade'          => $i['unidade'],
        'quantidade'       => (float)$i['quantidade'],
        'estoque_minimo'   => (float)$i['estoque_minimo'],
        'categoria'        => $i['categoria'],
        'ca'               => $i['ca'],
        'valor_unitario'   => $i['valor_unitario'] ? (float)$i['valor_unitario'] : null,
        'almoxarifado'     => [
            'id'     => (int)$i['almoxarifado_id'],
            'nome'   => $i['almoxarifado_nome'],
            'obra'   => $i['obra'],
            'cidade' => $i['cidade'],
        ],
        'status' => (float)$i['quantidade'] <= 0 ? 'critico'
                  : ((float)$i['quantidade'] <= (float)$i['estoque_minimo'] ? 'alerta' : 'ok'),
    ], $itens),
]);
