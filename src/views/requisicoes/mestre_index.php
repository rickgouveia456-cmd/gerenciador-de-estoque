<?php /* views/requisicoes/mestre_index.php */ ?>

<div class="mb-3"><a href="/" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Dashboard</a></div>
<?php
$perfilCores = [
    'admin'             => '#7c3aed',
    'almoxarife'        => '#ff6b35',
    'mestre'            => '#f0a500',
    'tecnico_seguranca' => '#059669',
    'analista'          => '#2563eb',
    'colaborador'       => '#64748b',
];
$statusMap = [
    'pendente'  => ['cls' => 'warning',   'ico' => 'clock',        'lbl' => 'Pendente'],
    'aprovada'  => ['cls' => 'info',      'ico' => 'check-circle', 'lbl' => 'Em Separação'],
    'entregue'  => ['cls' => 'success',   'ico' => 'bag-check',    'lbl' => 'Entregue'],
    'recusada'  => ['cls' => 'danger',    'ico' => 'x-circle',     'lbl' => 'Recusada'],
    'cancelada' => ['cls' => 'secondary', 'ico' => 'slash-circle', 'lbl' => 'Cancelada'],
];

function avatarIniciaisReq(string $nome): string {
    $partes = explode(' ', trim($nome));
    if (count($partes) === 1) return strtoupper(mb_substr($partes[0], 0, 2));
    return strtoupper(mb_substr($partes[0], 0, 1) . mb_substr(end($partes), 0, 1));
}
?>

<!-- Header -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div>
    <h5 class="fw-bold mb-1"><i class="bi bi-clipboard-check me-2"></i>Requisições de Materiais</h5>
    <div class="text-muted small">Gerencie e acompanhe todas as solicitações de materiais</div>
  </div>
  <?php if (in_array($u['perfil'], ['mestre','tecnico_seguranca','admin','almoxarife']) || $u['pode_requisitar']): ?>
  <a href="/requisicoes/mestre/nova" class="btn btn-primary">
    <i class="bi bi-plus me-1"></i>Nova Requisição
  </a>
  <?php endif; ?>
</div>

<!-- Stats cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card p-3 stat-req" style="cursor:pointer;border-left:4px solid var(--warning) !important"
         onclick="filtrarReqs('pendente')">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-2 d-flex align-items-center justify-content-center"
             style="width:38px;height:38px;background:var(--warning-light);flex-shrink:0">
          <i class="bi bi-clock" style="color:var(--warning);font-size:1.1rem"></i>
        </div>
        <div>
          <div class="fw-bold fs-3 mb-0" style="color:var(--warning)"><?= $stats['pendentes'] ?></div>
          <div class="text-muted small">Pendentes</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 stat-req" style="cursor:pointer;border-left:4px solid var(--info) !important"
         onclick="filtrarReqs('aprovada')">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-2 d-flex align-items-center justify-content-center"
             style="width:38px;height:38px;background:var(--info-light);flex-shrink:0">
          <i class="bi bi-box-seam" style="color:var(--info);font-size:1.1rem"></i>
        </div>
        <div>
          <div class="fw-bold fs-3 mb-0" style="color:var(--info)"><?= $stats['em_separacao'] ?></div>
          <div class="text-muted small">Em Separação</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 stat-req" style="cursor:pointer;border-left:4px solid var(--success) !important"
         onclick="filtrarReqs('entregue')">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-2 d-flex align-items-center justify-content-center"
             style="width:38px;height:38px;background:var(--success-light);flex-shrink:0">
          <i class="bi bi-bag-check" style="color:var(--success);font-size:1.1rem"></i>
        </div>
        <div>
          <div class="fw-bold fs-3 mb-0" style="color:var(--success)"><?= $stats['entregues'] ?></div>
          <div class="text-muted small">Entregues</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 stat-req" style="cursor:pointer;border-left:4px solid var(--accent) !important"
         onclick="filtrarReqs('')">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-2 d-flex align-items-center justify-content-center"
             style="width:38px;height:38px;background:var(--accent-light);flex-shrink:0">
          <i class="bi bi-clipboard-data" style="color:var(--accent);font-size:1.1rem"></i>
        </div>
        <div>
          <div class="fw-bold fs-3 mb-0" style="color:var(--accent)"><?= $stats['total'] ?></div>
          <div class="text-muted small">Total</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Filtros + busca -->
<div class="card p-3 mb-4">
  <div class="d-flex flex-wrap gap-2 align-items-center">
    <div class="d-flex flex-wrap gap-1 flex-grow-1" id="pillsStatus">
      <?php
      $statusPills = [
        ''          => ['emoji' => '📋', 'label' => 'Todas',        'cor' => '#64748b'],
        'pendente'  => ['emoji' => '⏳', 'label' => 'Pendentes',    'cor' => '#d97706'],
        'aprovada'  => ['emoji' => '✅', 'label' => 'Aprovadas',    'cor' => '#2563eb'],
        'entregue'  => ['emoji' => '📦', 'label' => 'Entregues',    'cor' => '#059669'],
        'recusada'  => ['emoji' => '❌', 'label' => 'Recusadas',    'cor' => '#dc2626'],
        'cancelada' => ['emoji' => '🚫', 'label' => 'Canceladas',   'cor' => '#64748b'],
      ];
      foreach ($statusPills as $sv => $sp):
      ?>
      <button class="btn btn-sm rounded-pill pill-status"
              id="pill-status-<?= $sv === '' ? 'todos' : $sv ?>"
              onclick="filtrarReqs('<?= $sv ?>')"
              data-status="<?= $sv ?>"
              style="background:<?= $sp['cor'] ?>18;color:<?= $sp['cor'] ?>;border:1px solid <?= $sp['cor'] ?>40;font-size:0.8rem">
        <?= $sp['emoji'] ?> <?= $sp['label'] ?>
      </button>
      <?php endforeach; ?>
    </div>
    <div class="input-group input-group-sm" style="max-width:260px">
      <span class="input-group-text"><i class="bi bi-search"></i></span>
      <input type="text" id="buscaReq" class="form-control"
             placeholder="Buscar protocolo ou colaborador..."
             oninput="buscarReq(this.value)">
    </div>
  </div>
</div>

<!-- Tabela desktop -->
<div class="card d-none d-md-block">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th style="width:40px">#</th>
          <th>Protocolo</th>
          <th>Solicitante</th>
          <th>Colaborador</th>
          <th>Almoxarifado</th>
          <th class="text-center">Itens</th>
          <th>Data</th>
          <th class="text-center">Status</th>
          <th class="text-center" style="width:70px">Ação</th>
        </tr>
      </thead>
      <tbody id="tbodyReqs">
      <?php if (empty($requisicoes)): ?>
        <tr><td colspan="9" class="text-center text-muted py-5">
          <i class="bi bi-clipboard fs-2 mb-2 d-block"></i>Nenhuma requisição encontrada.
        </td></tr>
      <?php endif; ?>
      <?php foreach ($requisicoes as $idx => $r):
        $sm = $statusMap[$r['status']] ?? ['cls' => 'secondary', 'ico' => 'question', 'lbl' => $r['status']];
        $corSolic = $perfilCores[$r['perfil'] ?? 'colaborador'] ?? '#64748b';
        $iniciais = avatarIniciaisReq($r['mestre_nome'] ?? '?');
      ?>
        <tr class="req-row" data-status="<?= h($r['status']) ?>"
            data-busca="<?= h(strtolower(($r['protocolo'] ?? '') . ' ' . $r['colaborador'] . ' ' . ($r['mestre_nome'] ?? ''))) ?>">
          <td class="text-muted small"><?= $pag['offset'] + $idx + 1 ?></td>
          <td>
            <a href="/requisicoes/mestre/<?= $r['id'] ?>"
               class="fw-semibold text-decoration-none font-monospace"
               style="color:var(--accent);font-size:0.85rem">
              <?= h($r['protocolo'] ?? 'REQ-' . $r['id']) ?>
            </a>
          </td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                   style="width:30px;height:30px;background:<?= $corSolic ?>;font-size:0.7rem">
                <?= h($iniciais) ?>
              </div>
              <span class="small fw-semibold"><?= h($r['mestre_nome'] ?? '—') ?></span>
            </div>
          </td>
          <td class="fw-semibold small"><?= h($r['colaborador']) ?></td>
          <td class="text-muted small"><?= h($r['alm_nome']) ?></td>
          <td class="text-center">
            <span class="badge rounded-pill" style="background:var(--accent-light);color:var(--accent)">
              <?= (int)($r['total_itens'] ?? 0) ?>
            </span>
          </td>
          <td class="text-muted small"><?= fmt_data($r['data_criacao'], 'd/m/Y H:i') ?></td>
          <td class="text-center">
            <span class="badge rounded-pill bg-<?= $sm['cls'] ?>">
              <i class="bi bi-<?= $sm['ico'] ?> me-1"></i><?= $sm['lbl'] ?>
            </span>
          </td>
          <td class="text-center">
            <a href="/requisicoes/mestre/<?= $r['id'] ?>"
               class="btn btn-sm btn-outline-primary" title="Ver detalhe">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pag['total_pages'] > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center">
    <small class="text-muted"><?= $total ?> resultado(s)</small>
    <nav>
      <ul class="pagination pagination-sm mb-0">
        <?php for ($p = 1; $p <= $pag['total_pages']; $p++): ?>
        <li class="page-item <?= $p === $pag['page'] ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $p ?>&status=<?= h($status) ?>&busca=<?= h($busca) ?>">
            <?= $p ?>
          </a>
        </li>
        <?php endfor; ?>
      </ul>
    </nav>
  </div>
  <?php endif; ?>
</div>

<!-- Cards mobile -->
<div class="d-md-none" id="cardsMobileReqs">
  <?php if (empty($requisicoes)): ?>
  <div class="card p-4 text-center text-muted">
    <i class="bi bi-clipboard fs-2 mb-2 d-block"></i>Nenhuma requisição encontrada.
  </div>
  <?php endif; ?>
  <?php foreach ($requisicoes as $r):
    $sm = $statusMap[$r['status']] ?? ['cls' => 'secondary', 'ico' => 'question', 'lbl' => $r['status']];
  ?>
  <div class="card mb-2 req-card" data-status="<?= h($r['status']) ?>"
       data-busca="<?= h(strtolower(($r['protocolo'] ?? '') . ' ' . $r['colaborador'])) ?>">
    <div class="card-body p-3">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <a href="/requisicoes/mestre/<?= $r['id'] ?>"
           class="fw-semibold font-monospace text-decoration-none"
           style="color:var(--accent);font-size:0.85rem">
          <?= h($r['protocolo'] ?? 'REQ-' . $r['id']) ?>
        </a>
        <span class="badge rounded-pill bg-<?= $sm['cls'] ?>"><?= $sm['lbl'] ?></span>
      </div>
      <div class="fw-semibold small"><?= h($r['colaborador']) ?></div>
      <div class="text-muted small"><?= h($r['alm_nome']) ?> · <?= fmt_data($r['data_criacao'], 'd/m/Y H:i') ?></div>
      <div class="mt-2">
        <a href="/requisicoes/mestre/<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary w-100">
          <i class="bi bi-eye me-1"></i>Ver Detalhe
        </a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div id="semReqs" class="text-center py-5 d-none">
  <i class="bi bi-clipboard-x fs-1 text-muted mb-3"></i>
  <div class="fw-semibold text-muted">Nenhuma requisição encontrada.</div>
</div>

<style>
.stat-req:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.1) !important; }
</style>

<script>
let _filtroStatus = '';
let _filtroBuscaReq = '';

function filtrarReqs(st) {
  _filtroStatus = st;
  // Atualizar pills
  document.querySelectorAll('.pill-status').forEach(el => {
    el.style.fontWeight = '';
    el.style.boxShadow = '';
  });
  const pid = st === '' ? 'pill-status-todos' : 'pill-status-' + st;
  const pill = document.getElementById(pid);
  if (pill) { pill.style.fontWeight = '700'; pill.style.boxShadow = '0 0 0 2px currentColor'; }
  aplicarFiltrosReq();
}

function buscarReq(q) {
  _filtroBuscaReq = q.toLowerCase().trim();
  aplicarFiltrosReq();
}

function aplicarFiltrosReq() {
  const rows  = document.querySelectorAll('.req-row, .req-card');
  let visiveis = 0;
  rows.forEach(el => {
    const stMatch = !_filtroStatus || el.dataset.status === _filtroStatus;
    const bqMatch = !_filtroBuscaReq || el.dataset.busca.includes(_filtroBuscaReq);
    if (stMatch && bqMatch) { el.style.display = ''; visiveis++; }
    else el.style.display = 'none';
  });
  document.getElementById('semReqs').classList.toggle('d-none', visiveis > 0);
}
</script>
