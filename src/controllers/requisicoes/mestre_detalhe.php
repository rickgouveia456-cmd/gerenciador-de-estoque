<?php
requer_login(); $id=(int)($params['id']??0); $u=usuario_atual();
$st=db()->prepare('SELECT rm.*,u.nome AS mestre_nome,a.nome AS alm_nome,e.nome AS entregue_nome FROM requisicao_mestre rm JOIN usuario u ON u.id=rm.mestre_id JOIN almoxarifado a ON a.id=rm.almoxarifado_id LEFT JOIN usuario e ON e.id=rm.entregue_por_id WHERE rm.id=?');
$st->execute([$id]); $req=$st->fetch();
if(!$req){http_response_code(404);exit;}
if(in_array($u['perfil'],['mestre','tecnico_seguranca','colaborador'])&&$req['mestre_id']!=$u['id']){flash('Acesso negado.','danger');redirect('/requisicoes/mestre');}
if($u['perfil']==='almoxarife'&&$req['almoxarifado_id']!=$u['almoxarifado_id']){flash('Acesso negado.','danger');redirect('/requisicoes/mestre');}
$stI=db()->prepare('SELECT rmi.*,i.nome AS item_nome,i.unidade,i.quantidade AS estoque_atual FROM requisicao_mestre_item rmi JOIN item i ON i.id=rmi.item_id WHERE rmi.requisicao_id=?');
$stI->execute([$id]); $itens=$stI->fetchAll();
$pageTitle="Req #{$req['id']}"; $activeMenu='req_mestre';
ob_start(); require VIEWS_PATH.'/requisicoes/mestre_detalhe.php';
$content=ob_get_clean(); require VIEWS_PATH.'/layouts/base.php';
