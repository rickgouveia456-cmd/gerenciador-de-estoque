<?php
requer_login(); csrf_check(); $id=(int)($params['id']??0); $u=usuario_atual();
$st=db()->prepare('SELECT * FROM item WHERE id=?'); $st->execute([$id]); $it=$st->fetch();
if(!$it){http_response_code(404);exit;}
$almId=$it['almoxarifado_id'];
if(!in_array($u['perfil'],['admin','almoxarife'])||!usuario_tem_acesso_almoxarifado((int)$almId)){flash('Sem permissao.','danger');redirect("/item/$id");}
db()->prepare('UPDATE item SET ativo=0 WHERE id=?')->execute([$id]);
flash('Item removido.','warning'); redirect("/almoxarifado/$almId");
