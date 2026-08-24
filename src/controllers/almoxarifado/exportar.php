<?php
requer_login(); $id=(int)($params['id']??0); $u=usuario_atual();
if(!usuario_tem_acesso_almoxarifado($id)){flash('Acesso negado.','danger');redirect('/');}
$stA=db()->prepare('SELECT * FROM almoxarifado WHERE id=?');$stA->execute([$id]);$alm=$stA->fetch();
$st=db()->prepare('SELECT * FROM item WHERE almoxarifado_id=? ORDER BY nome');$st->execute([$id]);$itens=$st->fetchAll();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="estoque_'.date('Y-m-d').'.csv"');
$f=fopen('php://output','w');
fprintf($f,chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
fputcsv($f,['Codigo','Nome','Categoria','Unidade','Quantidade','Estoque Minimo','Status','Valor Unit.'],';');
foreach($itens as $it){
    $st2=status_item((float)$it['quantidade'],(float)$it['estoque_minimo']);
    fputcsv($f,[$it['codigo'],$it['nome'],categoria_label($it['categoria']??'geral'),$it['unidade'],fmt_qtd((float)$it['quantidade']),fmt_qtd((float)$it['estoque_minimo']),$st2,$it['valor_unitario']??''],';');
}
fclose($f);exit;
