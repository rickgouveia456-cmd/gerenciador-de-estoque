<?php
/**
 * Baixar modelo CSV para importação do catálogo
 * GET /catalogo/modelo_csv
 */
requer_login();

$filename = 'modelo_catalogo.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// BOM UTF-8 para abrir corretamente no Excel
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// Cabeçalho
fputcsv($out, ['Nome', 'Código Ref', 'Unidade', 'Categoria', 'CA', 'Valor Unitário'], ';');

// Linhas de exemplo
fputcsv($out, ['Capacete de Segurança Aba Total', 'CAP-001', 'un', 'epi', 'CA12345', '45.90'], ';');
fputcsv($out, ['Cimento CP-II 50kg', 'CIM-002', 'sc', 'geral', '', '38.50'], ';');
fputcsv($out, ['Disjuntor Bipolar 20A', 'DIS-003', 'un', 'eletrica', '', '22.00'], ';');
fputcsv($out, ['Registro de Gaveta 3/4"', 'REG-004', 'un', 'hidraulica', '', '18.75'], ';');
fputcsv($out, ['Botijão de Gás 13kg', 'BOT-005', 'un', 'gas', '', '120.00'], ';');
fputcsv($out, ['Furadeira de Impacto 750W', 'FUR-006', 'un', 'maquinario', '', '380.00'], ';');

fclose($out);
exit;
