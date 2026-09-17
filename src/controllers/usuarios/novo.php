<?php
requer_login();
$uAtual = usuario_atual();
if (!in_array($uAtual['perfil'], ['admin', 'ggo'])) {
    flash('Acesso restrito.', 'danger'); redirect('/');
}

// GGO vê só almoxarifados da sua cidade; admin vê todos
$almoxarifados = ($uAtual['perfil'] === 'admin')
    ? db()->query('SELECT * FROM almoxarifado ORDER BY nome')->fetchAll()
    : (function() {
        $ids = almoxarifados_permitidos_ids();
        if (!$ids) return [];
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $s = db()->prepare("SELECT * FROM almoxarifado WHERE id IN ($ph) ORDER BY nome");
        $s->execute($ids);
        return $s->fetchAll();
    })();

$permissoesDisp = ['fazer_requisicao'=>'Fazer Requisições','ver_relatorios'=>'Ver Relatórios','ver_alertas'=>'Ver Alertas'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $login = trim($_POST['login'] ?? '');
    $novoPerfil = $_POST['perfil'] ?? 'colaborador';

    // GGO não pode criar admin ou outro GGO
    if ($uAtual['perfil'] === 'ggo' && in_array($novoPerfil, ['admin', 'ggo'])) {
        flash('GGO não pode criar perfis Admin ou GGO.', 'danger');
        redirect('/usuarios/novo');
    }

    $ex = db()->prepare('SELECT id FROM usuario WHERE login=?');
    $ex->execute([$login]);
    if ($ex->fetch()) {
        flash("Login \"$login\" já em uso.", 'danger');
    } elseif (strlen($_POST['senha'] ?? '') < 8) {
        flash('Senha mínima 8 caracteres.', 'danger');
    } else {
        $hash = password_hash($_POST['senha'], PASSWORD_BCRYPT);
        db()->prepare(
            'INSERT INTO usuario (nome,login,senha_hash,perfil,almoxarifado_id,email,pode_requisitar,pode_ver_alertas) VALUES (?,?,?,?,?,?,?,?)'
        )->execute([
            trim($_POST['nome']),
            $login,
            $hash,
            $novoPerfil,
            $_POST['almoxarifado_id'] ?? null ?: null,
            trim($_POST['email'] ?? '') ?: null,
            isset($_POST['pode_requisitar']) ? 1 : 0,
            isset($_POST['pode_ver_alertas']) ? 1 : 0,
        ]);
        flash('Usuário criado!', 'success');
        redirect('/usuarios');
    }
}

$pageTitle = 'Novo Usuário';
$activeMenu = 'usuarios';
$u2 = null;
ob_start();
require VIEWS_PATH . '/usuarios/form.php';
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/base.php';
