<?php
requer_almoxarife(); $id=(int)($params['id']??0);
$st=db()->prepare('SELECT * FROM catalogo_insumo WHERE id=?');$st->execute([$id]);$ins=$st->fetch();if(!$ins){http_response_code(404);exit;}
$categorias=['geral','epi','maquinario','eletrica','hidraulica','gas'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $val=(float)str_replace(',','.',$_POST['valor_unitario']??'0')?:null;
    db()->prepare('UPDATE catalogo_insumo SET nome=?,codigo_ref=?,unidade=?,categoria=?,ca=?,descricao=?,valor_unitario=? WHERE id=?')->execute([trim($_POST['nome']),trim($_POST['codigo_ref']??'')?:null,trim($_POST['unidade']??'un'),$_POST['categoria']??'geral',trim($_POST['ca']??'')?:null,trim($_POST['descricao']??'')?:null,$val,$id]);
    if($val){db()->prepare('UPDATE item SET valor_unitario=? WHERE LOWER(nome)=LOWER(?) AND ativo=1')->execute([$val,trim($_POST['nome'])]);}
    flash('Insumo atualizado!','success');redirect('/catalogo');
}
$pageTitle='Editar Insumo';$activeMenu='catalogo';
ob_start();require VIEWS_PATH.'/catalogo/form.php';
$content=ob_get_clean();require VIEWS_PATH.'/layouts/base.php';
