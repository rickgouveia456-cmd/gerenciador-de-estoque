<?php
requer_login();
$id = (int)($params['id']??0); $u=usuario_atual();
$st = db()->prepare('SELECT i.*,a.nome AS alm_nome FROM item i JOIN almoxarifado a ON a.id=i.almoxarifado_id WHERE i.id=?');
$st->execute([$id]); $it=$st->fetch();
if (!$it){http_response_code(404);exit;}
if (!usuario_tem_acesso_almoxarifado((int)$it['almoxarifado_id'])){flash('Acesso negado.','danger');redirect('/');}
$movs=db()->prepare('SELECT * FROM movimentacao WHERE item_id=? ORDER BY data DESC LIMIT 50');
$movs->execute([$id]); $movimentacoes=$movs->fetchAll();
$pageTitle=h($it['nome']); $activeMenu='almoxarifado'; $activeAlmId=$it['almoxarifado_id'];
ob_start(); require VIEWS_PATH.'/itens/show.php';
$content=ob_get_clean(); require VIEWS_PATH.'/layouts/base.php';
