<?php
requer_admin(); csrf_check(); $id=(int)($params['id']??0); $atual=usuario_atual();
if($id==$atual['id']){flash('Não pode remover própria conta.','danger');redirect('/usuarios');}
db()->prepare('UPDATE requisicao_mestre SET mestre_id=? WHERE mestre_id=?')->execute([$atual['id'],$id]);
db()->prepare('DELETE FROM usuario WHERE id=?')->execute([$id]);
flash('Usuário removido.','warning');redirect('/usuarios');
