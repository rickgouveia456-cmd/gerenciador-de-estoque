<?php
/**
 * Dashboard de Saúde do Sistema — 3.7.4
 * Monitoramento em tempo real: banco, usuários, movimentações, integrações
 */
requer_login();
$u = usuario_atual();
if ($u['perfil'] !== 'admin') {
    flash('Acesso restrito a administradores.', 'danger');
    redirect('/');
}

require_once HELPERS_PATH . '/sienge.php';

$agora = new DateTime();

// ── Banco de dados ────────────────────────────────────────────────────────────
try {
    $db_ok    = true;
    $db_info  = db()->query("SELECT VERSION() as v")->fetchColumn();
    $db_size  = db()->query("
        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
    ")->fetchColumn();
} catch (Throwable $e) {
    $db_ok   = false;
    $db_info = 'Erro: ' . $e->getMessage();
    $db_size = 0;
}

// ── Totais gerais ─────────────────────────────────────────────────────────────
$totais = [];
$tabelas = [
    'almoxarifado'     => 'Almoxarifados',
    'item'             => 'Itens',
    'usuario'          => 'Usuários',
    'colaborador'      => 'Colaboradores',
    'movimentacao'     => 'Movimentações',
    'requisicao_mestre'=> 'Requisições',
    'ficha_epi'        => 'Fichas EPI',
    'ferramenta'       => 'Ferramentas',
    'catalogo_insumo'  => 'Catálogo',
];
foreach ($tabelas as $tabela => $label) {
    try {
        $n = (int)db()->query("SELECT COUNT(*) FROM `$tabela`")->fetchColumn();
        $totais[$label] = $n;
    } catch (Throwable $e) {
        $totais[$label] = '—';
    }
}

// ── Atividade recente (últimas 24h) ───────────────────────────────────────────
$ontem = (clone $agora)->modify('-24 hours')->format('Y-m-d H:i:s');

$movs_hoje = (int)db()->prepare("SELECT COUNT(*) FROM movimentacao WHERE data >= ?")->execute([$ontem])
    ? db()->prepare("SELECT COUNT(*) FROM movimentacao WHERE data >= ?")->execute([$ontem]) && ($s = db()->prepare("SELECT COUNT(*) FROM movimentacao WHERE data >= ?")) && $s->execute([$ontem]) ? (int)$s->fetchColumn() : 0
    : 0;

$s = db()->prepare("SELECT COUNT(*) FROM movimentacao WHERE data >= ?");
$s->execute([$ontem]); $movs_hoje = (int)$s->fetchColumn();

$s = db()->prepare("SELECT COUNT(*) FROM requisicao_mestre WHERE data_criacao >= ?");
$s->execute([$ontem]); $reqs_hoje = (int)$s->fetchColumn();

$s = db()->prepare("SELECT COUNT(*) FROM usuario WHERE ativo = 1");
$s->execute(); $usuarios_ativos = (int)$s->fetchColumn();

// ── Últimos logins (últimas 10 sessões ativas) ────────────────────────────────
// Não temos tabela de sessões — mostramos os últimos usuários com movimentação
$ultimas_movs = db()->query("
    SELECT m.responsavel, m.data, m.tipo, i.nome as item_nome, a.nome as alm_nome
    FROM movimentacao m
    JOIN item i ON i.id = m.item_id
    JOIN almoxarifado a ON a.id = i.almoxarifado_id
    ORDER BY m.data DESC
    LIMIT 10
")->fetchAll();

// ── Itens críticos ────────────────────────────────────────────────────────────
$criticos = (int)db()->query("SELECT COUNT(*) FROM item WHERE quantidade <= 0 AND ativo = 1")->fetchColumn();
$alertas  = (int)db()->query("SELECT COUNT(*) FROM item WHERE quantidade > 0 AND quantidade <= estoque_minimo AND ativo = 1")->fetchColumn();

// ── Requisições pendentes ─────────────────────────────────────────────────────
$req_pendentes = (int)db()->query("SELECT COUNT(*) FROM requisicao_mestre WHERE status = 'pendente'")->fetchColumn();

// ── Log de integração (últimos erros) ─────────────────────────────────────────
$erros_integracao = [];
try {
    $s = db()->prepare("SELECT * FROM integracao_log WHERE status = 'erro' ORDER BY criado_em DESC LIMIT 5");
    $s->execute(); $erros_integracao = $s->fetchAll();
} catch (Throwable $e) {}

// ── Status da integração Sienge ───────────────────────────────────────────────
$sienge_ativo = sienge_ativo();

// ── Uptime aproximado (data do item mais antigo como proxy) ───────────────────
$primeiro = db()->query("SELECT MIN(id) FROM movimentacao")->fetchColumn();
$data_primeiro_uso = $primeiro
    ? db()->query("SELECT data FROM movimentacao ORDER BY id ASC LIMIT 1")->fetchColumn()
    : null;

$pageTitle  = 'Saúde do Sistema';
$activeMenu = 'saude';
ob_start();
require VIEWS_PATH . '/admin/saude.php';
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/base.php';
