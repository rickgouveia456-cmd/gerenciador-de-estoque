<?php
requer_login();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="modelo_importacao.csv"');
$f=fopen('php://output','w');
fprintf($f,chr(0xEF).chr(0xBB).chr(0xBF));
fputcsv($f,['Codigo','Nome','Unidade','Quantidade','Estoque Minimo'],';');
fputcsv($f,['CIM-001','Cimento CP-II','sc',500,100],';');
fputcsv($f,['ARG-002','Areia Grossa','m3',30,5],';');
fclose($f);exit;
