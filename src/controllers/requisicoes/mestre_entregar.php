<?php
requer_login(); csrf_check(); $id=(int)($params['id']??0); $u=usuario_atual();
if(!in_array($u['perfil'],['admin','ggo','almoxarife'])){flash('Acesso negado.','danger');redirect("/requisicoes/mestre/$id");}
$st=db()->prepare('SELECT * FROM requisicao_mestre WHERE id=?'); $st->execute([$id]); $req=$st->fetch();
if(!$req||!in_array($req['status'],['pendente','aprovada','parcial'])){flash('Já processada.','warning');redirect("/requisicoes/mestre/$id");}
$stI=db()->prepare("SELECT rmi.*,i.nome AS item_nome,i.unidade,i.quantidade AS estoq,i.categoria,i.ca FROM requisicao_mestre_item rmi JOIN item i ON i.id=rmi.item_id WHERE rmi.requisicao_id=? AND rmi.status_item IN ('aprovado','pendente')");
$stI->execute([$id]); $itens=$stI->fetchAll();
foreach($itens as $ri){
    if((float)$ri['quantidade']>(float)$ri['estoq']){flash("Estoque insuficiente: {$ri['item_nome']}",'danger');redirect("/requisicoes/mestre/$id");}
}
foreach($itens as $ri){
    db()->prepare('UPDATE item SET quantidade=quantidade-? WHERE id=?')->execute([$ri['quantidade'],$ri['item_id']]);
    db()->prepare('UPDATE requisicao_mestre_item SET status_item=? WHERE id=?')->execute(['aprovado',$ri['id']]);
    db()->prepare('INSERT INTO movimentacao (tipo,quantidade,responsavel,observacao,item_id) VALUES (?,?,?,?,?)')->execute(['saida',$ri['quantidade'],$req['colaborador'],"Req. Mestre #$id",$ri['item_id']]);
}
db()->prepare('UPDATE requisicao_mestre SET status=?,data_entrega=NOW(),entregue_por_id=? WHERE id=?')->execute(['entregue',$u['id'],$id]);

// ── Registrar EPIs entregues na ficha do colaborador ──────────────────────────
$itensEpi = array_filter($itens, fn($i) => strtolower($i['categoria'] ?? '') === 'epi');
if (!empty($itensEpi)) {
    $colab = $req['colaborador'];

    // Buscar ou criar ficha ativa para este colaborador
    $stFicha = db()->prepare(
        "SELECT id FROM ficha_epi WHERE LOWER(colaborador)=LOWER(?) AND status='ativa' ORDER BY criado_em DESC LIMIT 1"
    );
    $stFicha->execute([$colab]);
    $fichaId = $stFicha->fetchColumn();

    if (!$fichaId) {
        // Criar nova ficha automaticamente
        db()->prepare(
            "INSERT INTO ficha_epi (colaborador, almoxarifado_id, criado_por, data_abertura, status)
             VALUES (?, ?, ?, CURDATE(), 'ativa')"
        )->execute([$colab, $req['almoxarifado_id'], $u['nome']]);
        $fichaId = (int)db()->lastInsertId();
    }

    // Inserir cada EPI na ficha
    $stIns = db()->prepare(
        "INSERT INTO item_ficha_epi (ficha_id, descricao, ca, quantidade, data_entrega, registrado_por)
         VALUES (?, ?, ?, ?, NOW(), ?)"
    );
    foreach ($itensEpi as $epi) {
        $stIns->execute([
            $fichaId,
            $epi['item_nome'],
            $epi['ca'] ?: null,
            $epi['quantidade'],
            $u['nome'],
        ]);
    }
}
// ─────────────────────────────────────────────────────────────────────────────

flash('Entrega confirmada! Estoque atualizado.' . (!empty($itensEpi) ? ' EPIs registrados na ficha do colaborador.' : ''), 'success');
redirect("/requisicoes/mestre/$id");
