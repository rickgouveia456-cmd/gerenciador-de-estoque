<?php
if (usuario_logado()) redirect('/');

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $loginIn = trim($_POST['login'] ?? '');
    $senhaIn = $_POST['senha'] ?? '';
    $totpIn  = trim($_POST['totp_code'] ?? '');

    if (_check_rate_limit($ip)) {
        flash('Muitas tentativas. Aguarde 5 minutos.', 'danger');
    } elseif (!$loginIn || !$senhaIn) {
        flash('Preencha login e senha.', 'warning');
    } else {
        $stmt = db()->prepare('SELECT * FROM usuario WHERE login = ? AND ativo = 1');
        $stmt->execute([$loginIn]);
        $u = $stmt->fetch();

        if ($u && password_verify($senhaIn, $u['senha_hash'])) {
            // Verifica 2FA
            if ($u['totp_secret']) {
                // Validacao TOTP simples sem biblioteca — algoritmo RFC 6238
                $valid = false;
                if ($totpIn) {
                    $valid = _verify_totp($u['totp_secret'], $totpIn);
                }
                if (!$valid) {
                    _register_attempt($ip);
                    $pageTitle = 'Login';
                    ob_start();
                    require VIEWS_PATH . '/auth/login.php';
                    $content = ob_get_clean();
                    // Passa flag de 2FA necessario
                    $requer2fa = true;
                    require VIEWS_PATH . '/layouts/base.php';
                    exit;
                }
            }
            _clear_attempts($ip);
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $u['id'];
            $redir = $_SESSION['redirect_after_login'] ?? '/';
            unset($_SESSION['redirect_after_login']);
            flash('Bem-vindo, ' . $u['nome'] . '!', 'success');
            redirect($redir);
        } else {
            _register_attempt($ip);
            usleep(300000); // 300ms — dificulta enumeracao
            flash('Login ou senha incorretos.', 'danger');
        }
    }
}

function _verify_totp(string $secret, string $code): bool {
    // Decode Base32
    $base32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = strtoupper($secret);
    $bits = '';
    for ($i = 0; $i < strlen($secret); $i++) {
        $val = strpos($base32, $secret[$i]);
        if ($val === false) continue;
        $bits .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
    }
    $key = '';
    for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
        $key .= chr(bindec(substr($bits, $i, 8)));
    }
    $time = (int)floor(time() / 30);
    for ($offset = -1; $offset <= 1; $offset++) {
        $t  = pack('N*', 0) . pack('N*', $time + $offset);
        $h  = hash_hmac('sha1', $t, $key, true);
        $o  = ord($h[19]) & 0x0F;
        $v  = (
            ((ord($h[$o])   & 0x7F) << 24) |
            ((ord($h[$o+1]) & 0xFF) << 16) |
            ((ord($h[$o+2]) & 0xFF) << 8)  |
             (ord($h[$o+3]) & 0xFF)
        ) % 1000000;
        if (str_pad($v, 6, '0', STR_PAD_LEFT) === str_pad($code, 6, '0', STR_PAD_LEFT)) {
            return true;
        }
    }
    return false;
}

$pageTitle = 'Login';
ob_start();
require VIEWS_PATH . '/auth/login.php';
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/base.php';
