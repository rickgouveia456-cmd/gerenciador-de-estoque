<?php
requer_login(); csrf_check(); $id=(int)($params['id']??0);
$st=db()->prepare('SELECT * FROM item WHERE id=?'); $st->execute([$id]); $it=$st->fetch();
if(!$it||!usuario_tem_acesso_almoxarifado((int)$it['almoxarifado_id'])){http_response_code(403);exit;}
$sc=$_POST['status_compra']??'pendente';
db()->prepare('UPDATE item SET status_compra=? WHERE id=?')->execute([$sc,$id]);
http_response_code(204); exit;
