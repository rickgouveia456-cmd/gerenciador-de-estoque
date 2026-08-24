<?php
requer_admin();
$almoxarifados=db()->query('SELECT * FROM almoxarifado ORDER BY nome')->fetchAll();
$permissoesDisp=['fazer_requisicao'=>'Fazer Requisições','ver_relatorios'=>'Ver Relatórios','ver_alertas'=>'Ver Alertas'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $login=trim($_POST['login']??'');
    $ex=db()->prepare('SELECT id FROM usuario WHERE login=?');$ex->execute([$login]);
    if($ex->fetch()){flash("Login \"$login\" já em uso.",'danger');}
    elseif(strlen($_POST['senha']??'')<8){flash('Senha mínima 8 caracteres.','danger');}
    else{
        $hash=password_hash($_POST['senha'],PASSWORD_BCRYPT);
        db()->prepare('INSERT INTO usuario (nome,login,senha_hash,perfil,almoxarifado_id,email,pode_requisitar,pode_ver_alertas) VALUES (?,?,?,?,?,?,?,?)')->execute([trim($_POST['nome']),$login,$hash,$_POST['perfil']??'colaborador',$_POST['almoxarifado_id']??null?:null,trim($_POST['email']??'')?:null,isset($_POST['pode_requisitar'])?1:0,isset($_POST['pode_ver_alertas'])?1:0]);
        flash('Usuário criado!','success');redirect('/usuarios');
    }
}
$pageTitle='Novo Usuário';$activeMenu='usuarios';$u2=null;
ob_start();require VIEWS_PATH.'/usuarios/form.php';
$content=ob_get_clean();require VIEWS_PATH.'/layouts/base.php';
