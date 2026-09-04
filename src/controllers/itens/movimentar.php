<?php
requer_login(); csrf_check(); $id=(int)($params['id']??0); $u=usuario_atual();
$st=db()->prepare('SELECT * FROM item WHERE id=?'); $st->execute([$id]); $it=$st->fetch();
if(!$it||!usuario_tem_acesso_almoxarifado((int)$it['almoxarifado_id'])){flash('Acesso negado.','danger');redirect("/item/$id");}
$tipo=$_POST['tipo']??'saida'; $qtd=(float)$_POST['quantidade'];
$resp=trim($_POST['responsavel']??''); $obs=trim($_POST['observacao']??'');
if($tipo==='saida'&&$qtd>(float)$it['quantidade']){flash('Quantidade insuficiente!','danger');redirect("/item/$id");}
$nova=$tipo==='entrada'?round((float)$it['quantidade']+$qtd,4):round((float)$it['quantidade']-$qtd,4);
db()->prepare('UPDATE item SET quantidade=? WHERE id=?')->execute([$nova,$id]);
db()->prepare('INSERT INTO movimentacao (tipo,quantidade,responsavel,observacao,item_id) VALUES (?,?,?,?,?)')->execute([$tipo,$qtd,$resp,$obs,$id]);
flash(($tipo==='entrada'?'Entrada':'Saida')." de $qtd {$it['unidade']} registrada!",'success');
redirect("/item/$id");
