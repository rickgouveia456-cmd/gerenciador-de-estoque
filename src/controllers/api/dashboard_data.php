<?php
/**
 * API: Retorna dados do dashboard em JSON para atualização AJAX
 * GET /api/dashboard?periodo=30&alm=0
 */
requer_login();
header('Content-Type: application/json');

$u      = usuario_atual();
$periodo = (int)($_GET['periodo'] ?? 30);
$almFiltro = (int)($_GET['alm'] ?? 0);
if (!in_array($periodo, [7, 30, 60])) $periodo = 30;

// IDs permitidos
$ids = almoxarifados_permitidos_ids();
$almIdsConsulta = $ids;
if ($almFiltro && in_array($almFiltro, $ids)) {
    $almIdsConsulta = [$almFiltro];
}
$almStr = $almIdsConsulta ? implode(',', array_map('intval', $almIdsConsulta)) : '0';
$corte  = (new DateTime())->modify("-$periodo days")->format('Y-m-d H:i:s');

// Saídas totais
$saidaTotal = (int)db()->query(
    "SELECT COALESCE(SUM(m.quantidade),0)
     FROM movimentacao m JOIN item i ON i.id=m.item_id
     WHERE m.tipo='saida' AND m.data>='$corte'
       AND i.almoxarifado_id IN ($almStr)"
)->fetchColumn();

$mediaDia = $periodo > 0 ? round($saidaTotal / $periodo, 1) : 0;

// Consumo diário
$stmt = db()->prepare(
    "SELECT DATE(m.data) as dia, COALESCE(SUM(m.quantidade),0) as total
     FROM movimentacao m JOIN item i ON i.id=m.item_id
     WHERE m.tipo='saida' AND m.data>=?
       AND i.almoxarifado_id IN ($almStr)
     GROUP BY DATE(m.data) ORDER BY dia ASC"
);
$stmt->execute([$corte]);
$rows = $stmt->fetchAll();

$diasMap = [];
for ($i = $periodo - 1; $i >= 0; $i--) {
    $d = (new DateTime())->modify("-$i days")->format('Y-m-d');
    $diasMap[$d] = 0;
}
foreach ($rows as $r) $diasMap[$r['dia']] = round((float)$r['total'], 1);

// Top 5 insumos
$stmt2 = db()->prepare(
    "SELECT i.nome, COALESCE(SUM(m.quantidade),0) as total
     FROM movimentacao m JOIN item i ON i.id=m.item_id
     WHERE m.tipo='saida' AND m.data>=?
       AND i.almoxarifado_id IN ($almStr)
     GROUP BY i.id ORDER BY total DESC LIMIT 5"
);
$stmt2->execute([$corte]);
$top5Insumos = $stmt2->fetchAll();

// Top 5 colaboradores
$stmt3 = db()->prepare(
    "SELECT responsavel as colaborador, COALESCE(SUM(m.quantidade),0) as total
     FROM movimentacao m JOIN item i ON i.id=m.item_id
     WHERE m.tipo='saida' AND m.data>=?
       AND i.almoxarifado_id IN ($almStr)
       AND m.responsavel IS NOT NULL AND m.responsavel != ''
     GROUP BY m.responsavel ORDER BY total DESC LIMIT 5"
);
$stmt3->execute([$corte]);
$top5Colabs = $stmt3->fetchAll();

// Abaixo do mínimo
$abaixoMin = (int)db()->query(
    "SELECT COUNT(*) FROM item
     WHERE ativo=1 AND quantidade <= estoque_minimo
       AND almoxarifado_id IN ($almStr)"
)->fetchColumn();

echo json_encode([
    'saida_total'  => $saidaTotal,
    'media_dia'    => $mediaDia,
    'abaixo_min'   => $abaixoMin,
    'grafico_labels' => array_keys($diasMap),
    'grafico_data'   => array_values($diasMap),
    'top5_insumos'   => array_values($top5Insumos),
    'top5_colabs'    => array_values($top5Colabs),
], JSON_UNESCAPED_UNICODE);
