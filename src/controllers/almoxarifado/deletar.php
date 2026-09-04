<?php
requer_admin(); csrf_check();
$id = (int)($params['id'] ?? 0);
db()->prepare('UPDATE usuario SET almoxarifado_id=NULL WHERE almoxarifado_id=?')->execute([$id]);
db()->prepare('DELETE FROM acesso_extra WHERE almoxarifado_id=?')->execute([$id]);
db()->prepare('DELETE FROM almoxarifado WHERE id=?')->execute([$id]);
flash('Almoxarifado removido.','warning');
redirect('/');
