<?php
requer_admin();
$pageTitle='Backup';$activeMenu='backup';
ob_start();require VIEWS_PATH.'/admin/backup.php';
$content=ob_get_clean();require VIEWS_PATH.'/layouts/base.php';
