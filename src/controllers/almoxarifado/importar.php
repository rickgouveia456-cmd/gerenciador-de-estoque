<?php
requer_login(); $id=(int)($params['id']??0); $u=usuario_atual();
if(!usuario_tem_acesso_almoxarifado($id)){flash('Acesso negado.','danger');redirect('/');}
$stA=db()->prepare('SELECT * FROM almoxarifado WHERE id=?');$stA->execute([$id]);$alm=$stA->fetch();
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $file=$_FILES['arquivo']??null;
    if(!$file||$file['error']!==UPLOAD_ERR_OK){flash('Envie um arquivo CSV.','danger');redirect("/almoxarifado/$id/importar");}
    $handle=fopen($file['tmp_name'],'r');
    fgetcsv($handle,0,';'); // pula cabeçalho
    $inseridos=0;$erros=0;
    while(($row=fgetcsv($handle,0,';'))!==false){
        if(!isset($row[1])||!$row[1]) continue;
        [$codigo,$nome,$cat,$unidade,$qtd,$minimo]=[$row[0]??'',$row[1]??'',$row[2]??'geral',$row[3]??'un',(float)($row[4]??0),(float)($row[5]??0)];
        $ex=db()->prepare('SELECT id FROM item WHERE codigo=? AND almoxarifado_id=?');$ex->execute([$codigo,$id]);$exist=$ex->fetch();
        if($exist){db()->prepare('UPDATE item SET nome=?,unidade=?,quantidade=quantidade+?,estoque_minimo=? WHERE id=?')->execute([$nome,$unidade,$qtd,$minimo,$exist['id']]);}
        else{db()->prepare('INSERT INTO item (nome,codigo,unidade,quantidade,estoque_minimo,almoxarifado_id) VALUES (?,?,?,?,?,?)')->execute([$nome,$codigo,$unidade,$qtd,$minimo,$id]);}
        $inseridos++;
    }
    fclose($handle);
    flash("Importação: $inseridos item(ns) processados.",'success');redirect("/almoxarifado/$id");
}
$pageTitle='Importar Itens';$activeMenu='almoxarifado';$activeAlmId=$id;
ob_start();require VIEWS_PATH.'/almoxarifado/importar.php';
$content=ob_get_clean();require VIEWS_PATH.'/layouts/base.php';
