<?php
requer_almoxarife(); $id=(int)($params["id"]??0); $u=usuario_atual();
$st=db()->prepare("SELECT * FROM item_epi WHERE id=?");$st->execute([$id]);$f=$st->fetch();
if(!$f||!usuario_tem_acesso_almoxarifado((int)$f["almoxarifado_id"])){flash("Acesso negado.","danger");redirect("/epis");}
if($_SERVER["REQUEST_METHOD"]==="POST"){
    csrf_check();
    db()->prepare("UPDATE item_epi SET identificacao=?,nome=?,tamanho=?,quantidade=?,local=?,observacao=? WHERE id=?")->execute([trim($_POST["identificacao"]??""),trim($_POST["nome"]??""),trim($_POST["tamanho"]??"")?:null,(int)($_POST["quantidade"]??1),trim($_POST["local"]??"")?:null,trim($_POST["observacao"]??"")?:null,$id]);
    flash("EPI atualizado!","success");redirect("/epis?alm=".$f["almoxarifado_id"]);
}
$almId=$f["almoxarifado_id"];$stA=db()->prepare("SELECT * FROM almoxarifado WHERE id=?");$stA->execute([$almId]);$alm=$stA->fetch();
$pageTitle="Editar EPI";$activeMenu="epis";
ob_start();require VIEWS_PATH."/epis/form.php";$content=ob_get_clean();require VIEWS_PATH."/layouts/base.php";