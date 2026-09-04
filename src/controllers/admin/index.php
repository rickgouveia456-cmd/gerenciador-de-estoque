<?php
requer_admin();
$totalUsuarios=(int)db()->query('SELECT COUNT(*) FROM usuario WHERE ativo=1')->fetchColumn();
$totalAlm=(int)db()->query('SELECT COUNT(*) FROM almoxarifado')->fetchColumn();
$totalItens=(int)db()->query('SELECT COUNT(*) FROM item WHERE ativo=1')->fetchColumn();
$totalMov=(int)db()->query('SELECT COUNT(*) FROM movimentacao')->fetchColumn();
$pageTitle='Painel Admin';$activeMenu='admin';
ob_start();require VIEWS_PATH.'/admin/index.php';
$content=ob_get_clean();require VIEWS_PATH.'/layouts/base.php';
