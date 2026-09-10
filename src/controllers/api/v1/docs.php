<?php
/**
 * API v1 — Documentação dos endpoints disponíveis
 * GET /api/v1
 */
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'nome'    => 'Logi-Prime API',
    'versao'  => 'v1',
    'descricao' => 'API REST para integração com sistemas externos (Sienge, ERP, apps)',
    'autenticacao' => [
        'tipo'   => 'Bearer Token',
        'header' => 'Authorization: Bearer SEU_TOKEN',
        'config' => 'Defina API_TOKEN no .env ou em Admin → Configurações do sistema',
    ],
    'endpoints' => [
        [
            'metodo'    => 'GET',
            'rota'      => '/api/v1/almoxarifados',
            'descricao' => 'Lista todos os almoxarifados com totais de estoque',
            'params'    => [],
        ],
        [
            'metodo'    => 'GET',
            'rota'      => '/api/v1/estoque',
            'descricao' => 'Lista itens em estoque',
            'params'    => [
                'alm'    => 'ID do almoxarifado (opcional)',
                'codigo' => 'Código do item (opcional)',
                'nome'   => 'Busca por nome (opcional)',
            ],
        ],
        [
            'metodo'    => 'GET',
            'rota'      => '/api/v1/movimentacoes',
            'descricao' => 'Lista movimentações de entrada e saída',
            'params'    => [
                'alm'  => 'ID do almoxarifado (opcional)',
                'tipo' => 'entrada | saida (opcional)',
                'de'   => 'Data inicial YYYY-MM-DD (padrão: 30 dias atrás)',
                'ate'  => 'Data final YYYY-MM-DD (padrão: hoje)',
            ],
        ],
        [
            'metodo'    => 'POST',
            'rota'      => '/api/v1/movimentacoes',
            'descricao' => 'Registra nova entrada ou saída de item',
            'body'      => [
                'item_id'     => 'ID do item (obrigatório)',
                'tipo'        => 'entrada | saida (obrigatório)',
                'quantidade'  => 'Quantidade (obrigatório)',
                'responsavel' => 'Nome do responsável (opcional)',
                'observacao'  => 'Observação (opcional)',
            ],
        ],
        [
            'metodo'    => 'GET',
            'rota'      => '/api/v1/requisicoes',
            'descricao' => 'Lista requisições de materiais',
            'params'    => [
                'status' => 'pendente | aprovada | entregue | recusada (opcional)',
                'alm'    => 'ID do almoxarifado (opcional)',
            ],
        ],
        [
            'metodo'    => 'POST',
            'rota'      => '/api/v1/requisicoes',
            'descricao' => 'Cria nova requisição de materiais',
            'body'      => [
                'almoxarifado_id' => 'ID do almoxarifado (obrigatório)',
                'colaborador'     => 'Nome do colaborador (obrigatório)',
                'observacao'      => 'Observação (opcional)',
                'itens'           => 'Array de {item_id, quantidade} (obrigatório)',
            ],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
