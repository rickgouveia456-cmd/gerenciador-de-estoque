<?php
requer_almoxarife(); csrf_check(); $id=(int)($params['id']??0); $u=usuario_atual();
$st=db()->prepare('SELECT * FROM ferramenta WHERE id=?');$st->execute([$id]);$f=$st->fetch();
if(!$f||!usuario_tem_acesso_almoxarifado((int)$f['almoxarifado_id'])){json_response(['error'=>'Acesso negado'],403);}
$motivo=trim($_POST['motivo']??'');
db()->prepare("UPDATE historico_ferramenta SET data_devolucao=NOW() WHERE ferramenta_id=? AND data_devolucao IS NULL")->execute([$id]);
db()->prepare('UPDATE ferramenta SET status=?,responsavel_atual=? WHERE id=?')->execute(['manutencao',$motivo?:'Em manutenção',$id]);
db()->prepare('INSERT INTO historico_ferramenta (ferramenta_id,colaborador,data_saida,registrado_por,tipo_evento,motivo_manutencao) VALUES (?,?,NOW(),?,?,?)')->execute([$id,$u['nome'],$u['nome'],'manutencao',$motivo?:null]);
json_response(['status'=>'manutencao']);
