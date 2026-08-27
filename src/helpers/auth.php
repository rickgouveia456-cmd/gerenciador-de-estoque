<?php
/**
 * Logi-Prime — Helpers de autenticacao e controle de acesso
 */

function usuario_logado(): bool {
    return isset($_SESSION['usuario_id']);
}

function usuario_atual(): ?array {
    if (!usuario_logado()) return null;
    static $cache = [];
    $id = $_SESSION['usuario_id'];
    if (!isset($cache[$id])) {
        $stmt = db()->prepare(
            'SELECT u.*, a.nome AS alm_nome, a.cidade AS alm_cidade
             FROM usuario u
             LEFT JOIN almoxarifado a ON a.id = u.almoxarifado_id
             WHERE u.id = ? AND u.ativo = 1'
        );
        $stmt->execute([$id]);
        $cache[$id] = $stmt->fetch() ?: null;
    }
    return $cache[$id];
}

function requer_login(): void {
    if (!usuario_logado()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('/login');
    }
}

function requer_admin(): void {
    requer_login();
    $u = usuario_atual();
    if (!$u || $u['perfil'] !== 'admin') {
        flash('Acesso restrito a administradores.', 'danger');
        redirect('/');
    }
}

function requer_almoxarife(): void {
    requer_login();
    $u = usuario_atual();
    if (!$u || !in_array($u['perfil'], ['admin', 'almoxarife'])) {
        flash('Acesso restrito a almoxarifes.', 'danger');
        redirect('/');
    }
}

function perfil_pode(string $acao): bool {
    $u = usuario_atual();
    if (!$u) return false;
    $perfil = $u['perfil'];
    $mapa = [
        'ver_dashboard'      => ['admin', 'almoxarife', 'analista', 'assistente'],
        'ver_almoxarifado'   => ['admin', 'almoxarife', 'analista', 'assistente'],
        'editar_item'        => ['admin', 'almoxarife', 'assistente'],
        'movimentar'         => ['admin', 'almoxarife', 'assistente'],
        'ver_relatorios'     => ['admin', 'almoxarife', 'analista', 'assistente'],
        'fazer_requisicao'   => ['admin', 'almoxarife', 'mestre', 'tecnico_seguranca', 'colaborador'],
        'aprovar_requisicao' => ['admin', 'almoxarife'],
        'gerenciar_usuarios' => ['admin'],
        'ver_catalogo'       => ['admin', 'almoxarife', 'analista', 'assistente'],
        'gerenciar_catalogo' => ['admin', 'almoxarife', 'assistente'],
    ];
    return in_array($perfil, $mapa[$acao] ?? []);
}

function almoxarifados_permitidos_ids(): array {
    $u = usuario_atual();
    if (!$u) return [];
    if ($u['perfil'] === 'admin') {
        $rows = db()->query('SELECT id FROM almoxarifado')->fetchAll();
        return array_column($rows, 'id');
    }
    $ids = [];
    if ($u['almoxarifado_id']) $ids[] = (int)$u['almoxarifado_id'];

    // Acessos extras ativos
    $stmt = db()->prepare(
        'SELECT almoxarifado_id FROM acesso_extra
         WHERE usuario_id = ?
           AND (data_fim IS NULL OR data_fim > NOW())'
    );
    $stmt->execute([$u['id']]);
    foreach ($stmt->fetchAll() as $row) {
        $ids[] = (int)$row['almoxarifado_id'];
    }
    return array_unique($ids);
}

function usuario_tem_acesso_almoxarifado(int $almId): bool {
    $u = usuario_atual();
    if (!$u) return false;
    if ($u['perfil'] === 'admin') return true;
    return in_array($almId, almoxarifados_permitidos_ids());
}

// ── Rate Limiting (por IP na sessao) ─────────────────────────────────────────
function _check_rate_limit(string $ip): bool {
    $key     = 'rl_' . md5($ip);
    $bloqueio = 'rl_bloq_' . md5($ip);
    if (!empty($_SESSION[$bloqueio]) && $_SESSION[$bloqueio] > time()) return true;
    return false;
}

function _register_attempt(string $ip): void {
    $key = 'rl_' . md5($ip);
    $bloqueio = 'rl_bloq_' . md5($ip);
    $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;
    if ($_SESSION[$key] >= 5) {
        $_SESSION[$bloqueio] = time() + 300; // 5 min
        $_SESSION[$key] = 0;
    }
}

function _clear_attempts(string $ip): void {
    $key = 'rl_' . md5($ip);
    $bloqueio = 'rl_bloq_' . md5($ip);
    unset($_SESSION[$key], $_SESSION[$bloqueio]);
}
