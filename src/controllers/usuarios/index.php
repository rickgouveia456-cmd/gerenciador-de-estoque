<?php
requer_admin();
$todos=db()->query('SELECT u.*,a.nome AS alm_nome FROM usuario u LEFT JOIN almoxarifado a ON a.id=u.almoxarifado_id ORDER BY u.nome')->fetchAll();
$grupos=['admin'=>['label'=>'👑 Admin','usuarios'=>[]],'almoxarife'=>['label'=>'📦 Almoxarife','usuarios'=>[]],'mestre'=>['label'=>'🦺 Mestre de Obra','usuarios'=>[]],'tecnico_seguranca'=>['label'=>'🔒 Técnico de Segurança','usuarios'=>[]],'analista'=>['label'=>'📊 Analista','usuarios'=>[]],'colaborador'=>['label'=>'👔 Colaborador/Engenheiro','usuarios'=>[]]];
foreach($todos as $u2){ $perfil=$u2['perfil']; if(!isset($grupos[$perfil])) $perfil='colaborador'; $grupos[$perfil]['usuarios'][]=$u2; }
$almoxarifados=db()->query('SELECT * FROM almoxarifado ORDER BY nome')->fetchAll();
$permissoesDisp=['fazer_requisicao'=>'Fazer Requisições','ver_relatorios'=>'Ver Relatórios','ver_alertas'=>'Ver Alertas'];
$pageTitle='Usuários';$activeMenu='usuarios';
ob_start();require VIEWS_PATH.'/usuarios/index.php';
$content=ob_get_clean();require VIEWS_PATH.'/layouts/base.php';
