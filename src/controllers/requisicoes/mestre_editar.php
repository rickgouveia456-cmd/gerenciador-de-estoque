<?php
requer_login(); $id=(int)($params['id']??0); $u=usuario_atual();
if(!in_array($u['perfil'],['admin','almoxarife'])){flash('Sem permissao.','danger');redirect('/requisicoes/mestre');}
$st=db()->prepare('SELECT * FROM requisicao_mestre WHERE id=?'); $st->execute([$id]); $req=$st->fetch();
if(!$req||$req['status']==='entregue'){flash('Não editável.','warning');redirect("/requisicoes/mestre/$id");}
$stI=db()->prepare('SELECT * FROM requisicao_mestre_item WHERE requisicao_id=?'); $stI->execute([$id]); $itens=$stI->fetchAll();
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    db()->prepare('UPDATE requisicao_mestre SET colaborador=?,observacao=? WHERE id=?')->execute([trim($_POST['colaborador']??$req['colaborador']),trim($_POST['observacao']??''),$id]);
    foreach($itens as $ri){
        $qtd=(float)($_POST["qtd_{$ri['id']}"]??$ri['quantidade']); $obs=trim($_POST["obs_{$ri['id']}"]??'');
        db()->prepare('UPDATE requisicao_mestre_item SET quantidade=?,observacao=? WHERE id=?')->execute([$qtd,$obs,$ri['id']]);
    }
    flash('Requisição atualizada!','success'); redirect("/requisicoes/mestre/$id");
}
$pageTitle="Editar Req #{$req['id']}"; $activeMenu='req_mestre';
ob_start(); require VIEWS_PATH.'/requisicoes/mestre_editar.php';
$content=ob_get_clean(); require VIEWS_PATH.'/layouts/base.php';
