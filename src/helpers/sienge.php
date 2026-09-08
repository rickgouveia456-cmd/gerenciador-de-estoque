<?php
/**
 * Logi-Prime — Helper de Integração com Sienge
 *
 * Toda comunicação com a API do Sienge passa por aqui.
 * Quando a API estiver disponível, implemente os métodos marcados com TODO.
 *
 * Documentação Sienge API: https://developers.sienge.com.br
 */

// ── Carrega configuração do banco ─────────────────────────────────────────────
function sienge_config(): array {
    static $cfg = null;
    if ($cfg !== null) return $cfg;

    $stmt = db()->prepare("SELECT config_json, ativo FROM integracao_config WHERE sistema = 'sienge'");
    $stmt->execute();
    $row = $stmt->fetch();

    if (!$row) {
        $cfg = ['ativo' => false];
        return $cfg;
    }

    $cfg = array_merge(
        json_decode($row['config_json'] ?? '{}', true) ?: [],
        ['ativo' => (bool)$row['ativo']]
    );
    return $cfg;
}

function sienge_ativo(): bool {
    $cfg = sienge_config();
    return !empty($cfg['ativo'])
        && !empty($cfg['base_url'])
        && !empty($cfg['token']);
}

// ── Cliente HTTP base ─────────────────────────────────────────────────────────
/**
 * Faz uma requisição HTTP para a API do Sienge.
 *
 * @param string $method  GET | POST | PUT | DELETE
 * @param string $endpoint  Ex: /v1/materials
 * @param array  $body  Dados para POST/PUT (será convertido para JSON)
 * @param array  $query  Query params para GET
 * @return array ['ok' => bool, 'status' => int, 'data' => array, 'error' => string]
 */
function sienge_request(string $method, string $endpoint, array $body = [], array $query = []): array {
    $cfg = sienge_config();

    if (!sienge_ativo()) {
        return [
            'ok'     => false,
            'status' => 0,
            'data'   => [],
            'error'  => 'Integração Sienge não configurada ou desativada.',
        ];
    }

    $url = rtrim($cfg['base_url'], '/') . '/' . ltrim($endpoint, '/');
    if ($query) {
        $url .= '?' . http_build_query($query);
    }

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

    $ok = $httpCode >= 200 && $httpCode < 300;

    // Registra no log
    _sienge_log($endpoint, $method, $ok ? 'sucesso' : 'erro', $body, $dados, $curlError ?: ($ok ? null : ($dados['message'] ?? "HTTP $httpCode")));

    return [
        'ok'     => $ok,
        'status' => $httpCode,
        'data'   => $dados ?: [],
        'error'  => $ok ? null : ($curlError ?: ($dados['message'] ?? "Erro HTTP $httpCode")),
    ];
}

// ── Log interno ───────────────────────────────────────────────────────────────
function _sienge_log(string $operacao, string $metodo, string $status, array $payload, $resposta, ?string $erro): void {
    try {
        $u = usuario_atual();
        db()->prepare("
            INSERT INTO integracao_log (sistema, operacao, status, payload, resposta, erro_msg, concluido_em, usuario_id)
            VALUES ('sienge', ?, ?, ?, ?, ?, NOW(), ?)
        ")->execute([
            "$metodo $operacao",
            $status,
            json_encode($payload,  JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
            json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
            $erro,
            $u['id'] ?? null,
        ]);
    } catch (Throwable $e) {
        // Não deixa falha no log quebrar a requisição
    }
}

// ══════════════════════════════════════════════════════════════════════════════
//  MÉTODOS DA API — implemente quando o Sienge liberar o acesso
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Testa a conexão com o Sienge.
 * Retorna true se a API responder com sucesso.
 */
function sienge_testar_conexao(): array {
    // TODO: verificar endpoint correto na doc do Sienge
    $r = sienge_request('GET', '/v1/health');
    return [
        'ok'      => $r['ok'],
        'msg'     => $r['ok'] ? 'Conexão estabelecida com sucesso!' : 'Falha: ' . $r['error'],
        'status'  => $r['status'],
    ];
}

// ── MATERIAIS / INSUMOS ───────────────────────────────────────────────────────

/**
 * Busca materiais cadastrados no Sienge.
 * TODO: ajustar endpoint e mapeamento de campos quando a API estiver disponível.
 */
function sienge_buscar_materiais(int $pagina = 1, int $por_pagina = 100): array {
    // TODO: verificar endpoint correto
    return sienge_request('GET', '/v1/materials', [], [
        'page'     => $pagina,
        'pageSize' => $por_pagina,
    ]);
}

/**
 * Sincroniza materiais do Sienge → Catálogo do Logi-Prime.
 * Para cada material do Sienge, cria ou atualiza em catalogo_insumo.
 */
function sienge_sincronizar_materiais(): array {
    if (!sienge_ativo()) {
        return ['ok' => false, 'msg' => 'Sienge não configurado.', 'total' => 0];
    }

    $pagina    = 1;
    $inseridos = 0;
    $atualizados = 0;
    $erros     = 0;

    do {
        $r = sienge_buscar_materiais($pagina, 100);
        if (!$r['ok']) {
            return ['ok' => false, 'msg' => $r['error'], 'total' => 0];
        }

        // TODO: ajustar estrutura conforme resposta real do Sienge
        $materiais = $r['data']['items'] ?? $r['data'] ?? [];

        foreach ($materiais as $mat) {
            try {
                // TODO: ajustar campos conforme API do Sienge
                $codigo   = $mat['code']        ?? $mat['codigo']   ?? null;
                $nome     = $mat['description'] ?? $mat['nome']     ?? null;
                $unidade  = $mat['unit']        ?? $mat['unidade']  ?? 'un';
                $valor    = $mat['price']       ?? $mat['valor']    ?? null;

                if (!$codigo || !$nome) continue;

                $existe = db()->prepare("SELECT id FROM catalogo_insumo WHERE codigo_ref = ?")->execute([$codigo]);
                $row    = db()->prepare("SELECT id FROM catalogo_insumo WHERE codigo_ref = ?")->execute([$codigo]) ? db()->query("SELECT id FROM catalogo_insumo WHERE codigo_ref = '$codigo'")->fetch() : null;

                $stmt_check = db()->prepare("SELECT id FROM catalogo_insumo WHERE codigo_ref = ?");
                $stmt_check->execute([$codigo]);
                $existente = $stmt_check->fetch();

                if ($existente) {
                    db()->prepare("UPDATE catalogo_insumo SET nome=?, unidade=?, valor_unitario=? WHERE id=?")
                        ->execute([$nome, $unidade, $valor, $existente['id']]);
                    $atualizados++;
                } else {
                    db()->prepare("INSERT INTO catalogo_insumo (nome, codigo_ref, unidade, valor_unitario, criado_por) VALUES (?,?,?,?,'sienge')")
                        ->execute([$nome, $codigo, $unidade, $valor]);
                    $inseridos++;
                }
            } catch (Throwable $e) {
                $erros++;
            }
        }

        // TODO: ajustar paginação conforme resposta do Sienge
        $totalPaginas = $r['data']['totalPages'] ?? 1;
        $pagina++;

    } while ($pagina <= $totalPaginas);

    return [
        'ok'          => true,
        'inseridos'   => $inseridos,
        'atualizados' => $atualizados,
        'erros'       => $erros,
        'total'       => $inseridos + $atualizados,
        'msg'         => "Sincronização concluída: $inseridos inseridos, $atualizados atualizados, $erros erros.",
    ];
}

// ── ESTOQUE ───────────────────────────────────────────────────────────────────

/**
 * Envia posição de estoque do Logi-Prime → Sienge.
 * TODO: implementar quando endpoint de estoque do Sienge estiver disponível.
 */
function sienge_enviar_estoque(int $almoxarifado_id): array {
    // TODO: buscar itens do almoxarifado e enviar saldo para o Sienge
    return ['ok' => false, 'msg' => 'TODO: implementar quando API estiver disponível.'];
}

/**
 * Importa saldo de estoque do Sienge → Logi-Prime.
 * TODO: implementar quando endpoint estiver disponível.
 */
function sienge_importar_estoque(int $almoxarifado_id): array {
    // TODO: buscar saldo no Sienge e atualizar item.quantidade
    return ['ok' => false, 'msg' => 'TODO: implementar quando API estiver disponível.'];
}

// ── REQUISIÇÕES ───────────────────────────────────────────────────────────────

/**
 * Envia uma requisição aprovada do Logi-Prime → Sienge como solicitação de compra.
 * TODO: implementar quando endpoint de requisição do Sienge estiver disponível.
 */
function sienge_enviar_requisicao(int $requisicao_id): array {
    $stmt = db()->prepare("SELECT * FROM requisicao_mestre WHERE id = ?");
    $stmt->execute([$requisicao_id]);
    $req = $stmt->fetch();

    if (!$req) return ['ok' => false, 'msg' => 'Requisição não encontrada.'];

    $stmt2 = db()->prepare("SELECT rmi.*, i.nome, i.codigo, i.unidade FROM requisicao_mestre_item rmi JOIN item i ON i.id = rmi.item_id WHERE rmi.requisicao_id = ?");
    $stmt2->execute([$requisicao_id]);
    $itens = $stmt2->fetchAll();

    // TODO: mapear campos conforme API do Sienge
    $payload = [
        'protocolo'  => $req['protocolo'],
        'data'       => $req['data_criacao'],
        'solicitante'=> $req['colaborador'],
        'itens'      => array_map(fn($i) => [
            'codigo'     => $i['codigo'],
            'descricao'  => $i['nome'],
            'quantidade' => $i['quantidade'],
            'unidade'    => $i['unidade'],
        ], $itens),
    ];

    // TODO: verificar endpoint correto
    return sienge_request('POST', '/v1/purchase-requests', $payload);
}

// ── OBRAS / CENTROS DE CUSTO ──────────────────────────────────────────────────

/**
 * Busca obras/centros de custo no Sienge para vincular aos almoxarifados.
 * TODO: implementar quando endpoint estiver disponível.
 */
function sienge_buscar_obras(): array {
    // TODO: verificar endpoint correto
    return sienge_request('GET', '/v1/construction-sites');
}

// ── FORNECEDORES ──────────────────────────────────────────────────────────────

/**
 * Busca fornecedores cadastrados no Sienge.
 * TODO: implementar quando endpoint estiver disponível.
 */
function sienge_buscar_fornecedores(string $busca = ''): array {
    $query = $busca ? ['search' => $busca] : [];
    // TODO: verificar endpoint correto
    return sienge_request('GET', '/v1/suppliers', [], $query);
}
