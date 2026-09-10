<?php
/**
 * API v1 — Movimentações
 *
 * GET  /api/v1/movimentacoes             — lista movimentações recentes
 * GET  /api/v1/movimentacoes?alm=ID      — filtra por almoxarifado
 * GET  /api/v1/movimentacoes?tipo=saida  — filtra por tipo
 * GET  /api/v1/movimentacoes?de=YYYY-MM-DD&ate=YYYY-MM-DD
 * POST /api/v1/movimentacoes             — registra nova movimentação
 */
require_once HELPERS_PATH . '/api_auth.php';
api_autenticar();
api_metodo('GET', 'POST');

// ── POST: registrar movimentação ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = api_body();

    $itemId     = (int)($body['item_id']     ?? 0);
    $tipo       = $body['tipo']              ?? '';
    $quantidade = (float)($body['quantidade'] ?? 0);
    $responsavel= trim($body['responsavel']  ?? '');
    $observacao = trim($body['observacao']   ?? '');

    if (!$itemId)              api_erro(400, 'item_id é obrigatório.');
    if (!in_array($tipo, ['entrada','saida'])) api_erro(400, 'tipo deve ser "entrada" ou "saida".');
    if ($quantidade <= 0)      api_erro(400, 'quantidade deve ser maior que zero.');

    // Busca o item
    $stmt = db()->prepare("SELECT * FROM item WHERE id = ? AND ativo = 1");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    if (!$item) api_erro(404, "Item ID $itemId não encontrado.");

    // Valida estoque na saída
    if ($tipo === 'saida' && (float)$item['quantidade'] < $quantidade) {
        api_erro(409, "Estoque insuficiente. Disponível: {$item['quantidade']} {$item['unidade']}.");
    }

    // Atualiza estoque
    $novaQtd = $tipo === 'entrada'
        ? (float)$item['quantidade'] + $quantidade
        : (float)$item['quantidade'] - $quantidade;

    db()->prepare("UPDATE item SET quantidade = ? WHERE id = ?")->execute([$novaQtd, $itemId]);

    // Registra movimentação
    db()->prepare(
        "INSERT INTO movimentacao (tipo, quantidade, responsavel, observacao, data, item_id)
         VALUES (?, ?, ?, ?, NOW(), ?)"
    )->execute([$tipo, $quantidade, $responsavel ?: 'API', $observacao ?: null, $itemId]);

    $movId = (int)db()->lastInsertId();

    api_resposta([
        'ok'           => true,
        'movimentacao_id' => $movId,
        'item_id'      => $itemId,
        'tipo'         => $tipo,
        'quantidade'   => $quantidade,
        'estoque_novo' => $novaQtd,
        'unidade'      => $item['unidade'],
    ], 201);
}

// ── GET: listar movimentações ─────────────────────────────────────────────
$almId = (int)($_GET['alm']  ?? 0);
$tipo  = trim($_GET['tipo']  ?? '');
$de    = trim($_GET['de']    ?? (new DateTime())->modify('-30 days')->format('Y-m-d'));
$ate   = trim($_GET['ate']   ?? date('Y-m-d'));

$sql = "SELECT m.id, m.tipo, m.quantidade, m.responsavel, m.observacao, m.data, m.devolvido,
               i.id AS item_id, i.nome AS item_nome, i.codigo, i.unidade,
               a.id AS alm_id, a.nome AS alm_nome
        FROM movimentacao m
        JOIN item i ON i.id = m.item_id
        JOIN almoxarifado a ON a.id = i.almoxarifado_id
        WHERE m.data >= ? AND m.data <= ?";
$params = [$de . ' 00:00:00', $ate . ' 23:59:59'];

if ($almId) { $sql .= " AND i.almoxarifado_id = ?"; $params[] = $almId; }
if ($tipo)  { $sql .= " AND m.tipo = ?";             $params[] = $tipo; }

$sql .= " ORDER BY m.data DESC LIMIT 1000";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$movs = $stmt->fetchAll();

api_resposta([
    'total'         => count($movs),
    'periodo'       => ['de' => $de, 'ate' => $ate],
    'movimentacoes' => array_map(fn($m) => [
        'id'          => (int)$m['id'],
        'tipo'        => $m['tipo'],
        'quantidade'  => (float)$m['quantidade'],
        'responsavel' => $m['responsavel'],
        'observacao'  => $m['observacao'],
        'data'        => $m['data'],
        'devolvido'   => (bool)$m['devolvido'],
        'item'        => [
            'id'     => (int)$m['item_id'],
            'codigo' => $m['codigo'],
            'nome'   => $m['item_nome'],
            'unidade'=> $m['unidade'],
        ],
        'almoxarifado' => [
            'id'   => (int)$m['alm_id'],
            'nome' => $m['alm_nome'],
        ],
    ], $movs),
]);
