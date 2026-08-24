<?php
requer_login(); csrf_check(); $id=(int)($params['id']??0); $u=usuario_atual();
$st=db()->prepare('SELECT * FROM requisicao_mestre WHERE id=?');$st->execute([$id]);$req=$st->fetch();
if(!$req){http_response_code(404);exit;}
if(in_array($u['perfil'],['mestre','tecnico_seguranca','colaborador'])&&$req['mestre_id']!=$u['id']){flash('Acesso negado.','danger');redirect('/requisicoes/mestre');}
if($req['status']==='entregue'){flash('Não pode cancelar entregue.','warning');redirect("/requisicoes/mestre/$id");}
db()->prepare('UPDATE requisicao_mestre SET status=? WHERE id=?')->execute(['cancelada',$id]);
flash("Requisição #$id cancelada.",'warning');redirect('/requisicoes/mestre');
