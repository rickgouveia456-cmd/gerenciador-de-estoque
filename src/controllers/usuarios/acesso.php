<?php
requer_admin(); csrf_check(); $id=(int)($params['id']??0); $atual=usuario_atual();
$acao=$_POST['acao']??'';
if($acao==='acesso_extra'){
    db()->prepare('INSERT INTO acesso_extra (usuario_id,almoxarifado_id,motivo,data_fim,concedido_por) VALUES (?,?,?,?,?)')->execute([$id,(int)$_POST['almoxarifado_id'],trim($_POST['motivo']??'')?:null,trim($_POST['data_fim']??'')?:null,$atual['nome']]);
    flash('Acesso concedido!','success');
} elseif($acao==='revogar_acesso'){
    db()->prepare('DELETE FROM acesso_extra WHERE id=?')->execute([(int)$_POST['acesso_id']]);
    flash('Acesso revogado.','warning');
} elseif($acao==='permissao'){
    $perm=trim($_POST['permissao']??'');
    $ex=db()->prepare('SELECT id FROM permissao_extra WHERE usuario_id=? AND permissao=?');$ex->execute([$id,$perm]);
    if(!$ex->fetch()){ db()->prepare('INSERT INTO permissao_extra (usuario_id,permissao,concedido_por) VALUES (?,?,?)')->execute([$id,$perm,$atual['nome']]); flash('Permissão concedida!','success'); }
    else flash('Permissão já existe.','info');
} elseif($acao==='revogar_perm'){
    db()->prepare('DELETE FROM permissao_extra WHERE id=?')->execute([(int)$_POST['perm_id']]);
    flash('Permissão revogada.','warning');
}
redirect("/usuarios/$id/editar");
