<?php
requer_almoxarife(); $id=(int)($params['id']??0); $u=usuario_atual();
$st=db()->prepare('SELECT * FROM ferramenta WHERE id=?');$st->execute([$id]);$f=$st->fetch();
if(!$f||!usuario_tem_acesso_almoxarifado((int)$f['almoxarifado_id'])){flash('Acesso negado.','danger');redirect('/ferramentas');}
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    db()->prepare('UPDATE ferramenta SET identificacao=?,nome=?,empresa=?,local=?,observacao=? WHERE id=?')->execute([trim($_POST['identificacao']??''),trim($_POST['nome']??''),trim($_POST['empresa']??'')?:null,trim($_POST['local']??'')?:null,trim($_POST['observacao']??'')?:null,$id]);
    flash('Ferramenta atualizada!','success');redirect("/ferramentas?alm={$f['almoxarifado_id']}");
}
$almId=$f['almoxarifado_id'];$stA=db()->prepare('SELECT * FROM almoxarifado WHERE id=?');$stA->execute([$almId]);$alm=$stA->fetch();
$pageTitle='Editar Ferramenta';$activeMenu='ferramentas';
ob_start();require VIEWS_PATH.'/ferramentas/form.php';
$content=ob_get_clean();require VIEWS_PATH.'/layouts/base.php';
