<?php
requer_login();
$u = usuario_atual();

$almId   = (int)($_GET['almoxarifado_id'] ?? 0);
$dataIni = $_GET['data_ini'] ?? date('Y-m-01');
$dataFim = $_GET['data_fim'] ?? date('Y-m-d');
$aba     = $_GET['aba'] ?? 'saidas';
$tipoMov = $aba === 'entradas' ? 'entrada' : 'saida';
$ids     = almoxarifados_permitidos_ids();

$sql = "SELECT m.*, i.nome AS item_nome, i.unidade, i.codigo, i.categoria,
               a.nome AS alm_nome
        FROM movimentacao m
        JOIN item i ON i.id = m.item_id
        JOIN almoxarifado a ON a.id = i.almoxarifado_id
        WHERE m.tipo = ?
          AND m.data >= ?
          AND m.data <= ?";
$binds = [$tipoMov, $dataIni, "$dataFim 23:59:59"];

if ($almId) {
    $sql .= ' AND i.almoxarifado_id = ?';
    $binds[] = $almId;
} elseif ($u['perfil'] !== 'admin' && !empty($ids)) {
    $ph    = implode(',', array_fill(0, count($ids), '?'));
    $sql  .= " AND i.almoxarifado_id IN ($ph)";
    $binds = array_merge($binds, $ids);
}

$sql .= ' ORDER BY m.data DESC';
$st = db()->prepare($sql);
$st->execute($binds);
$movimentacoes = $st->fetchAll();

// Extrai colaborador da observação (mesmo padrão de consumo_pessoa)
function extrai_colaborador_csv(string $obs): string {
    if (preg_match('/liberado\s+p[\/\\.]\s*([^|]+)/i', $obs, $m)) return trim($m[1]);
    if (preg_match('/colaborador[:\s]+(.+)/i', $obs, $m))          return trim($m[1]);
    if (preg_match('/para[:\s]+(.+)/i', $obs, $m))                 return trim($m[1]);
    return '';
}

$filename = 'consumo_' . $aba . '_' . date('Ymd') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header("Content-Disposition: attachment; filename=\"$filename\"");
// BOM UTF-8 para Excel reconhecer acentos
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, ['Data', 'Código', 'Item', 'Categoria', 'Almoxarifado', 'Quantidade', 'Unidade', 'Responsável', 'Colaborador', 'Observação'], ';');

foreach ($movimentacoes as $m) {
    fputcsv($out, [
        fmt_data($m['data'], 'd/m/Y H:i'),
        $m['codigo']     ?? '',
        $m['item_nome']  ?? '',
        $m['categoria']  ?? '',
        $m['alm_nome']   ?? '',
        number_format((float)$m['quantidade'], 4, '.', ''),
        $m['unidade']    ?? '',
        $m['responsavel'] ?? '',
        extrai_colaborador_csv($m['observacao'] ?? ''),
        $m['observacao'] ?? '',
    ], ';');
}

fclose($out);
exit;
