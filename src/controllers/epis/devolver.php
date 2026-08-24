<?php
requer_almoxarife();csrf_check();$id=(int)($params["id"]??0);$u=usuario_atual();
$st=db()->prepare("SELECT * FROM item_epi WHERE id=?");$st->execute([$id]);$e=$st->fetch();
if(!$e||!usuario_tem_acesso_almoxarifado((int)$e["almoxarifado_id"])){json_response(["error"=>"Acesso negado"],403);}
db()->prepare("UPDATE historico_epi SET data_devolucao=NOW() WHERE item_epi_id=? AND data_devolucao IS NULL")->execute([$id]);
db()->prepare("UPDATE item_epi SET status=?,responsavel_atual=NULL WHERE id=?")->execute(["disponivel",$id]);
json_response(["status"=>"disponivel","responsavel"=>""]);