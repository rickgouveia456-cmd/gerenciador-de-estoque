<?php
/**
 * Dispara sincronizações manuais com o Sienge
 * POST /integracao/sincronizar — campo: acao = materiais | estoque | requisicao
 */
requer_login();
$u = usuario_atual();
if ($u['perfil'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Acesso negado.']);
    exit;
}

header('Content-Type: application/json');
require_once HELPERS_PATH . '/sienge.php';

$acao = trim($_POST['acao'] ?? $_GET['acao'] ?? '');

switch ($acao) {

    case 'materiais':
        $r = sienge_sincronizar_materiais();
        echo json_encode($r, JSON_UNESCAPED_UNICODE);
        break;

    case 'estoque':
        $almId = (int)($_POST['almoxarifado_id'] ?? 0);
        if (!$almId) {
            echo json_encode(['ok' => false, 'msg' => 'Informe o almoxarifado.']);
            break;
        }
        $r = sienge_enviar_estoque($almId);
        echo json_encode($r, JSON_UNESCAPED_UNICODE);
        break;

    case 'requisicao':
        $reqId = (int)($_POST['requisicao_id'] ?? 0);
        if (!$reqId) {
            echo json_encode(['ok' => false, 'msg' => 'Informe a requisição.']);
            break;
        }
        $r = sienge_enviar_requisicao($reqId);
        echo json_encode($r, JSON_UNESCAPED_UNICODE);
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => "Ação '$acao' desconhecida. Use: materiais | estoque | requisicao"]);
}
