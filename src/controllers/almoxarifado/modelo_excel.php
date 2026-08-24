<?php
requer_login();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="modelo_importacao_almoxarifado.csv"');
$f=fopen('php://output','w');
fprintf($f,chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8 para Excel BR
fputcsv($f,['Codigo','Nome','Categoria','Unidade','Quantidade','Estoque Minimo','CA (EPI)','Valor Unitario'],';');
// Exemplos de cada categoria
fputcsv($f,['7954','CALÇA BRIM CORDÃO ELASTICO M','epi','un',53,20,'','35.00'],';');
fputcsv($f,['9047','Óculos de Proteção incolor','epi','UND',100,20,'CA-12345','12.50'],';');
fputcsv($f,['9725','Martelo tipo Unha 27mm C/ Cabo Fibra','geral','un',20,10,'','45.90'],';');
fputcsv($f,['8426','Eletroduto Reforçado Laranja 20mm','eletrica','m',5500,2000,'','3.20'],';');
fputcsv($f,['8486','Fita Veda Rosca 18mm x 50m','hidraulica','UND',36,20,'','8.00'],';');
fputcsv($f,['19491','Broca diamantada 42mm x 320mm','maquinario','un',2,1,'','250.00'],';');
fclose($f);exit;