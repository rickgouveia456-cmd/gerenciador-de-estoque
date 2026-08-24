<?php
requer_almoxarife(); $u=usuario_atual();
$categorias=['geral','epi','maquinario','eletrica','hidraulica','gas'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $nome=trim($_POST['nome']??'');if(!$nome){flash('Nome obrigatório.','danger');redirect('/catalogo/novo');}
    $ex=db()->prepare('SELECT id FROM catalogo_insumo WHERE nome LIKE ? AND ativo=1');$ex->execute([$nome]);
    if($ex->fetch()){flash("\"$nome\" já existe no catálogo.",'warning');}
    else{
        $val=(float)str_replace(',','.',$_POST['valor_unitario']??'0')?:null;
        db()->prepare('INSERT INTO catalogo_insumo (nome,codigo_ref,unidade,categoria,ca,descricao,valor_unitario,criado_por) VALUES (?,?,?,?,?,?,?,?)')->execute([$nome,trim($_POST['codigo_ref']??'')?:null,trim($_POST['unidade']??'un'),$_POST['categoria']??'geral',trim($_POST['ca']??'')?:null,trim($_POST['descricao']??'')?:null,$val,$u['nome']]);
        // sincronizar valor com itens
        if($val){db()->prepare('UPDATE item SET valor_unitario=? WHERE LOWER(nome)=LOWER(?) AND ativo=1')->execute([$val,$nome]);}
        flash("\"$nome\" adicionado ao catálogo!",'success');redirect('/catalogo');
    }
}
$pageTitle='Novo Insumo';$activeMenu='catalogo';$ins=null;
ob_start();require VIEWS_PATH.'/catalogo/form.php';
$content=ob_get_clean();require VIEWS_PATH.'/layouts/base.php';
