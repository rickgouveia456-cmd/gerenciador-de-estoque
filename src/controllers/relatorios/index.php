<?php
requer_login(); $u=usuario_atual();
$pageTitle='Relatórios';$activeMenu='relatorios';
ob_start();require VIEWS_PATH.'/relatorios/index.php';
$content=ob_get_clean();require VIEWS_PATH.'/layouts/base.php';
