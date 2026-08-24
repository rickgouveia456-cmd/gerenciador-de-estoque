<?php
requer_login(); $id=(int)($params['id']??0); $u=usuario_atual();
$st=db()->prepare('SELECT * FROM item WHERE id=?'); $st->execute([$id]); $it=$st->fetch();
if(!$it){http_response_code(404);exit;}
if(!in_array($u['perfil'],['admin','almoxarife'])||!usuario_tem_acesso_almoxarifado((int)$it['almoxarifado_id'])){flash('Acesso negado.','danger');redirect("/item/$id");}
$ids=almoxarifados_permitidos_ids();
$almoxarifados=$u['perfil']==='admin' ? db()->query('SELECT * FROM almoxarifado ORDER BY nome')->fetchAll() :
    ($ids ? (function($ids){ $ph=implode(',',array_fill(0,count($ids),'?')); $s=db()->prepare("SELECT * FROM almoxarifado WHERE id IN ($ph) ORDER BY nome"); $s->execute($ids); return $s->fetchAll(); })($ids) : []);
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $novaQtd=(float)$_POST['quantidade']; $diff=$novaQtd-(float)$it['quantidade'];
    if($diff!=0){
        $tipo=$diff>0?'entrada':'saida';
        db()->prepare('INSERT INTO movimentacao (tipo,quantidade,responsavel,observacao,item_id) VALUES (?,?,?,?,?)')->execute([$tipo,abs($diff),$u['nome'],"Ajuste manual: {$it['quantidade']} → $novaQtd {$it['unidade']}",$id]);
    }
    db()->prepare('UPDATE item SET nome=?,codigo=?,unidade=?,quantidade=?,estoque_minimo=?,almoxarifado_id=?,categoria=?,ca=? WHERE id=?')->execute([trim($_POST['nome']),trim($_POST['codigo']),trim($_POST['unidade']),$novaQtd,(float)$_POST['estoque_minimo'],(int)$_POST['almoxarifado_id'],$_POST['categoria']??'geral',trim($_POST['ca']??'')?:null,$id]);
    flash('Item atualizado!','success'); redirect("/almoxarifado/{$_POST['almoxarifado_id']}");
}
$pageTitle='Editar Item'; $activeMenu='almoxarifado'; $activeAlmId=$it['almoxarifado_id'];
ob_start(); require VIEWS_PATH.'/itens/form.php';
$content=ob_get_clean(); require VIEWS_PATH.'/layouts/base.php';
