<?php
requer_almoxarife();csrf_check();$id=(int)($params["id"]??0);$u=usuario_atual();
$st=db()->prepare("SELECT * FROM item_epi WHERE id=?");$st->execute([$id]);$e=$st->fetch();
if(!$e||!usuario_tem_acesso_almoxarifado((int)$e["almoxarifado_id"])){json_response(["error"=>"Acesso negado"],403);}
$resp=trim($_POST["responsavel"]??"");
db()->prepare("UPDATE item_epi SET status=?,responsavel_atual=? WHERE id=?")->execute(["em_uso",$resp,$id]);
db()->prepare("INSERT INTO historico_epi (item_epi_id,colaborador,data_saida,registrado_por,tipo_evento) VALUES (?,?,NOW(),?,?)")->execute([$id,$resp,$u["nome"],"uso"]);
json_response(["status"=>"em_uso","responsavel"=>$resp]);