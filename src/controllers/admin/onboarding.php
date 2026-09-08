<?php
/**
 * Marca o onboarding como concluído via AJAX
 */
requer_login();
header('Content-Type: application/json');

$passo = trim($_POST['passo'] ?? '');
if (!$passo) { echo json_encode(['ok' => false]); exit; }

// Salva qual passo foi visto na sessão do usuário
$_SESSION['onboarding_visto'][$passo] = true;

// Se passou por todos os passos, marca no banco
$passos_totais = ['dashboard','almoxarifado','movimentacao','requisicao','epi'];
$todos = true;
foreach ($passos_totais as $p) {
    if (empty($_SESSION['onboarding_visto'][$p])) { $todos = false; break; }
}
if ($todos) cfg_set('onboarding_feito', '1');

echo json_encode(['ok' => true, 'todos' => $todos]);
