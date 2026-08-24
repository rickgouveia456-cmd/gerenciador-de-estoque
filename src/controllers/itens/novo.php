<?php
requer_almoxarife(); $u=usuario_atual();
$ids=almoxarifados_permitidos_ids();
$almoxarifados=$u['perfil']==='admin' ? db()->query('SELECT * FROM almoxarifado ORDER BY nome')->fetchAll() :
    ($ids ? (function($ids){ $ph=implode(',',array_fill(0,count($ids),'?')); $s=db()->prepare("SELECT * FROM almoxarifado WHERE id IN ($ph) ORDER BY nome"); $s->execute($ids); return $s->fetchAll(); })($ids) : []);
$almPresel=(int)($_GET['alm']??0);
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $codigo=trim($_POST['codigo']); $almId=(int)$_POST['almoxarifado_id'];
    $ex=db()->prepare('SELECT id,nome FROM item WHERE codigo=? AND almoxarifado_id=?');
    $ex->execute([$codigo,$almId]); $existente=$ex->fetch();
    if($existente){flash("Código \"$codigo\" já usado por \"{$existente['nome']}\" neste almoxarifado.",'danger');}
    else {
        db()->prepare('INSERT INTO item (nome,codigo,unidade,quantidade,estoque_minimo,almoxarifado_id,categoria,ca) VALUES (?,?,?,?,?,?,?,?)')->execute([trim($_POST['nome']),$codigo,trim($_POST['unidade']),(float)($_POST['quantidade']??0),(float)($_POST['estoque_minimo']??0),$almId,$_POST['categoria']??'geral',trim($_POST['ca']??'')?:null]);
        flash('Item cadastrado!','success'); redirect("/almoxarifado/$almId");
    }
}
$pageTitle='Novo Item'; $activeMenu='almoxarifado';
ob_start(); require VIEWS_PATH.'/itens/form.php';
$content=ob_get_clean(); require VIEWS_PATH.'/layouts/base.php';
