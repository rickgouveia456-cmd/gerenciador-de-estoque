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
    <button class="btn btn-sm d-lg-none border-0" onclick="closeSidebar()">
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



    <?php if (in_array($u['perfil'], ['admin','almoxarife','analista'])): ?>
    <a href="/movimentacao/lote" class="nav-link <?= ($activeMenu ?? '') === 'movimentacao' ? 'active' : '' ?>">
      <i class="bi bi-arrow-left-right me-2"></i>Registro de Movimentação
    </a>
    <a href="/relatorios/alertas" class="nav-link <?= ($activeMenu ?? '') === 'alertas' ? 'active' : '' ?>">
      <i class="bi bi-bell me-2"></i>Alertas de Estoque
      <?php
      $ids = almoxarifados_permitidos_ids();
      if ($ids) {
          $ph = implode(',', array_fill(0, count($ids), '?'));
          $stA = db()->prepare("SELECT COUNT(*) FROM item WHERE quantidade <= estoque_minimo AND ativo=1 AND almoxarifado_id IN ($ph)");
          $stA->execute($ids);
          $nA = (int)$stA->fetchColumn();
          if ($nA > 0) echo '<span class="badge bg-danger ms-auto rounded-pill">' . $nA . '</span>';
      }
      ?>
    </a>
    <?php endif; ?>
    <?php // Requisicoes - visivel para todos os perfis com acesso ?>
    <div class="nav-section">Requisições</div>
    <a href="/requisicoes/mestre" class="nav-link <?= in_array(($activeMenu ?? ''), ['req_mestre','requisicoes']) ? 'active' : '' ?>">
      <i class="bi bi-clipboard-list me-2"></i>Requisições
      <?php
      // Badge requisições pendentes para almoxarife/admin
      if (in_array($u['perfil'], ['admin','almoxarife'])) {
          $ids2 = almoxarifados_permitidos_ids();
          if ($ids2) {
              $ph2 = implode(',', array_fill(0, count($ids2), '?'));
              $stR = db()->prepare("SELECT COUNT(*) FROM requisicao_mestre WHERE status='pendente' AND almoxarifado_id IN ($ph2)");
              $stR->execute($ids2);
              $nR = (int)$stR->fetchColumn();
              if ($nR > 0) echo '<span class="badge bg-warning text-dark ms-auto rounded-pill">' . $nR . '</span>';
          }
      }
      ?>
    </a>
    <a href="/requisicoes/mestre/nova" class="nav-link <?= ($activeMenu ?? '') === 'req_mestre_nova' ? 'active' : '' ?>">
      <i class="bi bi-plus-circle me-2"></i>Nova Requisição
    </a>
  </nav>

  <!-- Almoxarifados com sublinks -->
  <?php if (!empty($sidebarAlms)): ?>
  <?php
  // Agrupar por cidade
  $cidadeGrupos = [];
  foreach($sidebarAlms as $alm) {
      $cidade = $alm['cidade'] ?: 'Sem Cidade';
      $cidadeGrupos[$cidade][] = $alm;
  }
  ?>
  <?php foreach($cidadeGrupos as $cidade => $almsGrupo): ?>
  <div class="nav-section d-flex align-items-center gap-1">
    <i class="bi bi-geo-alt-fill" style="color:var(--accent);font-size:0.7rem"></i>
    <span><?= strtoupper(h($cidade)) ?></span>
  </div>
  <nav class="nav flex-column">
    <?php foreach($almsGrupo as $alm):
      $isActiveAlm = (($activeAlmId ?? 0) == $alm['id']);
      // Contadores
      $stN = db()->prepare("SELECT COUNT(*) FROM item WHERE almoxarifado_id=? AND ativo=1"); $stN->execute([$alm['id']]); $nInsumos = (int)$stN->fetchColumn();
      $stF = db()->prepare("SELECT COUNT(*) FROM ferramenta WHERE almoxarifado_id=? AND ativo=1"); $stF->execute([$alm['id']]); $nFerr = (int)$stF->fetchColumn();
      $stE = db()->prepare("SELECT COUNT(*) FROM item_epi WHERE almoxarifado_id=? AND ativo=1"); $stE->execute([$alm['id']]); $nEpis = (int)$stE->fetchColumn();
      $stKT = db()->prepare("SELECT COUNT(*) FROM kit WHERE almoxarifado_id=? AND ativo=1"); $stKT->execute([$alm['id']]); $nKits = (int)$stKT->fetchColumn();
      // Saude do almoxarifado
      $stOK = db()->prepare("SELECT COUNT(*) FROM item WHERE almoxarifado_id=? AND ativo=1 AND quantidade > estoque_minimo"); $stOK->execute([$alm['id']]); $nOK = (int)$stOK->fetchColumn();
      $pct = $nInsumos > 0 ? round($nOK/$nInsumos*100) : 100;
      $pctColor = $pct>=80?'#22c55e':($pct>=50?'#f59e0b':'#ef4444');
      // Estado expandido
      $expanded = $isActiveAlm;
    ?>
    <div class="alm-nav-item">
      <!-- Header do almoxarifado -->
      <div class="d-flex align-items-center" style="padding:0 12px">
        <a href="/almoxarifado/<?= $alm['id'] ?>"
           class="nav-link flex-grow-1 <?= $isActiveAlm?'active':'' ?>"
           style="padding:6px 4px 6px 0">
          <i class="bi bi-building me-1" style="font-size:0.8rem"></i>
          <span class="text-truncate" style="font-size:0.82rem"><?= h($alm['nome']) ?></span>
        </a>
        <span style="font-size:0.72rem;color:<?= $pctColor ?>;font-weight:600;flex-shrink:0"><?= $pct ?>%</span>
        <button class="btn btn-xs border-0 ms-1 p-0 alm-toggle"
                onclick="toggleAlm(<?= $alm['id'] ?>)"
                style="color:var(--text-muted);font-size:0.8rem;width:20px;line-height:1"
                id="btn-alm-<?= $alm['id'] ?>">
          <i class="bi bi-chevron-<?= $expanded?'up':'down' ?>"></i>
        </button>
      </div>
      <!-- Sublinks -->
      <div id="sub-alm-<?= $alm['id'] ?>" style="<?= $expanded?'':'display:none' ?>;padding-left:16px">
        <a href="/almoxarifado/<?= $alm['id'] ?>" class="nav-link py-1 d-flex justify-content-between align-items-center" style="font-size:0.78rem">
          <span><i class="bi bi-boxes me-1"></i>Insumos</span>
          <span class="badge bg-secondary rounded-pill"><?= $nInsumos ?></span>
        </a>
        <a href="/ferramentas?alm=<?= $alm['id'] ?>" class="nav-link py-1 d-flex justify-content-between align-items-center" style="font-size:0.78rem">
          <span><i class="bi bi-tools me-1"></i>Ferramentas</span>
          <span class="badge bg-secondary rounded-pill"><?= $nFerr ?></span>
        </a>
        <a href="/epis?alm=<?= $alm['id'] ?>" class="nav-link py-1 d-flex justify-content-between align-items-center" style="font-size:0.78rem">
          <span><i class="bi bi-shield-check me-1"></i>EPIs / Uniformes</span>
          <span class="badge bg-secondary rounded-pill"><?= $nEpis ?></span>
        </a>
        <a href="/almoxarifado/<?= $alm['id'] ?>/kits" class="nav-link py-1 d-flex justify-content-between align-items-center" style="font-size:0.78rem">
          <span><i class="bi bi-box2-heart me-1"></i>Kits</span>
          <span class="badge bg-secondary rounded-pill"><?= $nKits ?></span>
        </a>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if($u['perfil']==='admin'): ?>
    <a href="/almoxarifado/novo" class="nav-link text-success" style="font-size:0.82rem">
      <i class="bi bi-plus-circle me-2"></i>Novo Almoxarifado
    </a>
    <?php endif; ?>
  </nav>
  <?php endforeach; ?>
  <?php endif; ?>

  <!-- Modulos -->
  <?php if (in_array($u['perfil'], ['admin','almoxarife','analista','tecnico_seguranca'])): ?>
  <div class="nav-section">Módulos</div>
  <nav class="nav flex-column">
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
      <button class="btn btn-sm border-0 d-lg-none" onclick="openSidebar()">
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
<script>
// Sidebar almoxarifado toggle
function toggleAlm(id) {
  const sub = document.getElementById('sub-alm-' + id);
  const ico = document.getElementById('ico-alm-' + id);
  if (!sub) return;
  const isOpen = sub.style.maxHeight && sub.style.maxHeight !== '0px';
  // Usar max-height: 0 vs 200px — sem reflow de scroll
  sub.style.maxHeight = isOpen ? '0' : '200px';
  if (ico) {
    ico.className = isOpen ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
  }
  try {
    const states = JSON.parse(sessionStorage.getItem('almStates') || '{}');
    states[id] = !isOpen;
    sessionStorage.setItem('almStates', JSON.stringify(states));
  } catch(e) {}
}
// Restaurar estados salvos
(function() {
  try {
    const states = JSON.parse(sessionStorage.getItem('almStates') || '{}');
    Object.entries(states).forEach(([id, open]) => {
      const sub = document.getElementById('sub-alm-' + id);
      const btn = document.getElementById('btn-alm-' + id);
      if (!sub) return;
      // Nao sobrescrever o ativo (ja esta expandido pelo PHP)
      if (!sub.closest('.alm-nav-item')?.querySelector('.nav-link.active')) {
        sub.style.display = open ? '' : 'none';
        if (btn) btn.innerHTML = open
          ? '<i class="bi bi-chevron-up"></i>'
          : '<i class="bi bi-chevron-down"></i>';
      }
    });
  } catch(e) {}
})();
</script></body>
</html>
