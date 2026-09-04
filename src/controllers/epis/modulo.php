<?php
requer_login();
$u = usuario_atual();
$aba = $_GET["aba"] ?? "painel";
$ids = almoxarifados_permitidos_ids();
$almoxarifados = $u["perfil"] === "admin"
    ? db()->query("SELECT * FROM almoxarifado ORDER BY nome")->fetchAll()
    : ($ids ? (function($ids){ $ph=implode(",",array_fill(0,count($ids),"?")); $s=db()->prepare("SELECT * FROM almoxarifado WHERE id IN ($ph) ORDER BY nome"); $s->execute($ids); return $s->fetchAll(); })($ids) : []);

// ── PAINEL ───────────────────────────────────────────────────
if ($aba === "painel") {
    $ph = $ids && $u["perfil"] !== "admin" ? implode(",", array_fill(0, count($ids), "?")) : null;
    $bids = $ids && $u["perfil"] !== "admin" ? $ids : [];

    $where = $ph ? "WHERE almoxarifado_id IN ($ph)" : "";
    $total_fichas  = (int)db()->prepare("SELECT COUNT(*) FROM ficha_epi $where")->execute($bids) ? 0 : 0;
    $stF = db()->prepare("SELECT COUNT(*) FROM ficha_epi $where"); $stF->execute($bids); $total_fichas = (int)$stF->fetchColumn();
    $stA = db()->prepare("SELECT COUNT(*) FROM ficha_epi $where" . ($ph ? " AND" : " WHERE") . " status=?"); $stA->execute(array_merge($bids, ["ativa"])); $fichas_ativas = (int)$stA->fetchColumn();
    $stD = db()->prepare("SELECT COUNT(*) FROM item_ficha_epi ife JOIN ficha_epi f ON f.id=ife.ficha_id WHERE ife.data_entrega IS NOT NULL AND ife.data_devolucao IS NULL" . ($ph ? " AND f.almoxarifado_id IN ($ph)" : "")); $stD->execute($bids); $devolucoes_abertas = (int)$stD->fetchColumn();

    $stU = db()->prepare("SELECT * FROM ficha_epi $where" . ($ph ? " AND" : " WHERE") . " status=? ORDER BY criado_em DESC LIMIT 6"); $stU->execute(array_merge($bids, ["ativa"])); $ultimas_fichas = $stU->fetchAll();
}

// ── FICHAS ───────────────────────────────────────────────────
if ($aba === "fichas") {
    $busca = trim($_GET["q"] ?? "");
    $status_filtro = $_GET["status"] ?? "";
    $sql = "SELECT f.*,a.nome AS alm_nome FROM ficha_epi f LEFT JOIN almoxarifado a ON a.id=f.almoxarifado_id WHERE 1=1";
    $binds = [];
    if ($ids && $u["perfil"] !== "admin") { $ph=implode(",",array_fill(0,count($ids),"?")); $sql.=" AND f.almoxarifado_id IN ($ph)"; $binds=array_merge($binds,$ids); }
    if ($busca) { $sql .= " AND f.colaborador LIKE ?"; $binds[] = "%$busca%"; }
    if ($status_filtro) { $sql .= " AND f.status=?"; $binds[] = $status_filtro; }
    $sql .= " ORDER BY f.criado_em DESC";
    $st = db()->prepare($sql); $st->execute($binds); $fichas = $st->fetchAll();
    // Contar itens por ficha
    foreach ($fichas as &$f) {
        $stI = db()->prepare("SELECT COUNT(*) FROM item_ficha_epi WHERE ficha_id=?"); $stI->execute([$f["id"]]); $f["total_itens"] = (int)$stI->fetchColumn();
    }
}

// ── FICHA DETALHE ────────────────────────────────────────────
if ($aba === "ficha_detalhe") {
    $fichaId = (int)($_GET["id"] ?? 0);
    $st = db()->prepare("SELECT f.*,a.nome AS alm_nome FROM ficha_epi f LEFT JOIN almoxarifado a ON a.id=f.almoxarifado_id WHERE f.id=?"); $st->execute([$fichaId]); $ficha = $st->fetch();
    if (!$ficha) { flash("Ficha nao encontrada.","danger"); redirect("/epi_modulo?aba=fichas"); }
    $stI = db()->prepare("SELECT * FROM item_ficha_epi WHERE ficha_id=? ORDER BY id"); $stI->execute([$fichaId]); $ficha_itens = $stI->fetchAll();
}

// ── NOVA FICHA (POST) — ficha fixa por colaborador ───────────
if ($aba === "ficha_nova" && $_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_check();
    $colab = trim($_POST["colaborador"] ?? "");
    $funcao = trim($_POST["funcao"] ?? "");
    $obra   = trim($_POST["obra"]   ?? "");
    $almId  = (int)($_POST["almoxarifado_id"] ?? 0);

    if (!$colab) {
        flash("Informe o colaborador.", "warning");
        redirect("/epi_modulo?aba=ficha_nova");
    }

    // Verificar se ja existe ficha ATIVA para este colaborador
    $stEx = db()->prepare(
        "SELECT id FROM ficha_epi WHERE LOWER(colaborador)=LOWER(?) AND status='ativa' ORDER BY criado_em DESC LIMIT 1"
    );
    $stEx->execute([$colab]);
    $fichaExistente = $stEx->fetchColumn();

    if ($fichaExistente) {
        // Reutilizar ficha existente — adicionar EPIs nela
        $fichaId = (int)$fichaExistente;
        // Atualizar funcao/obra se informados e ainda vazios
        if ($funcao || $obra) {
            db()->prepare(
                "UPDATE ficha_epi SET
                    funcao = COALESCE(NULLIF(funcao,''), ?),
                    obra   = COALESCE(NULLIF(obra,''),   ?)
                 WHERE id = ?"
            )->execute([$funcao ?: null, $obra ?: null, $fichaId]);
        }
        $msgFicha = "EPIs adicionados na ficha existente de $colab.";
    } else {
        // Criar nova ficha
        db()->prepare(
            "INSERT INTO ficha_epi (colaborador,funcao,obra,almoxarifado_id,criado_por,data_abertura)
             VALUES (?,?,?,?,?,CURDATE())"
        )->execute([$colab, $funcao ?: null, $obra ?: null, $almId ?: null, $u["nome"]]);
        $fichaId  = (int)db()->lastInsertId();
        $msgFicha = "Ficha criada para $colab!";
    }

    // Inserir itens na ficha (nova ou existente)
    $indices = [];
    foreach ($_POST as $k => $_) {
        if (preg_match("/^epi_desc_(\d+)$/", $k, $m)) $indices[] = (int)$m[1];
    }
    sort($indices);

    $inseridos = 0;
    foreach ($indices as $i) {
        $desc = trim($_POST["epi_desc_$i"] ?? "");
        if (!$desc) continue;
        $ca  = trim($_POST["epi_ca_$i"]  ?? "") ?: null;
        $qtd = (float)($_POST["epi_qtd_$i"] ?? 1);
        $tam = trim($_POST["epi_tam_$i"] ?? "") ?: null;
        $dtE = $_POST["epi_dt_$i"] ?? date("Y-m-d");
        db()->prepare(
            "INSERT INTO item_ficha_epi (ficha_id,descricao,ca,quantidade,tamanho,data_entrega)
             VALUES (?,?,?,?,?,?)"
        )->execute([$fichaId, $desc, $ca, $qtd, $tam, $dtE]);
        $inseridos++;
    }

    if ($inseridos === 0) {
        flash("Adicione pelo menos um EPI.", "warning");
        redirect("/epi_modulo?aba=ficha_nova");
    }

    flash($msgFicha, "success");
    redirect("/epi_modulo?aba=ficha_detalhe&id=$fichaId");
}

// ── DEVOLUCAO ITEM FICHA (POST) ──────────────────────────────
if ($aba === "devolver_item" && $_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_check();
    $itemId = (int)($_POST["item_id"] ?? 0); $fichaId = (int)($_POST["ficha_id"] ?? 0);
    db()->prepare("UPDATE item_ficha_epi SET data_devolucao=CURDATE() WHERE id=?")->execute([$itemId]);
    flash("Devolucao registrada!","success"); redirect("/epi_modulo?aba=ficha_detalhe&id=$fichaId");
}

// ── ENCERRAR FICHA (POST) ────────────────────────────────────
if ($aba === "encerrar_ficha" && $_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_check();
    $fichaId = (int)($_POST["ficha_id"] ?? 0);
    db()->prepare("UPDATE ficha_epi SET status=?,data_encerramento=CURDATE() WHERE id=?")->execute(["encerrada",$fichaId]);
    flash("Ficha encerrada.","warning"); redirect("/epi_modulo?aba=ficha_detalhe&id=$fichaId");
}

// ── DEVOLUCOES ───────────────────────────────────────────────
if ($aba === "devolucoes") {
    $sql = "SELECT ife.*,f.colaborador,f.funcao,f.obra,a.nome AS alm_nome FROM item_ficha_epi ife JOIN ficha_epi f ON f.id=ife.ficha_id LEFT JOIN almoxarifado a ON a.id=f.almoxarifado_id WHERE ife.data_entrega IS NOT NULL AND ife.data_devolucao IS NULL";
    $binds = [];
    if ($ids && $u["perfil"] !== "admin") { $ph=implode(",",array_fill(0,count($ids),"?")); $sql.=" AND f.almoxarifado_id IN ($ph)"; $binds=array_merge($binds,$ids); }
    $sql .= " ORDER BY ife.data_entrega ASC";
    $st = db()->prepare($sql); $st->execute($binds); $devolucoes = $st->fetchAll();
}

// ── MATRIZ ───────────────────────────────────────────────────
if ($aba === "matriz") {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        csrf_check();
        $funcao = trim($_POST["funcao"] ?? ""); $obra = trim($_POST["obra"] ?? ""); $norma = trim($_POST["norma"] ?? "");
        $epis = array_filter(array_map("trim", explode("\n", $_POST["epis_texto"] ?? "")));
        db()->prepare("INSERT INTO matriz_epi (funcao,obra,norma,epis_json,criado_por) VALUES (?,?,?,?,?)")->execute([$funcao,$obra?:null,$norma?:null,json_encode(array_values($epis)),$u["nome"]]);
        flash("Matriz criada!","success"); redirect("/epi_modulo?aba=matriz");
    }
    $st = db()->query("SELECT * FROM matriz_epi ORDER BY funcao"); $matrizes = $st->fetchAll();
    foreach ($matrizes as &$m) { $m["_epis"] = json_decode($m["epis_json"] ?? "[]", true) ?: []; }
}

if ($aba === "deletar_matriz" && $_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_check(); $mId = (int)($_POST["matriz_id"] ?? 0);
    db()->prepare("DELETE FROM matriz_epi WHERE id=?")->execute([$mId]);
    flash("Matriz removida.","warning"); redirect("/epi_modulo?aba=matriz");
}

// ── HABILITACOES ─────────────────────────────────────────────
if ($aba === "habilitacoes") {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        csrf_check();
        db()->prepare("INSERT INTO habilitacao_funcionario (colaborador,tipo,descricao,validade,almoxarifado_id,criado_por) VALUES (?,?,?,?,?,?)")->execute([trim($_POST["colaborador"]),trim($_POST["tipo"]),trim($_POST["descricao"]??"")?:null,$_POST["validade"]??null?:null,(int)($_POST["almoxarifado_id"]??0)?:null,$u["nome"]]);
        flash("Habilitacao registrada!","success"); redirect("/epi_modulo?aba=habilitacoes");
    }
    $sql = "SELECT * FROM habilitacao_funcionario WHERE 1=1";
    $binds = []; $q = trim($_GET["q"] ?? "");
    if ($q) { $sql .= " AND colaborador LIKE ?"; $binds[] = "%$q%"; }
    $sql .= " ORDER BY colaborador, tipo";
    $st = db()->prepare($sql); $st->execute($binds); $habilitacoes = $st->fetchAll();
}

$pageTitle = "Módulo EPI"; $activeMenu = "epi_modulo";
ob_start(); require VIEWS_PATH . "/epis/modulo.php";
$content = ob_get_clean(); require VIEWS_PATH . "/layouts/base.php";