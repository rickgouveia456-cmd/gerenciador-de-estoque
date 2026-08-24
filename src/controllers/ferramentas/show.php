<?php
requer_login(); $id=(int)($params['id']??0); $u=usuario_atual();
$st=db()->prepare('SELECT f.*,a.nome AS alm_nome FROM ferramenta f JOIN almoxarifado a ON a.id=f.almoxarifado_id WHERE f.id=?');
$st->execute([$id]);$f=$st->fetch();
if(!$f||!usuario_tem_acesso_almoxarifado((int)$f['almoxarifado_id'])){flash('Acesso negado.','danger');redirect('/ferramentas');}
$hist=db()->prepare('SELECT * FROM historico_ferramenta WHERE ferramenta_id=? ORDER BY data_saida DESC LIMIT 20');
$hist->execute([$id]);$historico=$hist->fetchAll();
json_response(['ferramenta'=>$f['nome'],'id'=>$f['identificacao'],'empresa'=>$f['empresa']??'','historico'=>array_map(fn($h)=>['colaborador'=>$h['colaborador'],'data_saida'=>fmt_data($h['data_saida']),'data_devolucao'=>$h['data_devolucao']?fmt_data($h['data_devolucao']):null,'registrado_por'=>$h['registrado_por']??'—','tipo_evento'=>$h['tipo_evento']??'uso','motivo_manutencao'=>$h['motivo_manutencao']??''],$historico)]);
