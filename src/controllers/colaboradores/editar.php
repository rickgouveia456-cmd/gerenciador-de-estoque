<?php
requer_login(); $id=(int)($params['id']??0); $u=usuario_atual();
if(!in_array($u['perfil'],['admin','almoxarife','analista'])){flash('Acesso negado.','danger');redirect('/');}
$st=db()->prepare('SELECT * FROM colaborador WHERE id=?');$st->execute([$id]);$c=$st->fetch();if(!$c){http_response_code(404);exit;}
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    db()->prepare('UPDATE colaborador SET nome=?,funcao=?,escopo=?,obra=?,cidade=?,tipo=?,ativo=? WHERE id=?')->execute([trim($_POST['nome']),trim($_POST['funcao']??'')?:null,trim($_POST['escopo']??'')?:null,trim($_POST['obra']??'')?:null,trim($_POST['cidade']??'')?:null,trim($_POST['tipo']??'peao'),isset($_POST['ativo'])?1:0,$id]);
    flash('Colaborador atualizado!','success');redirect('/colaboradores');
}
$pageTitle='Editar Colaborador';$activeMenu='colaboradores';
ob_start();require VIEWS_PATH.'/colaboradores/form.php';
$content=ob_get_clean();require VIEWS_PATH.'/layouts/base.php';
