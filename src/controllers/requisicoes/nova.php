<?php
requer_login(); $u=usuario_atual();
$ids=almoxarifados_permitidos_ids();
$almoxarifados=$u['perfil']==='admin' ? db()->query('SELECT * FROM almoxarifado ORDER BY nome')->fetchAll() :
    ($ids?(function($ids){$ph=implode(',',array_fill(0,count($ids),'?'));$s=db()->prepare("SELECT * FROM almoxarifado WHERE id IN ($ph) ORDER BY nome");$s->execute($ids);return $s->fetchAll();})($ids):[]);
$itensJson=[];
foreach($almoxarifados as $alm){
    $s=db()->prepare('SELECT id,nome,quantidade,unidade FROM item WHERE almoxarifado_id=? AND ativo=1 AND quantidade>0 ORDER BY nome');
    $s->execute([$alm['id']]); $itensJson[$alm['id']]=$s->fetchAll();
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $colab=trim($_POST['colaborador']??''); $obs=trim($_POST['observacao']??''); $criados=0;
    $indices=[];
    foreach($_POST as $k=>$_){ if(preg_match('/^item_id_(\d+)$/',$k,$m)) $indices[]=(int)$m[1]; }
    sort($indices);
    foreach($indices as $i){
        $itemId=(int)($_POST["item_id_$i"]??0); $qtd=(float)($_POST["quantidade_$i"]??0);
        if(!$itemId||$qtd<=0) continue;
        $st=db()->prepare('SELECT * FROM item WHERE id=? AND ativo=1'); $st->execute([$itemId]); $it=$st->fetch();
        if(!$it||$qtd>(float)$it['quantidade']){flash("Estoque insuficiente: {$it['nome']}",'danger');continue;}
        db()->prepare('UPDATE item SET quantidade=quantidade-? WHERE id=?')->execute([$qtd,$itemId]);
        db()->prepare('INSERT INTO requisicao (colaborador,observacao,quantidade,item_id) VALUES (?,?,?,?)')->execute([$colab,$obs,$qtd,$itemId]);
        db()->prepare('INSERT INTO movimentacao (tipo,quantidade,responsavel,observacao,item_id) VALUES (?,?,?,?,?)')->execute(['saida',$qtd,$colab,"Requisição — $obs",$itemId]);
        $criados++;
    }
    if($criados) flash("$criados item(ns) retirado(s) para $colab.",'success');
    redirect('/requisicoes');
}
$pageTitle='Nova Requisição'; $activeMenu='requisicoes';
ob_start(); require VIEWS_PATH.'/requisicoes/nova.php';
$content=ob_get_clean(); require VIEWS_PATH.'/layouts/base.php';
