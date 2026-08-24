<?php
requer_login(); csrf_check(); $id=(int)($params['id']??0); $u=usuario_atual();
if(!in_array($u['perfil'],['admin','almoxarife'])){flash('Acesso negado.','danger');redirect("/requisicoes/mestre/$id");}
$st=db()->prepare('SELECT * FROM requisicao_mestre WHERE id=?'); $st->execute([$id]); $req=$st->fetch();
if(!$req||!in_array($req['status'],['pendente','aprovada','parcial'])){flash('Já processada.','warning');redirect("/requisicoes/mestre/$id");}
$stI=db()->prepare("SELECT rmi.*,i.nome AS item_nome,i.unidade,i.quantidade AS estoq FROM requisicao_mestre_item rmi JOIN item i ON i.id=rmi.item_id WHERE rmi.requisicao_id=? AND rmi.status_item IN ('aprovado','pendente')");
$stI->execute([$id]); $itens=$stI->fetchAll();
foreach($itens as $ri){
    if((float)$ri['quantidade']>(float)$ri['estoq']){flash("Estoque insuficiente: {$ri['item_nome']}",'danger');redirect("/requisicoes/mestre/$id");}
}
foreach($itens as $ri){
    db()->prepare('UPDATE item SET quantidade=quantidade-? WHERE id=?')->execute([$ri['quantidade'],$ri['item_id']]);
    db()->prepare('UPDATE requisicao_mestre_item SET status_item=? WHERE id=?')->execute(['aprovado',$ri['id']]);
    db()->prepare('INSERT INTO movimentacao (tipo,quantidade,responsavel,observacao,item_id) VALUES (?,?,?,?,?)')->execute(['saida',$ri['quantidade'],$req['colaborador'],"Req. Mestre #$id",$ri['item_id']]);
}
db()->prepare('UPDATE requisicao_mestre SET status=?,data_entrega=NOW(),entregue_por_id=? WHERE id=?')->execute(['entregue',$u['id'],$id]);
flash('Entrega confirmada! Estoque atualizado.','success');
redirect("/requisicoes/mestre/$id");
