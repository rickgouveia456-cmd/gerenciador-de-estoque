<?php
requer_almoxarife(); $u=usuario_atual();

if($_SERVER["REQUEST_METHOD"]==="POST"){
    csrf_check();
    $file=$_FILES["arquivo"]??null;
    if(!$file||$file["error"]!==UPLOAD_ERR_OK){flash("Envie um arquivo Excel ou CSV.","danger");redirect("/catalogo/importar");}

    $content = file_get_contents($file["tmp_name"]);
    // Remover BOM
    if(str_starts_with($content,"\xEF\xBB\xBF")) $content=substr($content,3);
    // Converter encoding se necessario
    $enc = mb_detect_encoding($content,["UTF-8","Windows-1252","ISO-8859-1"],true);
    if($enc && $enc!=="UTF-8") $content=mb_convert_encoding($content,"UTF-8",$enc);
    $content=str_replace(["\r\n","\r"],"\n",$content);

    $linhas=explode("\n",trim($content));
    array_shift($linhas); // cabecalho

    $inseridos=0; $atualizados=0; $erros=[];
    foreach($linhas as $linha){
        if(!trim($linha)) continue;
        $row=str_getcsv($linha,";");
        if(count($row)<2) $row=str_getcsv($linha,",");

        $nome    = trim($row[0]??"");
        $codigo  = trim($row[1]??"");
        $unidade = trim($row[2]??"")?:"un";
        $catMap  = ["epi"=>"epi","geral"=>"geral","elétrica"=>"eletrica","eletrica"=>"eletrica","hidráulica"=>"hidraulica","hidraulica"=>"hidraulica","gás"=>"gas","gas"=>"gas","maquinário"=>"maquinario","maquinario"=>"maquinario"];
        $catRaw  = strtolower(trim($row[3]??"geral"));
        $cat     = $catMap[$catRaw]??"geral";
        $ca      = trim($row[4]??"")?:null;
        $valor   = (float)str_replace(",",".",$row[5]??"0")?:null;

        if(!$nome) continue;

        $ex=db()->prepare("SELECT id FROM catalogo_insumo WHERE nome LIKE ? AND ativo=1");
        $ex->execute([$nome]); $exist=$ex->fetch();

        try {
            if($exist){
                db()->prepare("UPDATE catalogo_insumo SET codigo_ref=?,unidade=?,categoria=?,ca=?,valor_unitario=? WHERE id=?")->execute([$codigo?:null,$unidade,$cat,$ca,$valor,$exist["id"]]);
                $atualizados++;
            } else {
                db()->prepare("INSERT INTO catalogo_insumo (nome,codigo_ref,unidade,categoria,ca,valor_unitario,criado_por) VALUES (?,?,?,?,?,?,?)")->execute([$nome,$codigo?:null,$unidade,$cat,$ca,$valor,$u["nome"]]);
                $inseridos++;
            }
            // Sincronizar valor com itens se tiver valor
            if($valor){ db()->prepare("UPDATE item SET valor_unitario=? WHERE LOWER(nome)=LOWER(?) AND ativo=1")->execute([$valor,$nome]); }
        } catch(PDOException $e){
            $erros[]="Linha ignorada: ".htmlspecialchars($nome)." (".$e->getMessage().")";
        }
    }
    if($inseridos||$atualizados) flash("Importação: $inseridos novo(s), $atualizados atualizado(s).","success");
    foreach(array_slice($erros,0,3) as $e) flash($e,"warning");
    redirect("/catalogo");
}

$pageTitle="Importar Catálogo";$activeMenu="catalogo";
ob_start();require VIEWS_PATH."/catalogo/importar.php";
$content=ob_get_clean();require VIEWS_PATH."/layouts/base.php";