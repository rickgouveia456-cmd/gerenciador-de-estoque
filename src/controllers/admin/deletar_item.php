<?php
requer_admin();
csrf_check();
$id = (int)($params['id'] ?? 0);
// Hard delete — so admin pode excluir permanentemente
$stmtIt = db()->prepare('SELECT almoxarifado_id FROM item WHERE id=?');
$stmtIt->execute([$id]);
$it = $stmtIt->fetch();
db()->prepare('DELETE FROM item WHERE id=?')->execute([$id]);
flash('Item excluido permanentemente.', 'warning');
redirect($it ? "/almoxarifado/{$it['almoxarifado_id']}" : '/');
