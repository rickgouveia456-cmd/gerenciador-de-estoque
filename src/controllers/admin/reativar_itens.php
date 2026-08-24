<?php
requer_admin();
$almId = (int)($_GET['alm'] ?? 0);

$sql   = 'SELECT i.*, a.nome AS alm_nome FROM item i JOIN almoxarifado a ON a.id=i.almoxarifado_id WHERE i.ativo=0';
$binds = [];
if ($almId) { $sql .= ' AND i.almoxarifado_id=?'; $binds[] = $almId; }
$sql .= ' ORDER BY a.nome, i.nome';
$stmt = db()->prepare($sql);
$stmt->execute($binds);
$itens = $stmt->fetchAll();

$almoxarifados = db()->query('SELECT id, nome FROM almoxarifado ORDER BY nome')->fetchAll();

$pageTitle  = 'Itens Desativados';
$activeMenu = 'admin';
ob_start(); require VIEWS_PATH . '/admin/reativar_itens.php';
$content = ob_get_clean(); require VIEWS_PATH . '/layouts/base.php';
