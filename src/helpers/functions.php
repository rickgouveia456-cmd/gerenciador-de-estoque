<?php
/**
 * Logi-Prime — Funcoes utilitarias globais
 */

function redirect(string $path): never {
    $base = rtrim(APP_URL, '/');
    header('Location: ' . $base . $path);
    exit;
}

function flash(string $msg, string $tipo = 'info'): void {
    $_SESSION['flash'][] = ['msg' => $msg, 'tipo' => $tipo];
}

function get_flash(): array {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

function csrf_verify(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals(csrf_token(), $token);
}

function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify()) {
        flash('Sessao expirada. Tente novamente.', 'warning');
        redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }
}

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function fmt_qtd(float $value): string {
    $v = round($value, 4);
    if ($v == (int)$v) return (string)(int)$v;
    return rtrim(rtrim(number_format($v, 4, '.', ''), '0'), '.');
}

function fmt_dinheiro(float $value): string {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function fmt_data(?string $dt, string $formato = 'd/m/Y H:i'): string {
    if (!$dt) return '—';
    try {
        return (new DateTime($dt))->format($formato);
    } catch (Exception) {
        return $dt;
    }
}

function status_item(float $qtd, float $minimo): string {
    if ($qtd <= 0) return 'critico';
    if ($qtd <= $minimo) return 'alerta';
    return 'ok';
}

function status_badge(string $status): string {
    return match($status) {
        'ok'      => '<span class="badge bg-success">OK</span>',
        'alerta'  => '<span class="badge bg-warning">Alerta</span>',
        'critico' => '<span class="badge bg-danger">Crítico</span>',
        default   => '<span class="badge bg-secondary">' . h($status) . '</span>',
    };
}

function categoria_label(string $cat): string {
    return match($cat) {
        'epi'        => 'EPI',
        'maquinario' => 'Maquinário',
        'eletrica'   => 'Elétrica',
        'hidraulica' => 'Hidráulica',
        'gas'        => 'Gás',
        default      => 'Geral',
    };
}

function paginate(int $total, int $page, int $perPage = 20): array {
    $totalPages = (int)ceil($total / $perPage);
    $page = max(1, min($page, $totalPages ?: 1));
    $offset = ($page - 1) * $perPage;
    return [
        'total'      => $total,
        'page'       => $page,
        'per_page'   => $perPage,
        'total_pages'=> $totalPages,
        'offset'     => $offset,
        'has_prev'   => $page > 1,
        'has_next'   => $page < $totalPages,
    ];
}

function json_response(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function is_ajax(): bool {
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

function gerar_protocolo(): string {
    return strtoupper(date('ymd')) . '-' . strtoupper(bin2hex(random_bytes(3)));
}
