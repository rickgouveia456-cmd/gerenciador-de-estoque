<?php
requer_almoxarife(); csrf_check(); $id=(int)($params['id']??0); $u=usuario_atual();
$st=db()->prepare('SELECT * FROM ferramenta WHERE id=?');$st->execute([$id]);$f=$st->fetch();
if(!$f||!usuario_tem_acesso_almoxarifado((int)$f['almoxarifado_id'])){json_response(['error'=>'Acesso negado'],403);}
db()->prepare("UPDATE historico_ferramenta SET data_devolucao=NOW() WHERE ferramenta_id=? AND data_devolucao IS NULL")->execute([$id]);
db()->prepare('UPDATE ferramenta SET status=?,responsavel_atual=NULL,data_saida=NULL WHERE id=?')->execute(['disponivel',$id]);
json_response(['status'=>'disponivel','responsavel'=>'']);
