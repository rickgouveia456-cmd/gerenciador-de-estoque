<?php
requer_login();
$u = usuario_atual();

// Redirecionar mestre/tecnico/colaborador com requisitar para tela de requisicoes
// analista e assistente ficam no dashboard normal — NÃO redirecionar
if (in_array($u['perfil'], ['mestre', 'tecnico_seguranca'])) {
    redirect('/requisicoes/mestre');
}
if ($u['perfil'] === 'colaborador') {
    $stmt = db()->prepare("SELECT COUNT(*) FROM permissao_extra WHERE usuario_id=? AND permissao='fazer_requisicao'");
    $stmt->execute([$u['id']]);
    if ($u['pode_requisitar'] || $stmt->fetchColumn() > 0) {
        redirect('/requisicoes/mestre');
    }
}

$ids = almoxarifados_permitidos_ids();
$idsStr = $ids ? implode(',', array_map('intval', $ids)) : '0';

// Almoxarifados visiveis
if ($u['perfil'] === 'admin') {
    $almoxarifados = db()->query('SELECT * FROM almoxarifado ORDER BY cidade, obra, nome')->fetchAll();
} elseif ($u['perfil'] === 'analista' && $u['almoxarifado_id']) {
    $stmtA = db()->prepare('SELECT * FROM almoxarifado WHERE id=?');
    $stmtA->execute([$u['almoxarifado_id']]);
    $refAlm = $stmtA->fetch();
    if ($refAlm && $refAlm['cidade']) {
        $stmtA2 = db()->prepare('SELECT * FROM almoxarifado WHERE cidade=? ORDER BY nome');
        $stmtA2->execute([$refAlm['cidade']]);
        $almoxarifados = $stmtA2->fetchAll();
    } else {
        $almoxarifados = $refAlm ? [$refAlm] : [];
    }
} else {
    if ($ids) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmtA = db()->prepare("SELECT * FROM almoxarifado WHERE id IN ($ph) ORDER BY cidade, obra, nome");
        $stmtA->execute($ids);
        $almoxarifados = $stmtA->fetchAll();
    } else {
        $almoxarifados = [];
    }
}

// IDs dos almoxarifados visiveis
$almIds = array_column($almoxarifados, 'id');
$almIdsStr = $almIds ? implode(',', array_map('intval', $almIds)) : '0';

// Alertas (quantidade <= estoque_minimo)
$alertas = db()->query(
    "SELECT i.*, a.nome AS alm_nome
     FROM item i
     JOIN almoxarifado a ON a.id = i.almoxarifado_id
     WHERE i.quantidade <= i.estoque_minimo AND i.ativo = 1 AND i.almoxarifado_id IN ($almIdsStr)
     ORDER BY i.quantidade ASC
     LIMIT 50"
)->fetchAll();

// Stats
$totalItens   = (int)db()->query("SELECT COUNT(*) FROM item WHERE ativo=1 AND almoxarifado_id IN ($almIdsStr)")->fetchColumn();
$itensAlerta  = count(array_filter($alertas, fn($a) => $a['quantidade'] > 0));
$itensCritico = count(array_filter($alertas, fn($a) => $a['quantidade'] <= 0));

// Movimentacoes recentes (ultimas 10)
$movRecentes = db()->query(
    "SELECT m.*, i.nome AS item_nome, i.unidade, a.nome AS alm_nome
     FROM movimentacao m
     JOIN item i ON i.id = m.item_id
     JOIN almoxarifado a ON a.id = i.almoxarifado_id
     WHERE i.almoxarifado_id IN ($almIdsStr)
     ORDER BY m.data DESC
     LIMIT 10"
)->fetchAll();

// Requisicoes mestre pendentes
$reqPendentes = (int)db()->query(
    "SELECT COUNT(*) FROM requisicao_mestre
     WHERE status='pendente' AND almoxarifado_id IN ($almIdsStr)"
)->fetchColumn();

$stats = [
    'total_almoxarifados' => count($almoxarifados),
    'total_itens'         => $totalItens,
    'itens_alerta'        => $itensAlerta,
    'itens_criticos'      => $itensCritico,
    'req_pendentes'       => $reqPendentes,
];


// ── Previsão de Ruptura (itens em risco nos próximos 15 dias) ──────────────
$ruptura = [];
if (!empty($almIds)) {
    $almIdsStrR = implode(',', array_map('intval', $almIds));
    $itensAtivos = db()->query(
        "SELECT * FROM item WHERE ativo=1 AND almoxarifado_id IN ($almIdsStrR) AND quantidade > 0"
    )->fetchAll();

    $agora = new DateTime();
    $corte30 = (new DateTime())->modify('-30 days')->format('Y-m-d H:i:s');
    $corte7  = (new DateTime())->modify('-7 days')->format('Y-m-d H:i:s');

    foreach ($itensAtivos as $it) {
        $stM = db()->prepare(
            "SELECT quantidade, data FROM movimentacao
             WHERE item_id=? AND tipo='saida' AND data>=?"
        );
        $stM->execute([$it['id'], $corte30]);
        $movs = $stM->fetchAll();
        if (empty($movs)) continue;

        $saidas7  = 0; $saidas30 = 0;
        foreach ($movs as $m) {
            $saidas30 += (float)$m['quantidade'];
            if ($m['data'] >= $corte7) $saidas7 += (float)$m['quantidade'];
        }

        $cd7  = $saidas7  > 0 ? $saidas7 / 7  : 0;
        $cd30 = $saidas30 > 0 ? $saidas30 / 30 : 0;

        if ($cd7 > 0 && $cd30 > 0)      $consumoDiario = ($cd7 * 3 + $cd30) / 4;
        elseif ($cd7 > 0)                $consumoDiario = $cd7;
        else                             $consumoDiario = $cd30;

        if ($consumoDiario <= 0) continue;

        $diasAteZero   = max(0, (float)$it['quantidade'] / $consumoDiario);
        $estoqueDisp   = (float)$it['quantidade'] - (float)$it['estoque_minimo'];
        $diasAteMinimo = $estoqueDisp / $consumoDiario;

        if ($diasAteMinimo > 15) continue; // só mostra risco nos próximos 15 dias

        $urgencia = $diasAteMinimo <= 0  ? 'critico'
                  : ($diasAteMinimo <= 7  ? 'alerta'
                  : ($diasAteMinimo <= 15 ? 'aviso'  : 'normal'));

        // Buscar nome do almoxarifado
        $stA2 = db()->prepare("SELECT nome FROM almoxarifado WHERE id=?");
        $stA2->execute([$it['almoxarifado_id']]);
        $almNomeR = $stA2->fetchColumn();

        $ruptura[] = [
            'item'           => $it,
            'alm_nome'       => $almNomeR,
            'dias'           => (int)round(max(0, $diasAteMinimo)),
            'dias_zero'      => (int)round($diasAteZero),
            'consumo_diario' => round($consumoDiario, 2),
            'urgencia'       => $urgencia,
        ];
    }
    usort($ruptura, fn($a, $b) => $a['dias'] <=> $b['dias']);
    $ruptura = array_slice($ruptura, 0, 10);
}
$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';
ob_start();
require VIEWS_PATH . '/dashboard/index.php';
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/base.php';
