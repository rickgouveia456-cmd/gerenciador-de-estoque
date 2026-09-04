<?php
requer_almoxarife(); $u=usuario_atual();
$almId=(int)($_GET["alm"]??0);$stA=db()->prepare("SELECT * FROM almoxarifado WHERE id=?");$stA->execute([$almId]);$alm=$stA->fetch();
if(!$alm||!usuario_tem_acesso_almoxarifado($almId)){flash("Acesso negado.","danger");redirect("/epis");}
if($_SERVER["REQUEST_METHOD"]==="POST"){
    csrf_check();
    db()->prepare("INSERT INTO item_epi (identificacao,nome,tamanho,almoxarifado_id,quantidade,local,observacao) VALUES (?,?,?,?,?,?,?)")->execute([trim($_POST["identificacao"]??""),trim($_POST["nome"]??""),trim($_POST["tamanho"]??"")?:null,$almId,(int)($_POST["quantidade"]??1),trim($_POST["local"]??"")?:null,trim($_POST["observacao"]??"")?:null]);
    flash("EPI cadastrado!","success");redirect("/epis?alm=$almId");
}
$pageTitle="Novo EPI";$activeMenu="epis";$f=null;
ob_start();require VIEWS_PATH."/epis/form.php";$content=ob_get_clean();require VIEWS_PATH."/layouts/base.php";