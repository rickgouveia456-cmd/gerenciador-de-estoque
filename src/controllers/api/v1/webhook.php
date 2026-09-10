<?php
/**
 * API v1 — Webhook receptor (Sienge → Logi-Prime)
 *
 * O Sienge envia eventos para esta URL quando algo acontece no ERP.
 * Configure no painel do Sienge: POST https://seu-servidor/api/v1/webhook
 *
 * EVENTOS SUPORTADOS:
 *   material.updated    — material atualizado no Sienge → atualiza catálogo
 *   material.created    — novo material no Sienge → cria no catálogo
 *   purchase.approved   — compra aprovada → pode gerar entrada no estoque
 *   stock.updated       — saldo atualizado no Sienge → pode sincronizar
 *
 * SEGURANÇA:
 *   O Sienge deve enviar o header X-Sienge-Signature com HMAC do body
 *   OU usar o Bearer token padrão da API.
 */
require_once HELPERS_PATH . '/api_auth.php';

// Aceita autenticação via Bearer token OU via assinatura HMAC
$assinatura = $_SERVER['HTTP_X_SIENGE_SIGNATURE'] ?? '';
if (!$assinatura) {
    // Tenta autenticação padrão Bearer
    api_autenticar();
} else {
    // SEGURANÇA: valida HMAC — ajustar conforme doc do Sienge
    $cfg    = function_exists('sienge_config') ? sienge_config() : [];
    $secret = $cfg['webhook_secret'] ?? env('SIENGE_WEBHOOK_SECRET', '');
    if ($secret) {
        $body     = file_get_contents('php://input');
        $esperado = hash_hmac('sha256', $body, $secret);
        if (!hash_equals($esperado, $assinatura)) {
            api_erro(401, 'Assinatura do webhook inválida.');
        }
    }
}

api_metodo('POST');
header('Content-Type: application/json');

$body  = api_body();
$event = trim($body['event'] ?? $body['type'] ?? $body['evento'] ?? '');
$data  = $body['data']    ?? $body['payload'] ?? [];

// Registra o webhook recebido
_sienge_log("/webhook/$event", 'POST', 'pendente', $body, [], null);

// ── Processa o evento ─────────────────────────────────────────────────────────
switch ($event) {

    case 'material.updated':
    case 'material.created':
        // Atualiza o catálogo quando um material muda no Sienge
        // CAMPOS: ajustar conforme estrutura real do evento
        $codigo  = $data['code']        ?? $data['codigo']   ?? null;
        $nome    = $data['description'] ?? $data['nome']     ?? null;
        $unidade = $data['unit']        ?? $data['unidade']  ?? 'un';
        $valor   = $data['price']       ?? $data['valor']    ?? null;

        if ($codigo && $nome) {
            $stmt = db()->prepare("SELECT id FROM catalogo_insumo WHERE codigo_ref = ?");
            $stmt->execute([$codigo]);
            $existente = $stmt->fetch();

            if ($existente) {
                db()->prepare("UPDATE catalogo_insumo SET nome=?, unidade=?, valor_unitario=? WHERE id=?")
                    ->execute([$nome, $unidade, $valor, $existente['id']]);
            } else {
                db()->prepare("INSERT INTO catalogo_insumo (nome, codigo_ref, unidade, valor_unitario, criado_por) VALUES (?,?,?,?,'sienge_webhook')")
                    ->execute([$nome, $codigo, $unidade, $valor]);
            }
            _sienge_log("/webhook/$event", 'POST', 'sucesso', $body, ['acao' => 'catalogo_atualizado', 'codigo' => $codigo], null);
            api_resposta(['ok' => true, 'acao' => 'catalogo_atualizado', 'codigo' => $codigo]);
        }
        api_resposta(['ok' => true, 'acao' => 'ignorado', 'motivo' => 'codigo ou nome ausente']);

    case 'purchase.approved':
        // Compra aprovada no Sienge — pode gerar entrada de estoque
        // CAMPOS: ajustar conforme estrutura real do evento
        $protocolo = $data['reference'] ?? $data['protocolo'] ?? null;
        // Por segurança, apenas loga — não altera estoque automaticamente
        // Implementar quando fluxo for validado com o TI do Sienge
        _sienge_log("/webhook/purchase.approved", 'POST', 'sucesso', $body,
            ['acao' => 'recebido', 'protocolo' => $protocolo], null);
        api_resposta(['ok' => true, 'acao' => 'recebido', 'nota' => 'entrada_pendente_validacao']);

    case 'stock.updated':
        // Saldo atualizado no Sienge
        // CAMPOS: ajustar conforme estrutura real
        $codigo     = $data['code']    ?? $data['codigo']    ?? null;
        $quantidade = $data['balance'] ?? $data['quantidade'] ?? null;
        $almId      = $data['warehouse_id'] ?? null;

        if ($codigo && $quantidade !== null) {
            $stmt = db()->prepare("SELECT id FROM item WHERE codigo = ? AND ativo = 1" . ($almId ? " AND almoxarifado_id = ?" : ""));
            $params = [$codigo];
            if ($almId) $params[] = $almId;
            $stmt->execute($params);
            $item = $stmt->fetch();

            if ($item) {
                db()->prepare("UPDATE item SET quantidade = ? WHERE id = ?")
                    ->execute([$quantidade, $item['id']]);
                _sienge_log("/webhook/stock.updated", 'POST', 'sucesso', $body,
                    ['item_id' => $item['id'], 'nova_quantidade' => $quantidade], null);
                api_resposta(['ok' => true, 'acao' => 'estoque_atualizado', 'item_id' => $item['id']]);
            }
        }
        api_resposta(['ok' => true, 'acao' => 'ignorado', 'motivo' => 'item_nao_encontrado']);

    default:
        // Evento desconhecido — loga e confirma recebimento (não retornar erro para o Sienge)
        _sienge_log("/webhook/desconhecido", 'POST', 'sucesso', $body,
            ['evento' => $event, 'acao' => 'ignorado'], null);
        api_resposta(['ok' => true, 'acao' => 'ignorado', 'evento' => $event]);
}
