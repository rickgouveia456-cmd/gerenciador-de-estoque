<?php
requer_admin(); csrf_check(); $id=(int)($params['id']??0);
db()->prepare('UPDATE catalogo_insumo SET ativo=0 WHERE id=?')->execute([$id]);
flash('Insumo removido.','warning');redirect('/catalogo');
