<?php
/**
 * Logi-Prime — Helper de Integração com Sienge
 *
 * FLUXOS SUPORTADOS:
 *   Logi-Prime → Sienge: enviar requisições aprovadas, enviar saldo de estoque
 *   Sienge → Logi-Prime: buscar materiais, importar saldo, receber webhooks
 *   Bidirecional: sincronizar catálogo de insumos
 *
 * PARA ATIVAR:
 *   1. Preencha no painel Admin → Integrações (base_url, token, empresa_id)
 *      OU defina no .env: SIENGE_BASE_URL, SIENGE_TOKEN, SIENGE_EMPRESA_ID
 *   2. Ative o toggle "Integração Sienge ativa"
 *   3. Confirme os endpoints na doc: https://developers.sienge.com.br
 *
 * CAMPOS COM // ENDPOINT: precisam ser confirmados na documentação do Sienge
 * antes de ativar em produção. O resto está pronto.
 */

// ── Configuração ──────────────────────────────────────────────────────────────
function sienge_config(): array {
    static $cfg = null;
    if ($cfg !== null) return $cfg;

    // Prioridade 1: variáveis de ambiente (.env)
    $base_url   = env('SIENGE_BASE_URL', '');
    $token      = env('SIENGE_TOKEN', '');
    $empresa_id = env('SIENGE_EMPRESA_ID', '');
    $ativo_env  = env('SIENGE_ATIVO', 'false') === 'true';

    // Prioridade 2: banco de dados (painel de configuração)
    try {
        $stmt = db()->prepare("SELECT config_json, ativo FROM integracao_config WHERE sistema = 'sienge'");
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row) {
            $banco = json_decode($row['config_json'] ?? '{}', true) ?: [];
            // .env tem prioridade sobre o banco
            $cfg = [
                'base_url'         => $base_url   ?: ($banco['base_url']   ?? ''),
                'token'            => $token       ?: ($banco['token']      ?? ''),
                'empresa_id'       => $empresa_id  ?: ($banco['empresa_id'] ?? ''),
                'ativo'            => $ativo_env   || (bool)$row['ativo'],
                'sync_materiais'   => $banco['sync_materiais']   ?? false,
                'sync_requisicoes' => $banco['sync_requisicoes'] ?? false,
                'sync_estoques'    => $banco['sync_estoques']    ?? false,
            ];
        } else {
            $cfg = [
                'base_url' => $base_url, 'token' => $token,
                'empresa_id' => $empresa_id, 'ativo' => $ativo_env,
                'sync_materiais' => false, 'sync_requisicoes' => false, 'sync_estoques' => false,
            ];
        }
    } catch (Throwable $e) {
        $cfg = ['base_url' => $base_url, 'token' => $token, 'empresa_id' => $empresa_id, 'ativo' => $ativo_env];
    }

    return $cfg;
}

function sienge_ativo(): bool {
    $cfg = sienge_config();
    return !empty($cfg['ativo'])
        && !empty($cfg['base_url'])
        && !empty($cfg['token']);
}

// ── Cliente HTTP ──────────────────────────────────────────────────────────────
function sienge_request(string $method, string $endpoint, array $body = [], array $query = []): array {
    $cfg = sienge_config();

    if (!sienge_ativo()) {
        return ['ok' => false, 'status' => 0, 'data' => [],
                'error' => 'Integração Sienge não configurada. Preencha Admin → Integrações.'];
    }

    $url = rtrim($cfg['base_url'], '/') . '/' . ltrim($endpoint, '/');
    if ($query) $url .= '?' . http_build_query($query);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $cfg['token'],
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Sienge-Empresa: ' . ($cfg['empresa_id'] ?? ''),
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
    ]);

    if (!empty($body) && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }

    $resposta  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $dados = $resposta ? json_decode($resposta, true) : [];
    $ok    = $httpCode >= 200 && $httpCode < 300;

    _sienge_log($endpoint, $method, $ok ? 'sucesso' : 'erro', $body, $dados,
        $curlError ?: ($ok ? null : ($dados['message'] ?? "HTTP $httpCode")));

    return [
        'ok'    => $ok,
        'status'=> $httpCode,
        'data'  => $dados ?: [],
        'error' => $ok ? null : ($curlError ?: ($dados['message'] ?? "Erro HTTP $httpCode")),
    ];
}

// ── Log ───────────────────────────────────────────────────────────────────────
function _sienge_log(string $operacao, string $metodo, string $status, array $payload, $resposta, ?string $erro): void {
    try {
        $u = function_exists('usuario_atual') ? usuario_atual() : null;
        db()->prepare("
            INSERT INTO integracao_log (sistema, operacao, status, payload, resposta, erro_msg, concluido_em, usuario_id)
            VALUES ('sienge', ?, ?, ?, ?, ?, NOW(), ?)
        ")->execute([
            "$metodo $operacao", $status,
            json_encode($payload,  JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
            json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
            $erro, $u['id'] ?? null,
        ]);
    } catch (Throwable $e) { /* log não pode quebrar a operação */ }
}

// ══════════════════════════════════════════════════════════════════════════════
//  SIENGE → LOGI-PRIME  (Logi-Prime consome a API do Sienge)
// ══════════════════════════════════════════════════════════════════════════════

// ── Conexão ───────────────────────────────────────────────────────────────────
function sienge_testar_conexao(): array {
    // ENDPOINT: confirmar na doc do Sienge qual rota retorna status 200
    // Candidatos comuns: /v1/health | /api/v1 | /v1/ping | /v1/status
    $r = sienge_request('GET', '/v1/health');
    return [
        'ok'    => $r['ok'],
        'msg'   => $r['ok'] ? 'Conexão estabelecida!' : 'Falha: ' . $r['error'],
        'status'=> $r['status'],
    ];
}

// ── Materiais / Catálogo ──────────────────────────────────────────────────────
function sienge_buscar_materiais(int $pagina = 1, int $por_pagina = 100): array {
    // ENDPOINT: confirmar na doc — candidatos: /v1/materials | /v1/insumos | /api/materials
    // PAGINAÇÃO: confirmar params page/pageSize ou offset/limit
    return sienge_request('GET', '/v1/materials', [], [
        'page'     => $pagina,
        'pageSize' => $por_pagina,
    ]);
}

function sienge_sincronizar_materiais(): array {
    if (!sienge_ativo()) return ['ok' => false, 'msg' => 'Sienge não configurado.', 'total' => 0];

    $pagina = 1; $inseridos = 0; $atualizados = 0; $erros = 0;

    do {
        $r = sienge_buscar_materiais($pagina, 100);
        if (!$r['ok']) return ['ok' => false, 'msg' => $r['error'], 'total' => 0];

        // MAPEAMENTO: ajustar conforme resposta real do Sienge
        // Estrutura esperada: { items: [...], totalPages: N } ou array direto
        $materiais    = $r['data']['items'] ?? $r['data']['data'] ?? $r['data'] ?? [];
        $totalPaginas = $r['data']['totalPages'] ?? $r['data']['last_page'] ?? 1;

        foreach ($materiais as $mat) {
            try {
                // CAMPOS: ajustar chaves conforme resposta do Sienge
                $codigo  = $mat['code']        ?? $mat['codigo']     ?? $mat['id_material'] ?? null;
                $nome    = $mat['description'] ?? $mat['nome']       ?? $mat['descricao']   ?? null;
                $unidade = $mat['unit']        ?? $mat['unidade']    ?? $mat['un']          ?? 'un';
                $valor   = $mat['price']       ?? $mat['valor']      ?? $mat['preco']       ?? null;

                if (!$codigo || !$nome) continue;

                $stmt = db()->prepare("SELECT id FROM catalogo_insumo WHERE codigo_ref = ?");
                $stmt->execute([$codigo]);
                $existente = $stmt->fetch();

                if ($existente) {
                    db()->prepare("UPDATE catalogo_insumo SET nome=?, unidade=?, valor_unitario=? WHERE id=?")
                        ->execute([$nome, $unidade, $valor, $existente['id']]);
                    $atualizados++;
                } else {
                    db()->prepare("INSERT INTO catalogo_insumo (nome, codigo_ref, unidade, valor_unitario, criado_por) VALUES (?,?,?,?,'sienge')")
                        ->execute([$nome, $codigo, $unidade, $valor]);
                    $inseridos++;
                }
            } catch (Throwable $e) { $erros++; }
        }
        $pagina++;
    } while ($pagina <= $totalPaginas);

    return [
        'ok'          => true,
        'inseridos'   => $inseridos,
        'atualizados' => $atualizados,
        'erros'       => $erros,
        'total'       => $inseridos + $atualizados,
        'msg'         => "Sync concluída: $inseridos inseridos, $atualizados atualizados, $erros erros.",
    ];
}

// ── Estoque ───────────────────────────────────────────────────────────────────
function sienge_enviar_estoque(int $almoxarifado_id): array {
    if (!sienge_ativo()) return ['ok' => false, 'msg' => 'Sienge não configurado.'];

    // Busca todos os itens ativos do almoxarifado
    $stmt = db()->prepare(
        "SELECT i.codigo, i.nome, i.unidade, i.quantidade, i.estoque_minimo,
                a.nome AS alm_nome, a.obra
         FROM item i JOIN almoxarifado a ON a.id = i.almoxarifado_id
         WHERE i.almoxarifado_id = ? AND i.ativo = 1"
    );
    $stmt->execute([$almoxarifado_id]);
    $itens = $stmt->fetchAll();

    if (empty($itens)) return ['ok' => false, 'msg' => 'Nenhum item encontrado.'];

    // ENDPOINT: confirmar na doc do Sienge
    // Candidatos: /v1/stock | /v1/inventory | /v1/estoque | /v1/warehouse/stock
    $payload = [
        'almoxarifado_id' => $almoxarifado_id,
        'obra'            => $itens[0]['obra'] ?? '',
        'data_referencia' => date('Y-m-d'),
        'itens'           => array_map(fn($i) => [
            'codigo'         => $i['codigo'],
            'descricao'      => $i['nome'],
            'unidade'        => $i['unidade'],
            'saldo'          => (float)$i['quantidade'],
            'estoque_minimo' => (float)$i['estoque_minimo'],
        ], $itens),
    ];

    $r = sienge_request('POST', '/v1/stock/update', $payload);
    return [
        'ok'    => $r['ok'],
        'msg'   => $r['ok'] ? count($itens) . ' itens enviados ao Sienge.' : 'Erro: ' . $r['error'],
        'total' => count($itens),
    ];
}

function sienge_importar_estoque(int $almoxarifado_id): array {
    if (!sienge_ativo()) return ['ok' => false, 'msg' => 'Sienge não configurado.'];

    // ENDPOINT: confirmar na doc do Sienge
    $r = sienge_request('GET', '/v1/stock', [], ['almoxarifado_id' => $almoxarifado_id]);
    if (!$r['ok']) return ['ok' => false, 'msg' => 'Erro ao buscar estoque: ' . $r['error']];

    // MAPEAMENTO: ajustar conforme resposta real do Sienge
    $itens       = $r['data']['items'] ?? $r['data'] ?? [];
    $atualizados = 0; $nao_encontrados = 0;

    foreach ($itens as $it) {
        $codigo    = $it['code']    ?? $it['codigo']    ?? null;
        $quantidade= $it['balance'] ?? $it['saldo']     ?? $it['quantidade'] ?? null;

        if (!$codigo || $quantidade === null) continue;

        $stmt = db()->prepare("SELECT id FROM item WHERE codigo = ? AND almoxarifado_id = ? AND ativo = 1");
        $stmt->execute([$codigo, $almoxarifado_id]);
        $item = $stmt->fetch();

        if ($item) {
            db()->prepare("UPDATE item SET quantidade = ? WHERE id = ?")
                ->execute([$quantidade, $item['id']]);
            $atualizados++;
        } else {
            $nao_encontrados++;
        }
    }

    return [
        'ok'              => true,
        'atualizados'     => $atualizados,
        'nao_encontrados' => $nao_encontrados,
        'msg'             => "Importação: $atualizados itens atualizados, $nao_encontrados não encontrados.",
    ];
}

// ── Requisições → Sienge ──────────────────────────────────────────────────────
function sienge_enviar_requisicao(int $requisicao_id): array {
    if (!sienge_ativo()) return ['ok' => false, 'msg' => 'Sienge não configurado.'];

    $stmt = db()->prepare("SELECT r.*, a.nome AS alm_nome, a.obra FROM requisicao_mestre r JOIN almoxarifado a ON a.id=r.almoxarifado_id WHERE r.id = ?");
    $stmt->execute([$requisicao_id]);
    $req = $stmt->fetch();
    if (!$req) return ['ok' => false, 'msg' => "Requisição #$requisicao_id não encontrada."];

    $stmt2 = db()->prepare("SELECT rmi.*, i.nome, i.codigo, i.unidade FROM requisicao_mestre_item rmi JOIN item i ON i.id=rmi.item_id WHERE rmi.requisicao_id = ?");
    $stmt2->execute([$requisicao_id]);
    $itens = $stmt2->fetchAll();

    // ENDPOINT: confirmar na doc do Sienge
    // Candidatos: /v1/purchase-requests | /v1/material-requests | /v1/requisicoes
    $payload = [
        // CAMPOS: ajustar conforme API do Sienge
        'referencia'  => $req['protocolo'],
        'data'        => date('Y-m-d', strtotime($req['data_criacao'])),
        'obra'        => $req['obra'] ?? '',
        'solicitante' => $req['colaborador'],
        'observacao'  => $req['observacao'] ?? '',
        'itens'       => array_map(fn($i) => [
            'codigo'     => $i['codigo'],
            'descricao'  => $i['nome'],
            'quantidade' => (float)$i['quantidade'],
            'unidade'    => $i['unidade'],
        ], $itens),
    ];

    $r = sienge_request('POST', '/v1/purchase-requests', $payload);

    // Se enviou com sucesso, marca a requisição como integrada
    if ($r['ok']) {
        try {
            db()->prepare("UPDATE requisicao_mestre SET observacao = CONCAT(COALESCE(observacao,''), ' [Enviado ao Sienge]') WHERE id = ?")
               ->execute([$requisicao_id]);
        } catch (Throwable $e) {}
    }

    return [
        'ok'  => $r['ok'],
        'msg' => $r['ok'] ? "Requisição {$req['protocolo']} enviada ao Sienge." : 'Erro: ' . $r['error'],
    ];
}

// ── Obras / Centros de custo ──────────────────────────────────────────────────
function sienge_buscar_obras(): array {
    // ENDPOINT: confirmar na doc do Sienge
    // Candidatos: /v1/construction-sites | /v1/obras | /v1/cost-centers
    $r = sienge_request('GET', '/v1/construction-sites');

    if (!$r['ok']) return ['ok' => false, 'msg' => $r['error'], 'obras' => []];

    // MAPEAMENTO: ajustar conforme resposta
    $obras = $r['data']['items'] ?? $r['data'] ?? [];
    return [
        'ok'    => true,
        'total' => count($obras),
        'obras' => array_map(fn($o) => [
            'id'   => $o['id']    ?? $o['codigo'] ?? null,
            'nome' => $o['name']  ?? $o['nome']   ?? '',
            'codigo' => $o['code'] ?? $o['codigo'] ?? '',
        ], $obras),
    ];
}

// ── Fornecedores ──────────────────────────────────────────────────────────────
function sienge_buscar_fornecedores(string $busca = ''): array {
    // ENDPOINT: confirmar na doc do Sienge
    $query = $busca ? ['search' => $busca, 'q' => $busca] : [];
    $r = sienge_request('GET', '/v1/suppliers', [], $query);

    if (!$r['ok']) return ['ok' => false, 'msg' => $r['error'], 'fornecedores' => []];

    $lista = $r['data']['items'] ?? $r['data'] ?? [];
    return [
        'ok'           => true,
        'total'        => count($lista),
        'fornecedores' => array_map(fn($f) => [
            'id'   => $f['id']   ?? null,
            'nome' => $f['name'] ?? $f['nome'] ?? '',
            'cnpj' => $f['cnpj'] ?? $f['document'] ?? '',
        ], $lista),
    ];
}

// ══════════════════════════════════════════════════════════════════════════════
//  LOGI-PRIME → SIENGE  (Sienge consome nossa API REST)
//  Endpoints em /api/v1/* — autenticação via API_TOKEN no .env
// ══════════════════════════════════════════════════════════════════════════════
// Ver: src/controllers/api/v1/
//   GET/POST /api/v1/estoque
//   GET/POST /api/v1/movimentacoes
//   GET/POST /api/v1/requisicoes
//   GET      /api/v1/almoxarifados
//   GET      /api/v1          (documentação)
//   POST     /api/v1/webhook  (recebe eventos do Sienge)
