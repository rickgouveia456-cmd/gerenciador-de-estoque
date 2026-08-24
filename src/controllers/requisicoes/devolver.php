<?php
requer_login(); csrf_check(); $id=(int)($params['id']??0); $u=usuario_atual();
$st=db()->prepare('SELECT r.*,i.almoxarifado_id,i.nome AS item_nome,i.unidade FROM requisicao r JOIN item i ON i.id=r.item_id WHERE r.id=?');
$st->execute([$id]); $r=$st->fetch();
if(!$r){http_response_code(404);exit;}
if($u['perfil']!=='admin'&&!usuario_tem_acesso_almoxarifado((int)$r['almoxarifado_id'])){flash('Acesso negado.','danger');redirect('/requisicoes');}
if($r['status']==='aberta'){
    db()->prepare('UPDATE requisicao SET status=?,data_devolucao=NOW() WHERE id=?')->execute(['devolvida',$id]);
    db()->prepare('UPDATE item SET quantidade=quantidade+? WHERE id=?')->execute([$r['quantidade'],$r['item_id']]);
    db()->prepare('INSERT INTO movimentacao (tipo,quantidade,responsavel,observacao,item_id) VALUES (?,?,?,?,?)')->execute(['entrada',$r['quantidade'],$r['colaborador'],"Devolução req #$id",$r['item_id']]);
    flash("Devolução de \"{$r['item_nome']}\" registrada!",'success');
}
redirect('/requisicoes');
