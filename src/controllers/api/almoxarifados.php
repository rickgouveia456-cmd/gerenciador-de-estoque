<?php
requer_login(); $u=usuario_atual();
$ids=almoxarifados_permitidos_ids();
$alms=$u['perfil']==='admin'?db()->query('SELECT * FROM almoxarifado ORDER BY nome')->fetchAll():
    ($ids?(function($ids){$ph=implode(',',array_fill(0,count($ids),'?'));$s=db()->prepare("SELECT * FROM almoxarifado WHERE id IN ($ph) ORDER BY nome");$s->execute($ids);return $s->fetchAll();})($ids):[]);
json_response($alms);
