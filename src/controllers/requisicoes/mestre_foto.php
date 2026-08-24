<?php
/**
 * Controller: Foto do Comprovante na Requisição Mestre
 * POST /requisicoes/mestre/{id}/foto  (JSON, somente admin/almoxarife)
 */
requer_login();

$u = usuario_atual();

if (!in_array($u['perfil'], ['admin', 'almoxarife'])) {
    json_response(['ok' => false, 'erro' => 'Acesso negado.'], 403);
}

$id = (int)($params['id'] ?? 0);
if ($id <= 0) {
    json_response(['ok' => false, 'erro' => 'ID inválido.'], 400);
}

// Verificar que a requisição existe e o almoxarife tem acesso
$stReq = db()->prepare('SELECT id, almoxarifado_id FROM requisicao_mestre WHERE id = ?');
$stReq->execute([$id]);
$req = $stReq->fetch();

if (!$req) {
    json_response(['ok' => false, 'erro' => 'Requisição não encontrada.'], 404);
}

if ($u['perfil'] === 'almoxarife' && !usuario_tem_acesso_almoxarifado((int)$req['almoxarifado_id'])) {
    json_response(['ok' => false, 'erro' => 'Acesso negado a este almoxarifado.'], 403);
}

// Ler corpo JSON
$rawBody = file_get_contents('php://input');
$data    = json_decode($rawBody, true);

// Suporte a POST form-data com campo "foto" (fallback)
if (!$data && !empty($_POST['foto'])) {
    $data = ['foto' => $_POST['foto']];
}

$foto = $data['foto'] ?? '';

if (empty($foto)) {
    json_response(['ok' => false, 'erro' => 'Campo "foto" ausente.'], 400);
}

// Validar que começa com data:image
if (!str_starts_with($foto, 'data:image')) {
    json_response(['ok' => false, 'erro' => 'Formato inválido. Esperado data:image/...'], 400);
}

// Limitar tamanho (~5MB em base64 ≈ ~6.7MB string)
if (strlen($foto) > 7_000_000) {
    json_response(['ok' => false, 'erro' => 'Imagem muito grande. Máximo 5MB.'], 400);
}

// Salvar no banco
try {
    db()->prepare('UPDATE requisicao_mestre SET foto_url = ? WHERE id = ?')
       ->execute([$foto, $id]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'erro' => 'Erro ao salvar foto.'], 500);
}

json_response(['ok' => true, 'foto_url' => $foto]);
