<?php
requer_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $stmt = db()->prepare('INSERT INTO almoxarifado (nome,descricao,obra,cidade) VALUES (?,?,?,?)');
    $stmt->execute([
        trim($_POST['nome']),
        trim($_POST['descricao'] ?? ''),
        trim($_POST['obra'] ?? '') ?: null,
        trim($_POST['cidade'] ?? '') ?: null,
    ]);
    flash('Almoxarifado criado!', 'success');
    redirect('/');
}
$pageTitle  = 'Novo Almoxarifado'; $activeMenu = 'admin';
ob_start(); require VIEWS_PATH . '/almoxarifado/form.php';
$content = ob_get_clean(); require VIEWS_PATH . '/layouts/base.php';
