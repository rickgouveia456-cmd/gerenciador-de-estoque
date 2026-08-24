<?php
/**
 * Controller: Excluir Movimentações (somente admin)
 * POST /movimentacoes/excluir
 */
requer_login();
requer_admin();
csrf_check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

$movIds = array_map('intval', (array)($_POST['mov_ids'] ?? []));
$movIds = array_filter($movIds, fn($id) => $id > 0);

if (empty($movIds)) {
    flash('Nenhuma movimentação selecionada.', 'warning');
    redirect($_SERVER['HTTP_REFERER'] ?? '/relatorios/consumo-por-pessoa');
}

$excluidas = 0;
$erros     = 0;

$pdo = db();

try {
    $pdo->beginTransaction();

    foreach ($movIds as $movId) {
        // Buscar movimentação
        $stM = $pdo->prepare('SELECT * FROM movimentacao WHERE id = ?');
        $stM->execute([$movId]);
        $mov = $stM->fetch();

        if (!$mov) {
            $erros++;
            continue;
        }

        $itemId    = (int)$mov['item_id'];
        $quantidade = (float)$mov['quantidade'];
        $tipo       = $mov['tipo'];

        // Reverter estoque
        if ($tipo === 'saida') {
            // Saída revertida: devolver ao estoque
            $pdo->prepare('UPDATE item SET quantidade = quantidade + ? WHERE id = ?')
                ->execute([$quantidade, $itemId]);
        } elseif ($tipo === 'entrada') {
            // Entrada revertida: subtrair do estoque
            $pdo->prepare('UPDATE item SET quantidade = quantidade - ? WHERE id = ?')
                ->execute([$quantidade, $itemId]);
        }

        // Deletar movimentação
        $pdo->prepare('DELETE FROM movimentacao WHERE id = ?')->execute([$movId]);
        $excluidas++;
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    flash('Erro ao excluir movimentações: ' . h($e->getMessage()), 'danger');
    redirect($_SERVER['HTTP_REFERER'] ?? '/relatorios/consumo-por-pessoa');
}

if ($excluidas > 0) {
    $msg = $excluidas === 1
        ? '1 movimentação excluída e estoque revertido com sucesso.'
        : "{$excluidas} movimentações excluídas e estoque revertido com sucesso.";
    if ($erros > 0) {
        $msg .= " ({$erros} não encontrada(s) e ignorada(s).)";
    }
    flash($msg, 'success');
} else {
    flash('Nenhuma movimentação foi excluída.', 'warning');
}

redirect($_SERVER['HTTP_REFERER'] ?? '/relatorios/consumo-por-pessoa');
