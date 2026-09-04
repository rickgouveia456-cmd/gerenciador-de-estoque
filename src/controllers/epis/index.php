<?php
requer_login(); $u=usuario_atual();
$almId=(int)($_GET["alm"]??0);
$ids=almoxarifados_permitidos_ids();
if($u["perfil"]==="admin"){$almoxarifados=db()->query("SELECT * FROM almoxarifado ORDER BY nome")->fetchAll();}
elseif($ids){$ph=implode(",",array_fill(0,count($ids),"?"));$s=db()->prepare("SELECT * FROM almoxarifado WHERE id IN ($ph) ORDER BY nome");$s->execute($ids);$almoxarifados=$s->fetchAll();}
else{$almoxarifados=[];}
$sql="SELECT e.*,a.nome AS alm_nome FROM item_epi e JOIN almoxarifado a ON a.id=e.almoxarifado_id WHERE e.ativo=1";$binds=[];
if($almId){$sql.=" AND e.almoxarifado_id=?";$binds[]=$almId;}
elseif($u["perfil"]!=="admin"&&$ids){$ph=implode(",",array_fill(0,count($ids),"?"));$sql.=" AND e.almoxarifado_id IN ($ph)";$binds=array_merge($binds,$ids);}
$sql.=" ORDER BY e.nome";$st=db()->prepare($sql);$st->execute($binds);$epis=$st->fetchAll();
$pageTitle="EPIs";$activeMenu="epis";
ob_start();require VIEWS_PATH."/epis/index.php";$content=ob_get_clean();require VIEWS_PATH."/layouts/base.php";