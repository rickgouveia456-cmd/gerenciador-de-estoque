<?php
/**
 * Layout base — Logi-Prime PHP
 * Uso: define $pageTitle, $activeMenu antes de incluir
 */
$u         = usuario_atual();
$flashes   = get_flash();
$pageTitle = $pageTitle ?? 'Logi-Prime';

// Sidebar: almoxarifados visiveis para o usuario
if ($u) {
    if ($u['perfil'] === 'admin') {
        $sidebarAlms = db()->query('SELECT * FROM almoxarifado ORDER BY cidade, obra, nome')->fetchAll();
    } else {
        $ids = almoxarifados_permitidos_ids();
        if ($ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = db()->prepare("SELECT * FROM almoxarifado WHERE id IN ($placeholders) ORDER BY cidade, obra, nome");
            $stmt->execute($ids);
            $sidebarAlms = $stmt->fetchAll();
        } else {
            $sidebarAlms = [];
        }
    }
} else {
    $sidebarAlms = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#ff6b35">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<title><?= h($pageTitle) ?> — Logi-Prime</title>
<link rel="manifest" href="/assets/manifest.json">
<link rel="icon" href="/assets/icons/logo.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
      crossorigin="anonymous">
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<?php if ($u): ?>
<!-- ── Sidebar ─────────────────────────────────────────────── -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<nav class="sidebar" id="sidebar">
  <div class="brand">
    <a href="/" class="d-flex align-items-center gap-2 text-decoration-none">
      <img src="/assets/icons/logo-sidebar.svg" height="32" alt="Logi-Prime">
      <span class="fw-bold text-dark fs-6">Logi-Prime</span>
    </a>
    <button class="btn btn-sm d-md-none border-0" onclick="closeSidebar()">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>

  <div class="nav-section">Menu Principal</div>
  <nav class="nav flex-column">
    <?php if (in_array($u['perfil'], ['admin','almoxarife','analista'])): ?>
    <a href="/" class="nav-link <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">
      <i class="bi bi-speedometer2 me-2"></i>Dashboard
    </a>
    <?php endif; ?>

    <?php if (in_array($u['perfil'], ['mestre','tecnico_seguranca']) || ($u['perfil'] === 'colaborador' && $u['pode_requisitar'])): ?>
    <a href="/requisicoes/mestre" class="nav-link <?= ($activeMenu ?? '') === 'req_mestre' ? 'active' : '' ?>">
      <i class="bi bi-clipboard-check me-2"></i>Minhas Requisições
    </a>
    <?php endif; ?>

    <?php if (in_array($u['perfil'], ['admin','almoxarife','analista'])): ?>
    <a href="/movimentacao/lote" class="nav-link <?= ($activeMenu ?? '') === 'movimentacao' ? 'active' : '' ?>">
      <i class="bi bi-arrow-left-right me-2"></i>Movimentação
    </a>
    <a href="/requisicoes" class="nav-link <?= ($activeMenu ?? '') === 'requisicoes' ? 'active' : '' ?>">
      <i class="bi bi-list-check me-2"></i>Requisições
    </a>
    <a href="/requisicoes/mestre" class="nav-link <?= ($activeMenu ?? '') === 'req_mestre' ? 'active' : '' ?>">
      <i class="bi bi-clipboard-plus me-2"></i>Req. Mestre
    </a>
    <?php endif; ?>
  </nav>

  <!-- Almoxarifados -->
  <?php if (!empty($sidebarAlms)): ?>
  <div class="nav-section">Almoxarifados</div>
  <nav class="nav flex-column">
    <?php foreach ($sidebarAlms as $alm): ?>
    <a href="/almoxarifado/<?= $alm['id'] ?>" class="nav-link <?= (($activeAlmId ?? 0) == $alm['id']) ? 'active' : '' ?>">
      <i class="bi bi-box-seam me-2"></i>
      <span class="text-truncate"><?= h($alm['nome']) ?></span>
    </a>
    <?php endforeach; ?>
    <?php if ($u['perfil'] === 'admin'): ?>
    <a href="/almoxarifado/novo" class="nav-link text-success">
      <i class="bi bi-plus-circle me-2"></i>Novo Almoxarifado
    </a>
    <?php endif; ?>
  </nav>
  <?php endif; ?>

  <!-- Modulos -->
  <?php if (in_array($u['perfil'], ['admin','almoxarife','analista','tecnico_seguranca'])): ?>
  <div class="nav-section">Módulos</div>
  <nav class="nav flex-column">
    <a href="/ferramentas" class="nav-link <?= ($activeMenu ?? '') === 'ferramentas' ? 'active' : '' ?>">
      <i class="bi bi-tools me-2"></i>Ferramentas
    </a>
    <a href="/epis" class="nav-link <?= ($activeMenu ?? '') === 'epis' ? 'active' : '' ?>">
      <i class="bi bi-shield-check me-2"></i>EPIs
    </a>
    <a href="/epi_modulo" class="nav-link <?= ($activeMenu ?? '') === 'epi_modulo' ? 'active' : '' ?>">
      <i class="bi bi-person-badge me-2"></i>Módulo EPI
    </a>
    <a href="/colaboradores" class="nav-link <?= ($activeMenu ?? '') === 'colaboradores' ? 'active' : '' ?>">
      <i class="bi bi-people me-2"></i>Colaboradores
    </a>
    <?php if (in_array($u['perfil'], ['admin','almoxarife','analista'])): ?>
    <a href="/catalogo" class="nav-link <?= ($activeMenu ?? '') === 'catalogo' ? 'active' : '' ?>">
      <i class="bi bi-journal-text me-2"></i>Catálogo
    </a>
    <a href="/relatorios" class="nav-link <?= ($activeMenu ?? '') === 'relatorios' ? 'active' : '' ?>">
      <i class="bi bi-bar-chart-line me-2"></i>Relatórios
    </a>
    <?php endif; ?>
  </nav>
  <?php endif; ?>

  <!-- Admin -->
  <?php if ($u['perfil'] === 'admin'): ?>
  <div class="nav-section">Administração</div>
  <nav class="nav flex-column">
    <a href="/usuarios" class="nav-link <?= ($activeMenu ?? '') === 'usuarios' ? 'active' : '' ?>">
      <i class="bi bi-person-gear me-2"></i>Usuários
    </a>
    <a href="/admin" class="nav-link <?= ($activeMenu ?? '') === 'admin' ? 'active' : '' ?>">
      <i class="bi bi-sliders me-2"></i>Painel Admin
    </a>
    <a href="/admin/backup" class="nav-link <?= ($activeMenu ?? '') === 'backup' ? 'active' : '' ?>">
      <i class="bi bi-cloud-download me-2"></i>Backup
    </a>
  </nav>
  <?php endif; ?>

  <!-- Footer sidebar -->
  <div class="mt-auto p-3 border-top" style="position:sticky;bottom:0;background:var(--primary)">
    <div class="d-flex align-items-center gap-2">
      <div class="rounded-circle bg-accent d-flex align-items-center justify-content-center"
           style="width:32px;height:32px;background:var(--accent);flex-shrink:0">
        <span class="text-white fw-bold" style="font-size:0.75rem">
          <?= strtoupper(substr($u['nome'], 0, 1)) ?>
        </span>
      </div>
      <div class="flex-grow-1 overflow-hidden">
        <div class="fw-semibold text-truncate" style="font-size:0.82rem"><?= h($u['nome']) ?></div>
        <div class="text-muted" style="font-size:0.72rem"><?= h(ucfirst($u['perfil'])) ?></div>
      </div>
      <form method="POST" action="/logout" class="m-0">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Sair">
          <i class="bi bi-box-arrow-right"></i>
        </button>
      </form>
    </div>
  </div>
</nav>

<!-- ── Main ─────────────────────────────────────────────────── -->
<div class="main-content">
  <!-- Topbar -->
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm border-0 d-md-none" onclick="openSidebar()">
        <i class="bi bi-list fs-5"></i>
      </button>
      <span class="topbar-title"><?= h($pageTitle) ?></span>
    </div>
    <div class="d-flex align-items-center gap-2">
      <?php
      // Contador de alertas
      $nAlertas = 0;
      if (in_array($u['perfil'], ['admin','almoxarife']) || $u['pode_ver_alertas']) {
          $ids = almoxarifados_permitidos_ids();
          if ($ids) {
              $ph = implode(',', array_fill(0, count($ids), '?'));
              $stmtA = db()->prepare("SELECT COUNT(*) FROM item WHERE quantidade <= estoque_minimo AND ativo=1 AND almoxarifado_id IN ($ph)");
              $stmtA->execute($ids);
              $nAlertas = (int)$stmtA->fetchColumn();
          }
      }
      ?>
      <?php if ($nAlertas > 0): ?>
      <a href="/" class="btn btn-sm btn-outline-danger position-relative">
        <i class="bi bi-bell-fill"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
          <?= $nAlertas ?>
        </span>
      </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Flashes -->
  <?php if (!empty($flashes)): ?>
  <div class="px-4 pt-3">
    <?php foreach ($flashes as $flash): ?>
    <div class="alert alert-<?= h($flash['tipo']) ?> alert-dismissible fade show" role="alert">
      <?= $flash['msg'] /* pode conter HTML seguro */ ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Page content -->
  <div class="page-body">
    <?= $content ?? '' ?>
  </div>
</div>

<?php else: /* nao logado — sem sidebar */ ?>
<div class="page-body">
  <?= $content ?? '' ?>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmO7O+NDXAz6RBf5Nk7hKikBMTN7"
        crossorigin="anonymous"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
