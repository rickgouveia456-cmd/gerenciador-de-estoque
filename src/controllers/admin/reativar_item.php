<?php
requer_admin();
csrf_check();
$id = (int)($params['id'] ?? 0);
$stmt = db()->prepare('UPDATE item SET ativo=1 WHERE id=?');
$stmt->execute([$id]);
flash('Item reativado.', 'success');
$ref = $_SERVER['HTTP_REFERER'] ?? '/admin/reativar_itens';
redirect($ref ?: '/admin/reativar_itens');
