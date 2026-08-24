<?php
requer_login();
$u = usuario_atual();

$almId      = (int)($_GET['almoxarifado_id'] ?? 0);
$dataIni    = $_GET['data_ini'] ?? date('Y-m-01');
$dataFim    = $_GET['data_fim'] ?? date('Y-m-d');
$filtroResp = trim($_GET['responsavel'] ?? '');
$exportar   = !empty($_GET['exportar']);

$ids = almoxarifados_permitidos_ids();

// Monta query de movimentações tipo 'saida'
$sql = "SELECT m.*, i.nome AS item_nome, i.unidade, i.codigo, i.categoria,
               a.nome AS alm_nome
        FROM movimentacao m
        JOIN item i ON i.id = m.item_id
        JOIN almoxarifado a ON a.id = i.almoxarifado_id
        WHERE m.tipo = 'saida'
          AND m.data >= ?
          AND m.data <= ?";
$binds = [$dataIni, "$dataFim 23:59:59"];

if ($almId) {
    $sql .= ' AND i.almoxarifado_id = ?';
    $binds[] = $almId;
} elseif ($u['perfil'] !== 'admin' && !empty($ids)) {
    $ph   = implode(',', array_fill(0, count($ids), '?'));
    $sql .= " AND i.almoxarifado_id IN ($ph)";
    $binds = array_merge($binds, $ids);
}

if ($filtroResp !== '') {
    $sql .= ' AND (m.responsavel LIKE ? OR m.observacao LIKE ?)';
    $binds[] = "%$filtroResp%";
    $binds[] = "%$filtroResp%";
}

$sql .= ' ORDER BY m.data DESC';
$st = db()->prepare($sql);
$st->execute($binds);
$movimentacoes = $st->fetchAll();

// Extrai nome da pessoa da observação ou campo responsavel
function extrair_pessoa(array $mov): string {
    $obs  = $mov['observacao'] ?? '';
    $resp = $mov['responsavel'] ?? '';

    // Padrão: "liberado P/ NOME | ..."
    if (preg_match('/liberado\s+p[\/\\.]\s*([^|]+)/i', $obs, $m)) {
        return trim($m[1]);
    }
    // Padrão: "Colaborador: NOME"
    if (preg_match('/colaborador[:\s]+(.+)/i', $obs, $m)) {
        return trim($m[1]);
    }
    // Padrão: "Para: NOME"
    if (preg_match('/para[:\s]+(.+)/i', $obs, $m)) {
        return trim($m[1]);
    }
    // Campo responsavel direto
    if ($resp !== '') return $resp;

    return 'Não identificado';
}

// Agrupar por pessoa
$porPessoa = [];
foreach ($movimentacoes as $mov) {
    $pessoa = extrair_pessoa($mov);
    if (!isset($porPessoa[$pessoa])) {
        $porPessoa[$pessoa] = ['total' => 0, 'movs' => []];
    }
    $porPessoa[$pessoa]['total'] += (float)$mov['quantidade'];
    $porPessoa[$pessoa]['movs'][] = $mov;
}

// Ordenar por maior consumo
uasort($porPessoa, fn($a, $b) => $b['total'] <=> $a['total']);

// Exportar CSV
if ($exportar) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="consumo_por_pessoa_' . date('Ymd') . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM UTF-8
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Pessoa', 'Item', 'Código', 'Categoria', 'Almoxarifado', 'Quantidade', 'Unidade', 'Data', 'Observação'], ';');
    foreach ($porPessoa as $pessoa => $dados) {
        foreach ($dados['movs'] as $mov) {
            fputcsv($out, [
                $pessoa,
                $mov['item_nome'],
                $mov['codigo']     ?? '',
                $mov['categoria']  ?? '',
                $mov['alm_nome'],
                $mov['quantidade'],
                $mov['unidade'],
                fmt_data($mov['data'], 'd/m/Y H:i'),
                $mov['observacao'] ?? '',
            ], ';');
        }
    }
    fclose($out);
    exit;
}

$almoxarifados = $u['perfil'] === 'admin'
    ? db()->query('SELECT * FROM almoxarifado ORDER BY nome')->fetchAll()
    : (function() use ($ids) {
        if (empty($ids)) return [];
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $s  = db()->prepare("SELECT * FROM almoxarifado WHERE id IN ($ph) ORDER BY nome");
        $s->execute($ids);
        return $s->fetchAll();
    })();

$pageTitle  = 'Consumo por Pessoa';
$activeMenu = 'relatorios';
ob_start();
require VIEWS_PATH . '/relatorios/consumo_pessoa.php';
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/base.php';
