<?php
requer_login(); csrf_check(); $id=(int)($params['id']??0); $u=usuario_atual();
$st=db()->prepare('SELECT * FROM item WHERE id=?'); $st->execute([$id]); $it=$st->fetch();
if(!$it||!usuario_tem_acesso_almoxarifado((int)$it['almoxarifado_id'])){flash('Acesso negado.','danger');redirect('/');}
if((float)$it['quantidade']>0){
    db()->prepare('INSERT INTO movimentacao (tipo,quantidade,responsavel,observacao,item_id) VALUES (?,?,?,?,?)')->execute(['saida',$it['quantidade'],$u['nome'],"Item desativado - saldo: {$it['quantidade']} {$it['unidade']}",$id]);
    db()->prepare('UPDATE item SET quantidade=0 WHERE id=?')->execute([$id]);
}
db()->prepare('UPDATE item SET ativo=0 WHERE id=?')->execute([$id]);
flash("Item \"{$it['nome']}\" desativado.",'warning');
redirect("/item/$id");
