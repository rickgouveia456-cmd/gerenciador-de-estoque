<?php
requer_login(); $u=usuario_atual();
$colaborador=urldecode(trim($_GET["colaborador"]??""));
$dataIni=$_GET["data_ini"]??"2020-01-01";$dataFim=$_GET["data_fim"]??date("Y-m-d");
$st=db()->prepare("SELECT m.*,i.nome AS item_nome,i.unidade,i.ca,a.nome AS alm_nome FROM movimentacao m JOIN item i ON i.id=m.item_id JOIN almoxarifado a ON a.id=i.almoxarifado_id WHERE m.tipo=? AND i.categoria=? AND m.data>=? AND m.data<=? ORDER BY m.data ASC");
$st->execute(["saida","epi",$dataIni,"$dataFim 23:59:59"]);$all=$st->fetchAll();
$movs=array_filter($all,function($m)use($colaborador){return stripos($m["observacao"]??"",$colaborador)!==false;});
$pageTitle="Ficha EPI";$activeMenu="epis";
ob_start();require VIEWS_PATH."/epis/ficha.php";$content=ob_get_clean();require VIEWS_PATH."/layouts/base.php";