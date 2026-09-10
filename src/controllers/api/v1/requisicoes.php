<?php
/**
 * API v1 — Requisições
 *
 * GET  /api/v1/requisicoes              — lista requisições
 * GET  /api/v1/requisicoes?status=pendente
 * POST /api/v1/requisicoes              — cria nova requisição
 */
require_once HELPERS_PATH . '/api_auth.php';
api_autenticar();
api_metodo('GET', 'POST');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body       = api_body();
    $almId      = (int)($body['almoxarifado_id'] ?? 0);
    $colaborador= trim($body['colaborador'] ?? '');
    $observacao = trim($body['observacao']  ?? '');
    $itens      = $body['itens']            ?? [];

    if (!$almId)        api_erro(400, 'almoxarifado_id é obrigatório.');
    if (!$colaborador)  api_erro(400, 'colaborador é obrigatório.');
    if (empty($itens))  api_erro(400, 'itens é obrigatório e não pode ser vazio.');

    // Cria a requisição
    $proto = 'REQ-' . date('Ymd') . '-API';
    db()->prepare(
        "INSERT INTO requisicao_mestre (mestre_id, colaborador, almoxarifado_id, observacao, status, protocolo, data_criacao)
         VALUES (1, ?, ?, ?, 'pendente', ?, NOW())"
    )->execute([$colaborador, $almId, $observacao ?: null, $proto]);
    $reqId = (int)db()->lastInsertId();

    // Atualiza protocolo com ID
    $proto = 'REQ-' . date('Ymd') . '-' . str_pad($reqId, 4, '0', STR_PAD_LEFT);
    db()->prepare("UPDATE requisicao_mestre SET protocolo = ? WHERE id = ?")->execute([$proto, $reqId]);

    // Insere itens
    foreach ($itens as $it) {
        $itemId = (int)($it['item_id'] ?? 0);
        $qtd    = (float)($it['quantidade'] ?? 0);
        if (!$itemId || $qtd <= 0) continue;
        db()->prepare(
            "INSERT INTO requisicao_mestre_item (requisicao_id, item_id, quantidade) VALUES (?,?,?)"
        )->execute([$reqId, $itemId, $qtd]);
    }

    api_resposta(['ok' => true, 'requisicao_id' => $reqId, 'protocolo' => $proto], 201);
}

// GET
$status = trim($_GET['status'] ?? '');
$almId  = (int)($_GET['alm']   ?? 0);

$sql = "SELECT r.id, r.protocolo, r.colaborador, r.observacao, r.status,
               r.data_criacao, r.data_entrega,
               a.nome AS alm_nome
        FROM requisicao_mestre r
        JOIN almoxarifado a ON a.id = r.almoxarifado_id
        WHERE 1=1";
$params = [];
if ($status) { $sql .= " AND r.status = ?"; $params[] = $status; }
if ($almId)  { $sql .= " AND r.almoxarifado_id = ?"; $params[] = $almId; }
$sql .= " ORDER BY r.data_criacao DESC LIMIT 200";

$stmt = db()->prepare($sql); $stmt->execute($params);
$reqs = $stmt->fetchAll();

api_resposta([
    'total'      => count($reqs),
    'requisicoes'=> array_map(fn($r) => [
        'id'           => (int)$r['id'],
        'protocolo'    => $r['protocolo'],
        'colaborador'  => $r['colaborador'],
        'observacao'   => $r['observacao'],
        'status'       => $r['status'],
        'data_criacao' => $r['data_criacao'],
        'data_entrega' => $r['data_entrega'],
        'almoxarifado' => $r['alm_nome'],
    ], $reqs),
]);
