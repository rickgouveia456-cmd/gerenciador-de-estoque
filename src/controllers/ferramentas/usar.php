<?php
requer_almoxarife(); csrf_check(); $id=(int)($params['id']??0); $u=usuario_atual();
$st=db()->prepare('SELECT * FROM ferramenta WHERE id=?');$st->execute([$id]);$f=$st->fetch();
if(!$f||!usuario_tem_acesso_almoxarifado((int)$f['almoxarifado_id'])){json_response(['error'=>'Acesso negado'],403);}
$resp=trim($_POST['responsavel']??'');
db()->prepare('UPDATE ferramenta SET status=?,responsavel_atual=?,data_saida=NOW() WHERE id=?')->execute(['em_uso',$resp,$id]);
db()->prepare('INSERT INTO historico_ferramenta (ferramenta_id,colaborador,data_saida,registrado_por,tipo_evento) VALUES (?,?,NOW(),?,?)')->execute([$id,$resp,$u['nome'],'uso']);
$histId=(int)db()->lastInsertId();
json_response(['status'=>'em_uso','responsavel'=>$resp,'hist_id'=>$histId]);
