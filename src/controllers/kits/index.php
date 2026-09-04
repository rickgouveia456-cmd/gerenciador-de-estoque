<?php
requer_login(); $u=usuario_atual();
$almId=(int)($params["alm_id"]??0);
$stA=db()->prepare("SELECT * FROM almoxarifado WHERE id=?"); $stA->execute([$almId]); $alm=$stA->fetch();
if(!$alm||!usuario_tem_acesso_almoxarifado($almId)){flash("Acesso negado.","danger");redirect("/");}
$stmt=db()->prepare("SELECT k.*,COUNT(ki.id) AS total_itens FROM kit k LEFT JOIN kit_item ki ON ki.kit_id=k.id WHERE k.ativo=1 AND k.almoxarifado_id=? GROUP BY k.id ORDER BY k.nome");
$stmt->execute([$almId]); $kits=$stmt->fetchAll();
// Itens de cada kit
foreach($kits as &$kit){
    $stI=db()->prepare("SELECT ki.*,i.nome AS item_nome,i.unidade,i.codigo FROM kit_item ki JOIN item i ON i.id=ki.item_id WHERE ki.kit_id=?");
    $stI->execute([$kit["id"]]); $kit["_itens"]=$stI->fetchAll();
}
$pageTitle="Kits — ".h($alm["nome"]); $activeMenu="kits"; $activeAlmId=$almId;
ob_start(); require VIEWS_PATH."/kits/index.php";
$content=ob_get_clean(); require VIEWS_PATH."/layouts/base.php";