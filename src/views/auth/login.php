<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#ff6b35">
<title>Login — Logi-Prime</title>
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
      crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="/assets/css/app.css">
<style>
body { background: var(--bg); display:flex; align-items:center; justify-content:center; min-height:100vh; }
.login-card { width:100%; max-width:400px; }
.login-logo { width:64px; height:64px; object-fit:contain; }
</style>
</head>
<body>
<?php $flashes = get_flash(); ?>
<div class="login-card p-3">
  <div class="card shadow-lg">
    <div class="card-body p-4">
      <div class="text-center mb-4">
        <img src="/assets/icons/logo.svg" alt="Logi-Prime" class="login-logo mb-3">
        <h4 class="fw-bold mb-1" style="background:var(--gradient-primary);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">
          Logi-Prime
        </h4>
        <p class="text-muted small">Gestão de Almoxarifado</p>
      </div>

      <?php foreach ($flashes as $fl): ?>
      <div class="alert alert-<?= h($fl['tipo']) ?> alert-dismissible fade show" role="alert">
        <?= $fl['msg'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endforeach; ?>

      <form method="POST" action="/login" autocomplete="on">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label fw-semibold">Login</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" name="login" class="form-control" placeholder="Seu login"
                   value="<?= h($_POST['login'] ?? '') ?>"
                   autocomplete="username" required autofocus>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Senha</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="senha" class="form-control" placeholder="Sua senha"
                   autocomplete="current-password" required>
            <button type="button" class="btn btn-outline-secondary" id="btnToggleSenha">
              <i class="bi bi-eye" id="iconSenha"></i>
            </button>
          </div>
        </div>

        <?php if (!empty($requer2fa)): ?>
        <div class="mb-3" id="secao2fa">
          <label class="form-label fw-semibold">Código 2FA</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
            <input type="text" name="totp_code" class="form-control" placeholder="000000"
                   maxlength="6" inputmode="numeric" autocomplete="one-time-code" autofocus>
          </div>
          <div class="form-text">Digite o código do Google Authenticator.</div>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary w-100 py-2 mt-2">
          <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
        </button>
      </form>
    </div>
  </div>
  <p class="text-center text-muted small mt-3">Logi-Prime v2.0 (PHP)</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmO7O+NDXAz6RBf5Nk7hKikBMTN7"
        crossorigin="anonymous"></script>
<script>
document.getElementById('btnToggleSenha')?.addEventListener('click', function() {
  const inp = document.querySelector('input[name=senha]');
  const ico = document.getElementById('iconSenha');
  if (inp.type === 'password') { inp.type = 'text'; ico.className = 'bi bi-eye-slash'; }
  else { inp.type = 'password'; ico.className = 'bi bi-eye'; }
});
</script>
</body>
</html>
