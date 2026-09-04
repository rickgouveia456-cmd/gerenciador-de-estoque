<?php
/**
 * Controller: Relatório Ficha de EPI (FORM.SEG.014)
 * GET  /relatorios/ficha-epi         → formulário de seleção
 * GET  /relatorios/ficha-epi/exportar → exporta CSV
 */
requer_login();
$u = usuario_atual();

$ids = almoxarifados_permitidos_ids();

// Buscar todas movimentações de saída de EPIs nos almoxarifados permitidos
$sql = "SELECT m.*, i.nome AS item_nome, i.unidade, i.codigo, i.categoria,
               a.nome AS alm_nome
        FROM movimentacao m
        JOIN item i ON i.id = m.item_id
        JOIN almoxarifado a ON a.id = i.almoxarifado_id
        WHERE m.tipo = 'saida'
          AND i.categoria = 'epi'";
$binds = [];

if ($u['perfil'] !== 'admin' && !empty($ids)) {
    $ph   = implode(',', array_fill(0, count($ids), '?'));
    $sql .= " AND i.almoxarifado_id IN ($ph)";
    $binds = array_merge($binds, $ids);
} elseif ($u['perfil'] !== 'admin' && empty($ids)) {
    // sem acesso
    $sql .= ' AND 1=0';
}

$sql .= ' ORDER BY m.data DESC';
$st = db()->prepare($sql);
$st->execute($binds);
$todasMovs = $st->fetchAll();

// Extrair funcionários únicos
function extrair_pessoa_epi(array $mov): string {
    $obs  = $mov['observacao'] ?? '';
    $resp = $mov['responsavel'] ?? '';

    if (preg_match('/liberado\s+p[\/\.]\s*([^|]+)/i', $obs, $m)) {
        return trim($m[1]);
    }
    if (preg_match('/colaborador[:\s]+(.+)/i', $obs, $m)) {
        return trim($m[1]);
    }
    if (preg_match('/para[:\s]+(.+)/i', $obs, $m)) {
        return trim($m[1]);
    }
    if ($resp !== '') return $resp;
    return 'Não identificado';
}

$funcionarios = [];
foreach ($todasMovs as $mov) {
    $nome = extrair_pessoa_epi($mov);
    if ($nome !== 'Não identificado') {
        $funcionarios[$nome] = $nome;
    }
}
ksort($funcionarios);

// Parâmetros do formulário
$funcionarioSel = trim($_GET['funcionario'] ?? '');
$dataIni        = $_GET['data_ini'] ?? date('Y-01-01');
$dataFim        = $_GET['data_fim'] ?? date('Y-m-d');
$exportar       = !empty($_GET['exportar']);

// Filtrar movimentações para o funcionário selecionado
$fichaMovs = [];
if ($funcionarioSel !== '') {
    $sqlF = "SELECT m.*, i.nome AS item_nome, i.unidade, i.codigo, i.ca,
                    a.nome AS alm_nome
             FROM movimentacao m
             JOIN item i ON i.id = m.item_id
             JOIN almoxarifado a ON a.id = i.almoxarifado_id
             WHERE m.tipo = 'saida'
               AND i.categoria = 'epi'
               AND m.data >= ?
               AND m.data <= ?";
    $bindsF = [$dataIni, "$dataFim 23:59:59"];

    if ($u['perfil'] !== 'admin' && !empty($ids)) {
        $ph    = implode(',', array_fill(0, count($ids), '?'));
        $sqlF .= " AND i.almoxarifado_id IN ($ph)";
        $bindsF = array_merge($bindsF, $ids);
    }
    $sqlF .= ' ORDER BY m.data ASC';

    $stF = db()->prepare($sqlF);
    $stF->execute($bindsF);
    $todasF = $stF->fetchAll();

    foreach ($todasF as $mov) {
        if (extrair_pessoa_epi($mov) === $funcionarioSel) {
            $fichaMovs[] = $mov;
        }
    }
}

// Exportar CSV (FORM.SEG.014)
if ($exportar && $funcionarioSel !== '') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="FORM_SEG_014_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $funcionarioSel) . '_' . date('Ymd') . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM UTF-8
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Qtd', 'Descrição do EPI', 'C.A.', 'Data Entrega', 'Responsável', 'Data Devolução', 'Observação'], ';');
    foreach ($fichaMovs as $mov) {
        fputcsv($out, [
            $mov['quantidade'],
            $mov['item_nome'],
            $mov['ca'] ?? '',
            fmt_data($mov['data'], 'd/m/Y'),
            extrair_pessoa_epi($mov),
            '', // Data Devolução — não armazenado neste fluxo
            $mov['observacao'] ?? '',
        ], ';');
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

$pageTitle  = 'Ficha de EPI (FORM.SEG.014)';
$activeMenu = 'relatorios';
ob_start();
require VIEWS_PATH . '/relatorios/ficha_epi.php';
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/base.php';
