<?php
requer_login();
$u = usuario_atual();

// Atualização de status via POST (AJAX-friendly)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $itemId    = (int)($_POST['item_id'] ?? 0);
    $novoStatus = $_POST['status_compra'] ?? '';
    $validos   = ['pendente', 'verificando', 'pedido_efetuado', 'pedido_rota', 'recebido'];
    if ($itemId && in_array($novoStatus, $validos)) {
        $stItem = db()->prepare('SELECT almoxarifado_id FROM item WHERE id=?');
        $stItem->execute([$itemId]);
        $almId = (int)($stItem->fetchColumn() ?: 0);
        if ($almId && usuario_tem_acesso_almoxarifado($almId)) {
            db()->prepare('UPDATE item SET status_compra=? WHERE id=?')->execute([$novoStatus, $itemId]);
        }
    }
    if (is_ajax()) {
        json_response(['ok' => true]);
    }
    redirect('/relatorios/alertas');
}

$ids = almoxarifados_permitidos_ids();
if (empty($ids)) {
    $alertas = [];
    $almoxarifados = [];
} else {
    $ph = implode(',', array_fill(0, count($ids), '?'));

    $almoxarifados = $u['perfil'] === 'admin'
        ? db()->query('SELECT * FROM almoxarifado ORDER BY nome')->fetchAll()
        : (function() use ($ids, $ph) {
            $s = db()->prepare("SELECT * FROM almoxarifado WHERE id IN ($ph) ORDER BY nome");
            $s->execute($ids);
            return $s->fetchAll();
        })();

    $stAlerts = db()->prepare(
        "SELECT i.*, a.nome AS alm_nome
         FROM item i
         JOIN almoxarifado a ON a.id = i.almoxarifado_id
         WHERE i.quantidade <= i.estoque_minimo
           AND i.ativo = 1
           AND i.almoxarifado_id IN ($ph)
         ORDER BY i.quantidade ASC, i.nome ASC"
    );
    $stAlerts->execute($ids);
    $itensAlerta = $stAlerts->fetchAll();

    // Calcular previsão de ruptura para cada item
    $agora  = new DateTime();
    $corte7 = (new DateTime())->modify('-7 days')->format('Y-m-d H:i:s');
    $corte30= (new DateTime())->modify('-30 days')->format('Y-m-d H:i:s');

    $alertas = [];
    foreach ($itensAlerta as $it) {
        $stM = db()->prepare(
            "SELECT quantidade, data FROM movimentacao
             WHERE item_id=? AND tipo='saida' AND data>=?"
        );
        $stM->execute([$it['id'], $corte30]);
        $movs = $stM->fetchAll();

        $saidas7 = 0; $saidas30 = 0;
        foreach ($movs as $m) {
            $saidas30 += (float)$m['quantidade'];
            if ($m['data'] >= $corte7) $saidas7 += (float)$m['quantidade'];
        }

        $cd7  = $saidas7  > 0 ? $saidas7  / 7  : 0;
        $cd30 = $saidas30 > 0 ? $saidas30 / 30 : 0;

        if ($cd7 > 0 && $cd30 > 0)    $consumoDiario = ($cd7 * 3 + $cd30) / 4;
        elseif ($cd7 > 0)             $consumoDiario = $cd7;
        elseif ($cd30 > 0)            $consumoDiario = $cd30;
        else                           $consumoDiario = 0;

        $diasAteZero = ($consumoDiario > 0 && (float)$it['quantidade'] > 0)
            ? (int)ceil((float)$it['quantidade'] / $consumoDiario)
            : 0;

        $deficit = max(0, (float)$it['estoque_minimo'] - (float)$it['quantidade']);

        $alertas[] = [
            'item'          => $it,
            'alm_nome'      => $it['alm_nome'],
            'deficit'       => $deficit,
            'dias_ate_zero' => $diasAteZero,
            'consumo_diario'=> round($consumoDiario, 2),
            'urgencia'      => (float)$it['quantidade'] <= 0 ? 'critico' : 'alerta',
        ];
    }
}

$pageTitle  = 'Alertas / Pedidos de Compra';
$activeMenu = 'relatorios';
ob_start();
require VIEWS_PATH . '/relatorios/alertas.php';
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/base.php';
