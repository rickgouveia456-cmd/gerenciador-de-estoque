<?php
requer_login(); $id=(int)($params["id"]??0); $u=usuario_atual();
if(!usuario_tem_acesso_almoxarifado($id)){flash("Acesso negado.","danger");redirect("/");}
$stA=db()->prepare("SELECT * FROM almoxarifado WHERE id=?");$stA->execute([$id]);$alm=$stA->fetch();
if(!$alm){http_response_code(404);exit;}

if($_SERVER["REQUEST_METHOD"]==="POST"){
    csrf_check();
    $file=$_FILES["arquivo"]??null;
    if(!$file||$file["error"]!==UPLOAD_ERR_OK){flash("Envie um arquivo CSV valido.","danger");redirect("/almoxarifado/$id/importar");}
    
    // Detectar e remover BOM do arquivo
    $content = file_get_contents($file["tmp_name"]);
    // Remover BOM UTF-8
    if(str_starts_with($content, "\xEF\xBB\xBF")) $content = substr($content, 3);
    // Converter para UTF-8 se necessario
    $encoding = mb_detect_encoding($content, ["UTF-8","Windows-1252","ISO-8859-1"], true);
    if($encoding && $encoding !== "UTF-8") $content = mb_convert_encoding($content, "UTF-8", $encoding);
    // Normalizar quebras de linha
    $content = str_replace(["\r\n","\r"], "\n", $content);
    
    $linhas = explode("\n", trim($content));
    array_shift($linhas); // pular cabecalho
    
    $inseridos=0; $atualizados=0; $erros=[];
    foreach($linhas as $linha){
        if(!trim($linha)) continue;
        // Tentar separador ; primeiro, depois ,
        $row = str_getcsv($linha, ";");
        if(count($row) < 2) $row = str_getcsv($linha, ",");
        
        $codigo  = trim($row[0] ?? "");
        $nome    = trim($row[1] ?? "");
        $unidade = trim($row[3] ?? "un") ?: "un";
        $qtd     = (float)str_replace(",",".",$row[4] ?? "0");
        $minimo  = (float)str_replace(",",".",$row[5] ?? "0");
        
        if(!$nome) continue;
        if(!$codigo) $codigo = "IMP-".strtoupper(substr(md5($nome),0,6));
        
        // Limpar caracteres invalidos
        $nome    = mb_convert_encoding($nome, "UTF-8", "UTF-8");
        $codigo  = preg_replace("/[^\w\-\.]/", "", $codigo) ?: "IMP".rand(1000,9999);
        
        $ex=db()->prepare("SELECT id FROM item WHERE codigo=? AND almoxarifado_id=?");
        $ex->execute([$codigo,$id]); $exist=$ex->fetch();
        
        try {
            if($exist){
                db()->prepare("UPDATE item SET nome=?,unidade=?,quantidade=quantidade+?,estoque_minimo=? WHERE id=?")->execute([$nome,$unidade,$qtd,$minimo,$exist["id"]]);
                $atualizados++;
            } else {
                db()->prepare("INSERT INTO item (nome,codigo,unidade,quantidade,estoque_minimo,almoxarifado_id) VALUES (?,?,?,?,?,?)")->execute([$nome,$codigo,$unidade,$qtd,$minimo,$id]);
                $inseridos++;
            }
        } catch(PDOException $e) {
            $erros[] = "Linha ignorada: ".h($nome)." (".$e->getMessage().")";
        }
    }
    
    if($inseridos||$atualizados) flash("Importação: $inseridos novo(s), $atualizados atualizado(s).","success");
    foreach(array_slice($erros,0,3) as $e) flash($e,"warning");
    redirect("/almoxarifado/$id");
}

$pageTitle="Importar Itens";$activeMenu="almoxarifado";$activeAlmId=$id;
ob_start();require VIEWS_PATH."/almoxarifado/importar.php";
$content=ob_get_clean();require VIEWS_PATH."/layouts/base.php";