<?php
requer_login(); csrf_check(); $id=(int)($params['id']??0);
$st=db()->prepare('SELECT * FROM item WHERE id=?'); $st->execute([$id]); $it=$st->fetch();
if(!$it||!usuario_tem_acesso_almoxarifado((int)$it['almoxarifado_id'])){json_response(['error'=>'Acesso negado'],403);}
$novo=$it['fixado']?0:1;
db()->prepare('UPDATE item SET fixado=? WHERE id=?')->execute([$novo,$id]);
json_response(['fixado'=>(bool)$novo]);
