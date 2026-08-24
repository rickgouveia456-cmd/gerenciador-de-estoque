<?php
requer_login(); $u=usuario_atual();
$almId=(int)($_GET['alm']??0);
if($almId&&!usuario_tem_acesso_almoxarifado($almId)){flash('Acesso negado.','danger');redirect('/');}
$ids=almoxarifados_permitidos_ids();
$almoxarifados=$u['perfil']==='admin'?db()->query('SELECT * FROM almoxarifado ORDER BY nome')->fetchAll():
    ($ids?(function($ids){$ph=implode(',',array_fill(0,count($ids),'?'));$s=db()->prepare("SELECT * FROM almoxarifado WHERE id IN ($ph) ORDER BY nome");$s->execute($ids);return $s->fetchAll();})($ids):[]);
$sql='SELECT f.*,a.nome AS alm_nome FROM ferramenta f JOIN almoxarifado a ON a.id=f.almoxarifado_id WHERE f.ativo=1';
$binds=[];
if($almId){$sql.=' AND f.almoxarifado_id=?';$binds[]=$almId;}
elseif($u['perfil']!=='admin'){$ph=implode(',',array_fill(0,count($ids),'?'));$sql.=" AND f.almoxarifado_id IN ($ph)";$binds=array_merge($binds,$ids);}
$sql.=' ORDER BY f.nome';
$st=db()->prepare($sql);$st->execute($binds);$ferramentas=$st->fetchAll();
$pageTitle='Ferramentas';$activeMenu='ferramentas';
ob_start();require VIEWS_PATH.'/ferramentas/index.php';
$content=ob_get_clean();require VIEWS_PATH.'/layouts/base.php';
