<?php /* views/dashboard/tecnico.php */ ?>
<?php
$statusBadge = [
    'pendente'  => ['label' => 'Pendente',   'cls' => 'bg-warning'],
    'aprovada'  => ['label' => 'Aprovada',   'cls' => 'bg-success'],
    'entregue'  => ['label' => 'Entregue',   'cls' => 'bg-info'],
    'cancelada' => ['label' => 'Cancelada',  'cls' => 'bg-secondary'],
    'recusada'  => ['label' => 'Recusada',   'cls' => 'bg-danger'],
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="fw-bold mb-0"><i class="bi bi-shield-fill-check me-2" style="color:var(--accent)"></i>Dashboard Técnico</h5>
    <div class="text-muted small">Bem-vindo, <?= h($u['nome']) ?> · <?= h(ucfirst(str_replace('_', ' ', $u['perfil']))) ?></div>
  </div>
  <a href="/requisicoes/mestre/nova" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i>Nova Requisição
  </a>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card p-3 text-center card-stat" style="border-left:4px solid var(--accent) !important">
      <div class="fw-bold fs-2" style="color:var(--accent)"><?= $totalReqs ?></div>
      <div class="text-muted small">Total Requisições</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card p-3 text-center card-stat" style="border-left:4px solid var(--warning) !important">
      <div class="fw-bold fs-2 text-warning"><?= $pendentes ?></div>
      <div class="text-muted small">Pendentes</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card p-3 text-center card-stat" style="border-left:4px solid var(--success) !important">
      <div class="fw-bold fs-2 text-success"><?= $aprovadas ?></div>
      <div class="text-muted small">Aprovadas</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card p-3 text-center card-stat" style="border-left:4px solid var(--info) !important">
      <div class="fw-bold fs-2 text-info"><?= $entregues ?></div>
      <div class="text-muted small">Entregues</div>
    </div>
  </div>
</div>

<!-- Atalhos rápidos -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <a href="/requisicoes/mestre/nova" class="card p-3 text-decoration-none card-stat d-flex flex-row align-items-center gap-3">
      <div class="rounded-3 p-2" style="background:var(--accent-light)">
        <i class="bi bi-plus-square-fill fs-4" style="color:var(--accent)"></i>
      </div>
      <div>
        <div class="fw-semibold text-dark">Nova Requisição</div>
        <div class="text-muted small">Solicitar materiais</div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="/requisicoes/mestre" class="card p-3 text-decoration-none card-stat d-flex flex-row align-items-center gap-3">
      <div class="rounded-3 p-2" style="background:var(--info-light)">
        <i class="bi bi-clipboard-check fs-4 text-info"></i>
      </div>
      <div>
        <div class="fw-semibold text-dark">Minhas Requisições</div>
        <div class="text-muted small">Ver histórico</div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="/epi_modulo" class="card p-3 text-decoration-none card-stat d-flex flex-row align-items-center gap-3">
      <div class="rounded-3 p-2" style="background:var(--success-light)">
        <i class="bi bi-person-badge fs-4 text-success"></i>
      </div>
      <div>
        <div class="fw-semibold text-dark">Módulo EPI</div>
        <div class="text-muted small">Gestão de EPIs</div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="/epis" class="card p-3 text-decoration-none card-stat d-flex flex-row align-items-center gap-3">
      <div class="rounded-3 p-2" style="background:var(--warning-light)">
        <i class="bi bi-shield-check fs-4 text-warning"></i>
      </div>
      <div>
        <div class="fw-semibold text-dark">Ver EPIs</div>
        <div class="text-muted small">Estoque de EPIs</div>
      </div>
    </a>
  </div>
</div>

<!-- Requisições Recentes -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold"><i class="bi bi-clock-history me-1"></i>Requisições Recentes</span>
    <a href="/requisicoes/mestre" class="btn btn-sm btn-outline-secondary">Ver todas</a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
      <thead>
        <tr>
          <th>Protocolo</th>
          <th>Almoxarifado</th>
          <?php if ($u['perfil'] === 'admin'): ?>
          <th>Solicitante</th>
          <?php endif; ?>
          <th class="text-center">Status</th>
          <th class="text-center">Data</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($reqRecentes)): ?>
      <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma requisição encontrada.</td></tr>
      <?php else: ?>
      <?php foreach ($reqRecentes as $req):
        $sc   = $req['status'] ?? 'pendente';
        $sbInfo = $statusBadge[$sc] ?? ['label' => ucfirst($sc), 'cls' => 'bg-secondary'];
      ?>
      <tr>
        <td class="font-monospace small fw-semibold"><?= h($req['protocolo'] ?? '#' . $req['id']) ?></td>
        <td class="text-muted small"><?= h($req['alm_nome'] ?? '—') ?></td>
        <?php if ($u['perfil'] === 'admin'): ?>
        <td class="small"><?= h($req['solicitante_nome'] ?? '—') ?></td>
        <?php endif; ?>
        <td class="text-center">
          <span class="badge <?= $sbInfo['cls'] ?> rounded-pill" style="border-radius:20px">
            <?= $sbInfo['label'] ?>
          </span>
        </td>
        <td class="text-center text-muted small"><?= fmt_data($req['criado_em'] ?? null, 'd/m/Y') ?></td>
        <td class="text-end pe-3">
          <a href="/requisicoes/mestre/<?= $req['id'] ?>" class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2">
            <i class="bi bi-eye"></i>
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
