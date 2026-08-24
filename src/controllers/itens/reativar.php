<?php
requer_login(); csrf_check(); $id=(int)($params['id']??0); $u=usuario_atual();
$st=db()->prepare('SELECT * FROM item WHERE id=?'); $st->execute([$id]); $it=$st->fetch();
if(!$it||!usuario_tem_acesso_almoxarifado((int)$it['almoxarifado_id'])){flash('Acesso negado.','danger');redirect('/');}
db()->prepare('UPDATE item SET ativo=1 WHERE id=?')->execute([$id]);
flash("Item \"{$it['nome']}\" reativado.",'success');
redirect("/item/$id");
