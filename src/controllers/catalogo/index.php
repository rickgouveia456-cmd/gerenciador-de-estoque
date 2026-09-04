<?php
requer_login(); $u=usuario_atual();
$q=trim($_GET['q']??'');$cat=$_GET['categoria']??'';
$sql='SELECT * FROM catalogo_insumo WHERE ativo=1';$binds=[];
if($q){$sql.=' AND nome LIKE ?';$binds[]="%$q%";}
if($cat){$sql.=' AND categoria=?';$binds[]=$cat;}
$sql.=' ORDER BY nome';$st=db()->prepare($sql);$st->execute($binds);$insumos=$st->fetchAll();
$categorias=['geral','epi','maquinario','eletrica','hidraulica','gas'];
$pageTitle='Catálogo de Insumos';$activeMenu='catalogo';
ob_start();require VIEWS_PATH.'/catalogo/index.php';
$content=ob_get_clean();require VIEWS_PATH.'/layouts/base.php';
