<?php
requer_login(); $u=usuario_atual();
$busca=trim($_GET["q"]??"");
$sql="SELECT e.*,a.nome AS alm_nome FROM item_epi e JOIN almoxarifado a ON a.id=e.almoxarifado_id WHERE e.ativo=1";$binds=[];
if($busca){$sql.=" AND (e.nome LIKE ? OR e.identificacao LIKE ?)";$binds[]="%$busca%";$binds[]="%$busca%";}
$ids=almoxarifados_permitidos_ids();
if($u["perfil"]!=="admin"&&$ids){$ph=implode(",",array_fill(0,count($ids),"?"));$sql.=" AND e.almoxarifado_id IN ($ph)";$binds=array_merge($binds,$ids);}
$sql.=" ORDER BY e.nome";$st=db()->prepare($sql);$st->execute($binds);$epis=$st->fetchAll();
$pageTitle="Modulo EPI";$activeMenu="epi_modulo";
ob_start();require VIEWS_PATH."/epis/modulo.php";$content=ob_get_clean();require VIEWS_PATH."/layouts/base.php";