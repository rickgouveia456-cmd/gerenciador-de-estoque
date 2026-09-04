<?php
requer_login(); csrf_check(); $id=(int)($params['id']??0); $u=usuario_atual();
if(!in_array($u['perfil'],['admin','almoxarife'])){json_response(['error'=>'Acesso negado'],403);}
$st=db()->prepare('SELECT devolvido FROM movimentacao WHERE id=?'); $st->execute([$id]); $m=$st->fetch();
if(!$m){http_response_code(404);exit;}
$novo=$m['devolvido']?0:1;
db()->prepare('UPDATE movimentacao SET devolvido=? WHERE id=?')->execute([$novo,$id]);
json_response(['devolvido'=>(bool)$novo]);
