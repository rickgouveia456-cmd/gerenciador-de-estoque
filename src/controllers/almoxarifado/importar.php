<?php
requer_login(); $id=(int)($params["id"]??0); $u=usuario_atual();
if(!usuario_tem_acesso_almoxarifado($id)){flash("Acesso negado.","danger");redirect("/");}
$stA=db()->prepare("SELECT * FROM almoxarifado WHERE id=?");$stA->execute([$id]);$alm=$stA->fetch();
if(!$alm){http_response_code(404);exit;}

if($_SERVER["REQUEST_METHOD"]==="POST"){
    csrf_check();
    $file=$_FILES["arquivo"]??null;
    if(!$file||$file["error"]!==UPLOAD_ERR_OK){flash("Envie um arquivo CSV valido.","danger");redirect("/almoxarifado/$id/importar");}

    // Detectar e remover BOM UTF-8
    $content = file_get_contents($file["tmp_name"]);
    if(str_starts_with($content,"\xEF\xBB\xBF")) $content=substr($content,3);
    // Converter encoding se necessario
    $encoding = mb_detect_encoding($content,["UTF-8","Windows-1252","ISO-8859-1"],true);
    if($encoding && $encoding!=="UTF-8") $content=mb_convert_encoding($content,"UTF-8",$encoding);
    // Normalizar quebras de linha
    $content=str_replace(["\r\n","\r"],"\n",$content);

    $linhas=explode("\n",trim($content));
    array_shift($linhas); // pular cabecalho

    $catMap=["geral"=>"geral","epi"=>"epi","elétrica"=>"eletrica","eletrica"=>"eletrica",
             "hidráulica"=>"hidraulica","hidraulica"=>"hidraulica","gás"=>"gas","gas"=>"gas",
             "maquinário"=>"maquinario","maquinario"=>"maquinario"];

    $inseridos=0; $atualizados=0; $erros=[];
    foreach($linhas as $linha){
        if(!trim($linha)) continue;
        // Tentar separador ; primeiro, depois ,
        $row=str_getcsv($linha,";");
        if(count($row)<2) $row=str_getcsv($linha,",");

        $codigo  = trim($row[0]??"");
        $nome    = trim($row[1]??"");
        $catRaw  = strtolower(trim($row[2]??"geral"));
        $cat     = $catMap[$catRaw]??"geral";
        $unidade = trim($row[3]??"un")?:"un";
        $qtd     = (float)str_replace(",",".",$row[4]??"0");
        $minimo  = (float)str_replace(",",".",$row[5]??"0");
        $ca      = trim($row[6]??"")?:null;
        $valor   = (float)str_replace(",",".",$row[7]??"0")?:null;

        if(!$nome) continue;
        if(!$codigo) $codigo="IMP-".strtoupper(substr(md5($nome),0,6));

        // Limpar caracteres invalidos do codigo
        $codigo=preg_replace("/[^\w\-\.\/]/","",$codigo)?:"IMP".rand(1000,9999);

        $ex=db()->prepare("SELECT id FROM item WHERE codigo=? AND almoxarifado_id=?");
        $ex->execute([$codigo,$id]); $exist=$ex->fetch();

        try {
            if($exist){
                db()->prepare("UPDATE item SET nome=?,categoria=?,unidade=?,quantidade=quantidade+?,estoque_minimo=?,ca=?,valor_unitario=COALESCE(?,valor_unitario) WHERE id=?")->execute([$nome,$cat,$unidade,$qtd,$minimo,$ca,$valor,$exist["id"]]);
                $atualizados++;
            } else {
                db()->prepare("INSERT INTO item (nome,codigo,categoria,unidade,quantidade,estoque_minimo,almoxarifado_id,ca,valor_unitario) VALUES (?,?,?,?,?,?,?,?,?)")->execute([$nome,$codigo,$cat,$unidade,$qtd,$minimo,$id,$ca,$valor]);
                $inseridos++;
            }
        } catch(PDOException $e){
            $erros[]="Ignorado: ".h($nome)." (".$e->getMessage().")";
        }
    }

    if($inseridos||$atualizados) flash("Importação: $inseridos novo(s), $atualizados atualizado(s).","success");
    foreach(array_slice($erros,0,3) as $e) flash($e,"warning");
    redirect("/almoxarifado/$id");
}

$pageTitle="Importar Itens";$activeMenu="almoxarifado";$activeAlmId=$id;
ob_start();require VIEWS_PATH."/almoxarifado/importar.php";
$content=ob_get_clean();require VIEWS_PATH."/layouts/base.php";