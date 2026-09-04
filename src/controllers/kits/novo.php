<?php
requer_almoxarife(); $u=usuario_atual();
$almId=(int)($params["alm_id"]??0);
$stA=db()->prepare("SELECT * FROM almoxarifado WHERE id=?"); $stA->execute([$almId]); $alm=$stA->fetch();
if(!$alm||!usuario_tem_acesso_almoxarifado($almId)){flash("Acesso negado.","danger");redirect("/almoxarifado/$almId/kits");}
$stI=db()->prepare("SELECT id,nome,codigo,unidade,quantidade FROM item WHERE almoxarifado_id=? AND ativo=1 ORDER BY nome"); $stI->execute([$almId]); $itens=$stI->fetchAll();
if($_SERVER["REQUEST_METHOD"]==="POST"){
    csrf_check();
    $nome=trim($_POST["nome"]??"")||"Kit sem nome";
    $desc=trim($_POST["descricao"]??"")?:null;
    db()->prepare("INSERT INTO kit (nome,descricao,almoxarifado_id,criado_por) VALUES (?,?,?,?)")->execute([$nome,$desc,$almId,$u["nome"]]);
    $kitId=(int)db()->lastInsertId();
    $indices=[]; foreach($_POST as $k=>$_){if(preg_match("/^item_id_(\d+)$/",$k,$m)) $indices[]=(int)$m[1];} sort($indices);
    foreach($indices as $i){
        $itemId=(int)($_POST["item_id_$i"]??0); $qtd=(float)($_POST["qtd_$i"]??1);
        if(!$itemId||$qtd<=0) continue;
        db()->prepare("INSERT INTO kit_item (kit_id,item_id,quantidade) VALUES (?,?,?)")->execute([$kitId,$itemId,$qtd]);
    }
    flash("Kit \"$nome\" criado!","success"); redirect("/almoxarifado/$almId/kits");
}
$pageTitle="Novo Kit"; $activeAlmId=$almId;
ob_start(); require VIEWS_PATH."/kits/form.php";
$content=ob_get_clean(); require VIEWS_PATH."/layouts/base.php";