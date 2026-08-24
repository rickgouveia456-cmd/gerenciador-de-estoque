<?php
requer_login();
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"modelo_catalogo.csv\"");
$f=fopen("php://output","w");
fprintf($f,chr(0xEF).chr(0xBB).chr(0xBF));
fputcsv($f,["Nome","Codigo Ref","Unidade","Categoria","CA","Valor Unitario"],";");
fputcsv($f,["Capacete Amarelo","8707","UND","epi","","45.90"],";");
fputcsv($f,["Luva Flextactil","7794","un","epi","CA-12345","8.50"],";");
fputcsv($f,["FITA CREPE 50MMX50M","4828","un","geral","","12.00"],";");
fputcsv($f,["Eletroduto Laranja 20mm","8426","m","eletrica","","3.20"],";");
fclose($f);exit;