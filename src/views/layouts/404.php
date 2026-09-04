<?php $pageTitle = 'Página não encontrada'; ob_start(); ?>
<div class="d-flex flex-column align-items-center justify-content-center" style="min-height:60vh">
  <div class="display-1 fw-bold text-muted">404</div>
  <h2 class="mb-3">Página não encontrada</h2>
  <a href="/" class="btn btn-primary"><i class="bi bi-house me-2"></i>Voltar ao início</a>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/base.php'; ?>
