<?php
requer_almoxarife(); $u=usuario_atual();
$almId=(int)($_GET['alm']??0);
$st=db()->prepare('SELECT * FROM almoxarifado WHERE id=?');$st->execute([$almId]);$alm=$st->fetch();
if(!$alm||!usuario_tem_acesso_almoxarifado($almId)){flash('Acesso negado.','danger');redirect('/ferramentas');}
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $id=trim($_POST['identificacao']??'');
    $ex=db()->prepare('SELECT id,nome,almoxarifado_id FROM ferramenta WHERE identificacao=? AND ativo=1');$ex->execute([$id]);$existente=$ex->fetch();
    if($existente){flash("ID \"$id\" já cadastrado: {$existente['nome']}",'danger');}
    else{
        db()->prepare('INSERT INTO ferramenta (identificacao,nome,empresa,almoxarifado_id,local,observacao) VALUES (?,?,?,?,?,?)')->execute([$id,trim($_POST['nome']??''),trim($_POST['empresa']??'')?:null,$almId,trim($_POST['local']??'')?:null,trim($_POST['observacao']??'')?:null]);
        flash('Ferramenta cadastrada!','success');redirect("/ferramentas?alm=$almId");
    }
}
$pageTitle='Nova Ferramenta';$activeMenu='ferramentas';
ob_start();require VIEWS_PATH.'/ferramentas/form.php';
$content=ob_get_clean();require VIEWS_PATH.'/layouts/base.php';
