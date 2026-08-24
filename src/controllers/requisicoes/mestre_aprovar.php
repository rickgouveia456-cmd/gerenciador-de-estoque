<?php
requer_login(); csrf_check(); $id=(int)($params['id']??0); $u=usuario_atual();
if(!in_array($u['perfil'],['admin','almoxarife'])){flash('Acesso negado.','danger');redirect('/requisicoes/mestre');}
$st=db()->prepare('SELECT * FROM requisicao_mestre WHERE id=?'); $st->execute([$id]); $req=$st->fetch();
if(!$req||$req['status']!=='pendente'){flash('Requisição não está pendente.','warning');redirect("/requisicoes/mestre/$id");}
$decisao=$_POST['decisao']??'aprovada';
$stI=db()->prepare('SELECT * FROM requisicao_mestre_item WHERE requisicao_id=?'); $stI->execute([$id]); $itens=$stI->fetchAll();
if($decisao==='parcial'){
    $aprov=0; $recus=0;
    foreach($itens as $ri){
        $st2=$_POST["item_status_{$ri['id']}"]??'aprovado'; $mot=trim($_POST["item_motivo_{$ri['id']}"]??'');
        db()->prepare('UPDATE requisicao_mestre_item SET status_item=?,motivo_recusa=? WHERE id=?')->execute([$st2,$st2==='recusado'?$mot:null,$ri['id']]);
        $st2==='aprovado'?$aprov++:$recus++;
    }
    $novoStatus=$aprov===0?'recusada':($recus===0?'aprovada':'aprovada');
} elseif($decisao==='recusada'){
    $novoStatus='recusada'; $mot=trim($_POST['motivo_geral']??'');
    foreach($itens as $ri) db()->prepare('UPDATE requisicao_mestre_item SET status_item=?,motivo_recusa=? WHERE id=?')->execute(['recusado',$mot,$ri['id']]);
} else {
    $novoStatus='aprovada';
    foreach($itens as $ri) db()->prepare('UPDATE requisicao_mestre_item SET status_item=?,motivo_recusa=NULL WHERE id=?')->execute(['aprovado',$ri['id']]);
}
db()->prepare('UPDATE requisicao_mestre SET status=? WHERE id=?')->execute([$novoStatus,$id]);
flash($novoStatus==='aprovada'?'Requisição aprovada!':'Requisição '.$novoStatus.'.',$novoStatus==='recusada'?'danger':'success');
redirect("/requisicoes/mestre/$id");
