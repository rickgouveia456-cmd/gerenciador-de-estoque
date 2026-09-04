<?php
requer_almoxarife(); csrf_check();
$almId=(int)($params["alm_id"]??0); $kitId=(int)($params["kit_id"]??0);
if(!usuario_tem_acesso_almoxarifado($almId)){flash("Acesso negado.","danger");redirect("/");}
db()->prepare("UPDATE kit SET ativo=0 WHERE id=? AND almoxarifado_id=?")->execute([$kitId,$almId]);
flash("Kit removido.","warning"); redirect("/almoxarifado/$almId/kits");