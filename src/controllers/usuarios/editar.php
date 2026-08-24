<?php
requer_admin(); $id=(int)($params['id']??0); $atual=usuario_atual();
$st=db()->prepare('SELECT * FROM usuario WHERE id=?');$st->execute([$id]);$u2=$st->fetch();if(!$u2){http_response_code(404);exit;}
$almoxarifados=db()->query('SELECT * FROM almoxarifado ORDER BY nome')->fetchAll();
$permissoesDisp=['fazer_requisicao'=>'Fazer Requisições','ver_relatorios'=>'Ver Relatórios','ver_alertas'=>'Ver Alertas'];
$stP=db()->prepare('SELECT * FROM permissao_extra WHERE usuario_id=?');$stP->execute([$id]);$permissoesExtra=$stP->fetchAll();
$stA=db()->prepare('SELECT ae.*,alm.nome AS alm_nome FROM acesso_extra ae JOIN almoxarifado alm ON alm.id=ae.almoxarifado_id WHERE ae.usuario_id=?');$stA->execute([$id]);$acessosExtra=$stA->fetchAll();
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $novoPerfil=$_POST['perfil']??'colaborador';
    if($u2['id']==$atual['id']&&$novoPerfil!=='admin'){flash('Não pode remover próprio perfil admin.','danger');redirect("/usuarios/$id/editar");}
    $ativo=isset($_POST['ativo'])?1:0;
    if($u2['id']==$atual['id']) $ativo=1;
    db()->prepare('UPDATE usuario SET nome=?,login=?,perfil=?,almoxarifado_id=?,email=?,ativo=?,pode_requisitar=?,pode_ver_alertas=? WHERE id=?')->execute([trim($_POST['nome']),trim($_POST['login']),$novoPerfil,$_POST['almoxarifado_id']??null?:null,trim($_POST['email']??'')?:null,$ativo,isset($_POST['pode_requisitar'])?1:0,isset($_POST['pode_ver_alertas'])?1:0,$id]);
    if(!empty($_POST['senha'])){ if(strlen($_POST['senha'])<8){flash('Senha mínima 8 chars.','danger');redirect("/usuarios/$id/editar");}
        db()->prepare('UPDATE usuario SET senha_hash=? WHERE id=?')->execute([password_hash($_POST['senha'],PASSWORD_BCRYPT),$id]); }
    flash('Usuário atualizado!','success');redirect('/usuarios');
}
$pageTitle='Editar Usuário';$activeMenu='usuarios';
ob_start();require VIEWS_PATH.'/usuarios/form.php';
$content=ob_get_clean();require VIEWS_PATH.'/layouts/base.php';
