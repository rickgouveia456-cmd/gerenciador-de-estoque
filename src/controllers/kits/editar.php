<?php
requer_almoxarife(); $u=usuario_atual();
$almId=(int)($params["alm_id"]??0); $kitId=(int)($params["kit_id"]??0);
$stA=db()->prepare("SELECT * FROM almoxarifado WHERE id=?"); $stA->execute([$almId]); $alm=$stA->fetch();
if(!$alm||!usuario_tem_acesso_almoxarifado($almId)){flash("Acesso negado.","danger");redirect("/");}
$stK=db()->prepare("SELECT * FROM kit WHERE id=? AND almoxarifado_id=?"); $stK->execute([$kitId,$almId]); $kit=$stK->fetch();
if(!$kit){flash("Kit nao encontrado.","danger");redirect("/almoxarifado/$almId/kits");}
$stI=db()->prepare("SELECT id,nome,codigo,unidade,quantidade FROM item WHERE almoxarifado_id=? AND ativo=1 ORDER BY nome"); $stI->execute([$almId]); $itens=$stI->fetchAll();
$stKI=db()->prepare("SELECT ki.*,i.nome AS item_nome,i.unidade FROM kit_item ki JOIN item i ON i.id=ki.item_id WHERE ki.kit_id=?"); $stKI->execute([$kitId]); $kit["_itens"]=$stKI->fetchAll();
if($_SERVER["REQUEST_METHOD"]==="POST"){
    csrf_check();
    db()->prepare("UPDATE kit SET nome=?,descricao=? WHERE id=?")->execute([trim($_POST["nome"]??$kit["nome"]),trim($_POST["descricao"]??"")?:null,$kitId]);
    db()->prepare("DELETE FROM kit_item WHERE kit_id=?")->execute([$kitId]);
    $indices=[]; foreach($_POST as $k=>$_){if(preg_match("/^item_id_(\d+)$/",$k,$m)) $indices[]=(int)$m[1];} sort($indices);
    foreach($indices as $i){
        $itemId=(int)($_POST["item_id_$i"]??0); $qtd=(float)($_POST["qtd_$i"]??1);
        if(!$itemId||$qtd<=0) continue;
        db()->prepare("INSERT INTO kit_item (kit_id,item_id,quantidade) VALUES (?,?,?)")->execute([$kitId,$itemId,$qtd]);
    }
    flash("Kit atualizado!","success"); redirect("/almoxarifado/$almId/kits");
}
$pageTitle="Editar Kit"; $activeAlmId=$almId;
ob_start(); require VIEWS_PATH."/kits/form.php";
$content=ob_get_clean(); require VIEWS_PATH."/layouts/base.php";