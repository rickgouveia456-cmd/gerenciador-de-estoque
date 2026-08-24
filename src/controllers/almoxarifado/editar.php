<?php
requer_login();
$id = (int)($params['id'] ?? 0);
$u  = usuario_atual();
$stmt = db()->prepare('SELECT * FROM almoxarifado WHERE id=?');
$stmt->execute([$id]); $alm = $stmt->fetch();
if (!$alm) { http_response_code(404); exit; }
if (!usuario_tem_acesso_almoxarifado($id)) { flash('Acesso negado.','danger'); redirect('/'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    db()->prepare('UPDATE almoxarifado SET nome=?,descricao=?,obra=?,cidade=? WHERE id=?')
       ->execute([trim($_POST['nome']),trim($_POST['descricao']??''),trim($_POST['obra']??'')?:null,trim($_POST['cidade']??'')?:null,$id]);
    flash('Almoxarifado atualizado!','success');
    redirect("/almoxarifado/$id");
}
$pageTitle='Editar Almoxarifado'; $activeMenu='admin'; $activeAlmId=$id;
ob_start(); require VIEWS_PATH.'/almoxarifado/form.php';
$content=ob_get_clean(); require VIEWS_PATH.'/layouts/base.php';
