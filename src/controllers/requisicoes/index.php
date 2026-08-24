<?php
requer_login(); $u=usuario_atual();
$colab=trim($_GET['colaborador']??''); $status=$_GET['status']??''; $dini=$_GET['data_ini']??''; $dfim=$_GET['data_fim']??'';
$ids=almoxarifados_permitidos_ids();
$sql='SELECT r.*,i.nome AS item_nome,i.unidade,a.nome AS alm_nome FROM requisicao r JOIN item i ON i.id=r.item_id JOIN almoxarifado a ON a.id=i.almoxarifado_id WHERE 1=1';
$binds=[];
if($u['perfil']!=='admin'){$ph=implode(',',array_fill(0,count($ids),'?')); $sql.=" AND i.almoxarifado_id IN ($ph)"; $binds=array_merge($binds,$ids);}
if($colab){$sql.=' AND r.colaborador LIKE ?'; $binds[]="%$colab%";}
if($status){$sql.=' AND r.status=?'; $binds[]=$status;}
if($dini){$sql.=' AND r.data_retirada>=?'; $binds[]=$dini;}
if($dfim){$sql.=' AND r.data_retirada<=?'; $binds[]="$dfim 23:59:59";}
$sql.=' ORDER BY r.data_retirada DESC';
$st=db()->prepare($sql); $st->execute($binds); $requisicoes=$st->fetchAll();
$pageTitle='Requisições'; $activeMenu='requisicoes';
ob_start(); require VIEWS_PATH.'/requisicoes/index.php';
$content=ob_get_clean(); require VIEWS_PATH.'/layouts/base.php';
