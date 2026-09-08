<?php
/**
 * Logi-Prime — Funções auxiliares globais
 */

// ── Output e segurança ────────────────────────────────────────────────────────
function h($str): string {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

function flash(string $msg, string $tipo = 'info'): void {
    $_SESSION['flash'][] = ['msg' => $msg, 'tipo' => $tipo];
}

function get_flashes(): array {
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ── CSRF ──────────────────────────────────────────────────────────────────────
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function csrf_check(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        die('Token CSRF inválido. Recarregue a página e tente novamente.');
    }
}

// ── Paginação ─────────────────────────────────────────────────────────────────
function paginar(int $total, int $por_pagina, int $pagina_atual): array {
    $total_paginas = max(1, (int)ceil($total / $por_pagina));
    $pagina_atual  = max(1, min($pagina_atual, $total_paginas));
    $offset        = ($pagina_atual - 1) * $por_pagina;
    return [
        'total'         => $total,
        'por_pagina'    => $por_pagina,
        'pagina_atual'  => $pagina_atual,
        'total_paginas' => $total_paginas,
        'offset'        => $offset,
        'tem_anterior'  => $pagina_atual > 1,
        'tem_proxima'   => $pagina_atual < $total_paginas,
    ];
}

// ── Formatação ────────────────────────────────────────────────────────────────
function fmt_numero(float $n, int $casas = 2): string {
    return number_format($n, $casas, ',', '.');
}

function fmt_data(string $data, string $formato = 'd/m/Y'): string {
    if (!$data) return '—';
    try {
        return (new DateTime($data))->format($formato);
    } catch (Exception $e) {
        return $data;
    }
}

function fmt_data_hora(string $data): string {
    return fmt_data($data, 'd/m/Y H:i');
}

function tempo_relativo(string $data): string {
    if (!$data) return '—';
    $diff = time() - strtotime($data);
    if ($diff < 60)     return 'agora';
    if ($diff < 3600)   return (int)($diff/60) . ' min atrás';
    if ($diff < 86400)  return (int)($diff/3600) . 'h atrás';
    if ($diff < 604800) return (int)($diff/86400) . 'd atrás';
    return fmt_data($data);
}

// ── Configuração do sistema ───────────────────────────────────────────────────
function cfg(string $chave, string $padrao = ''): string {
    static $cache = [];
    if (isset($cache[$chave])) return $cache[$chave];
    try {
        $stmt = db()->prepare("SELECT valor FROM configuracao_sistema WHERE chave = ?");
        $stmt->execute([$chave]);
        $val = $stmt->fetchColumn();
        $cache[$chave] = ($val !== false) ? $val : $padrao;
    } catch (Throwable $e) {
        $cache[$chave] = $padrao;
    }
    return $cache[$chave];
}

function cfg_set(string $chave, string $valor): void {
    db()->prepare("
        INSERT INTO configuracao_sistema (chave, valor)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE valor = VALUES(valor)
    ")->execute([$chave, $valor]);
}

// ── Status de estoque ─────────────────────────────────────────────────────────
function status_estoque(float $qtd, float $minimo): string {
    if ($qtd <= 0)       return 'critico';
    if ($qtd <= $minimo) return 'alerta';
    return 'ok';
}

function badge_status_estoque(float $qtd, float $minimo): string {
    $s = status_estoque($qtd, $minimo);
    $map = [
        'critico' => ['bg-danger',  'Crítico'],
        'alerta'  => ['bg-warning text-dark', 'Alerta'],
        'ok'      => ['bg-success', 'OK'],
    ];
    [$cls, $label] = $map[$s];
    return "<span class=\"badge $cls\">$label</span>";
}
