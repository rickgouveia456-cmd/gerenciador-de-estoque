<?php
requer_login(); csrf_check(); $id=(int)($params['id']??0); $u=usuario_atual();
if(!in_array($u['perfil'],['admin','ggo','almoxarife','analista'])){flash('Acesso negado.','danger');redirect('/');}
db()->prepare('UPDATE colaborador SET ativo=0 WHERE id=?')->execute([$id]);
flash('Colaborador desativado.','warning');redirect('/colaboradores');
