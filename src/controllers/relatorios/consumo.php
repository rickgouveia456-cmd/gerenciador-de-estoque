<?php
requer_login(); $u=usuario_atual();
$almId=(int)($_GET['almoxarifado_id']??0);
$dataIni=$_GET['data_ini']??date('Y-m-01');
$dataFim=$_GET['data_fim']??date('Y-m-d');
$aba=$_GET['aba']??'saidas';
$tipoMov=$aba==='entradas'?'entrada':'saida';
$ids=almoxarifados_permitidos_ids();
$sql="SELECT m.*,i.nome AS item_nome,i.unidade,i.codigo,i.categoria,a.nome AS alm_nome FROM movimentacao m JOIN item i ON i.id=m.item_id JOIN almoxarifado a ON a.id=i.almoxarifado_id WHERE m.tipo=? AND m.data>=? AND m.data<=?";
$binds=[$tipoMov,$dataIni,"$dataFim 23:59:59"];
if($almId){$sql.=' AND i.almoxarifado_id=?';$binds[]=$almId;}
elseif($u['perfil']!=='admin'){$ph=implode(',',array_fill(0,count($ids),'?'));$sql.=" AND i.almoxarifado_id IN ($ph)";$binds=array_merge($binds,$ids);}
$sql.=' ORDER BY m.data DESC';$st=db()->prepare($sql);$st->execute($binds);$movimentacoes=$st->fetchAll();
$almoxarifados=$u['perfil']==='admin'?db()->query('SELECT * FROM almoxarifado ORDER BY nome')->fetchAll():
    ($ids?(function($ids){$ph=implode(',',array_fill(0,count($ids),'?'));$s=db()->prepare("SELECT * FROM almoxarifado WHERE id IN ($ph) ORDER BY nome");$s->execute($ids);return $s->fetchAll();})($ids):[]);
$pageTitle='Relatório de Consumo';$activeMenu='relatorios';
ob_start();require VIEWS_PATH.'/relatorios/consumo.php';
$content=ob_get_clean();require VIEWS_PATH.'/layouts/base.php';
