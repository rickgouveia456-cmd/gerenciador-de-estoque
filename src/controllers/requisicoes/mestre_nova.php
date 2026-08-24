<?php
requer_login(); $u=usuario_atual();
$podeFazer=in_array($u['perfil'],['mestre','tecnico_seguranca','admin','almoxarife'])||$u['pode_requisitar'];
if(!$podeFazer){flash('Sem permissao.','danger');redirect('/');}
if($u['perfil']==='admin'){ $almoxarifados=db()->query('SELECT * FROM almoxarifado ORDER BY nome')->fetchAll(); }
elseif($u['perfil']==='tecnico_seguranca'){ $ids=almoxarifados_permitidos_ids(); $ph=implode(',',array_fill(0,count($ids),'?')); $s=db()->prepare("SELECT * FROM almoxarifado WHERE id IN ($ph)"); $s->execute($ids); $almoxarifados=$s->fetchAll(); }
else { if(!$u['almoxarifado_id']){flash('Sem almoxarifado vinculado.','warning');redirect('/requisicoes/mestre');} $s=db()->prepare('SELECT * FROM almoxarifado WHERE id=?'); $s->execute([$u['almoxarifado_id']]); $almoxarifados=[$s->fetch()]; }
$itensJson=[];
foreach($almoxarifados as $alm){
    $s=db()->prepare('SELECT id,nome,quantidade,unidade,categoria FROM item WHERE almoxarifado_id=? AND ativo=1 AND quantidade>0 ORDER BY nome');
    $s->execute([$alm['id']]); $list=$s->fetchAll();
    if($u['perfil']==='mestre') $list=array_filter($list,fn($i)=>$i['categoria']!=='epi');
    $itensJson[$alm['id']]=array_values($list);
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $colab=trim($_POST['colaborador']??''); $almId=(int)($_POST['almoxarifado_id']??$u['almoxarifado_id']??0); $obs=trim($_POST['observacao']??'');
    if(!$colab){flash('Informe o colaborador.','warning');redirect('/requisicoes/mestre/nova');}
    $indices=[]; foreach($_POST as $k=>$_){if(preg_match('/^item_id_(\d+)$/',$k,$m)) $indices[]=(int)$m[1];} sort($indices);
    if(!$indices){flash('Adicione pelo menos um item.','warning');redirect('/requisicoes/mestre/nova');}
    $stmt=db()->prepare('INSERT INTO requisicao_mestre (mestre_id,colaborador,almoxarifado_id,observacao,status,data_criacao) VALUES (?,?,?,?,?,NOW())');
    $stmt->execute([$u['id'],$colab,$almId,$obs,'pendente']);
    $reqId=(int)db()->lastInsertId();
    $proto='REQ-'.date('Ymd').'-'.str_pad($reqId,4,'0',STR_PAD_LEFT);
    db()->prepare('UPDATE requisicao_mestre SET protocolo=? WHERE id=?')->execute([$proto,$reqId]);
    foreach($indices as $i){
        $itemId=(int)($_POST["item_id_$i"]??0); $qtd=(float)($_POST["quantidade_$i"]??0); $obsI=trim($_POST["observacao_$i"]??'');
        if(!$itemId||$qtd<=0) continue;
        db()->prepare('INSERT INTO requisicao_mestre_item (requisicao_id,item_id,quantidade,observacao) VALUES (?,?,?,?)')->execute([$reqId,$itemId,$qtd,$obsI]);
    }
    flash("Requisição $proto enviada!",'success'); redirect('/requisicoes/mestre');
}
$pageTitle='Nova Requisição'; $activeMenu='req_mestre';
ob_start(); require VIEWS_PATH.'/requisicoes/mestre_nova.php';
$content=ob_get_clean(); require VIEWS_PATH.'/layouts/base.php';
