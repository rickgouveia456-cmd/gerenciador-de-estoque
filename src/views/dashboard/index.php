<?php /* views/dashboard/index.php */ ?>

<!-- Stats compactos -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card p-3 d-flex flex-row align-items-center gap-3">
      <div class="rounded-3 p-2" style="background:var(--info-light)">
        <i class="bi bi-buildings fs-5" style="color:var(--info)"></i>
      </div>
      <div>
        <div class="text-muted small">Almoxarifados</div>
        <div class="fw-bold fs-4"><?= $stats['total_almoxarifados'] ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 d-flex flex-row align-items-center gap-3">
      <div class="rounded-3 p-2" style="background:var(--accent-light)">
        <i class="bi bi-box-seam fs-5" style="color:var(--accent)"></i>
      </div>
      <div>
        <div class="text-muted small">Total de Itens</div>
        <div class="fw-bold fs-4"><?= $stats['total_itens'] ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 d-flex flex-row align-items-center gap-3">
      <div class="rounded-3 p-2" style="background:var(--warning-light)">
        <i class="bi bi-exclamation-triangle fs-5" style="color:var(--warning)"></i>
      </div>
      <div>
        <div class="text-muted small">Em Alerta</div>
        <div class="fw-bold fs-4" style="color:var(--warning)"><?= $stats['itens_alerta'] ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 d-flex flex-row align-items-center gap-3">
      <div class="rounded-3 p-2" style="background:var(--danger-light)">
        <i class="bi bi-exclamation-octagon fs-5" style="color:var(--danger)"></i>
      </div>
      <div>
        <div class="text-muted small">Críticos (zerados)</div>
        <div class="fw-bold fs-4" style="color:var(--danger)"><?= $stats['itens_criticos'] ?></div>
      </div>
    </div>
  </div>
</div>

<!-- Alertas de Estoque -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold"><i class="bi bi-bell-fill me-2 text-warning"></i>Alertas de Estoque</span>
    <a href="/relatorios/alertas" class="btn btn-sm btn-outline-secondary">Ver todos →</a>
  </div>
  <div class="card-body p-0">
    <?php if (empty($alertas)): ?>
    <div class="text-center py-5">
      <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
      <div class="fw-semibold text-success">Estoque saudável!</div>
      <div class="text-muted small">Todos os itens estão acima do estoque mínimo.</div>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Item</th>
            <th>Almoxarifado</th>
            <th class="text-center">Qtd Atual</th>
            <th class="text-center">Mínimo</th>
            <th class="text-center">Status</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($alertas as $al):
          $st = status_item((float)$al['quantidade'], (float)$al['estoque_minimo']);
          $cls = $st === 'critico' ? 'danger' : 'warning';
        ?>
        <tr>
          <td>
            <a href="/item/<?= $al['id'] ?>" class="fw-semibold text-decoration-none text-dark">
              <?= h($al['nome']) ?>
            </a>
            <div class="font-monospace text-muted" style="font-size:0.73rem"><?= h($al['codigo']) ?></div>
          </td>
          <td class="text-muted small"><?= h($al['alm_nome']) ?></td>
          <td class="text-center fw-bold text-<?= $cls ?>">
            <?= fmt_qtd((float)$al['quantidade']) ?> <span class="text-muted fw-normal small"><?= h($al['unidade']) ?></span>
          </td>
          <td class="text-center text-muted small"><?= fmt_qtd((float)$al['estoque_minimo']) ?></td>
          <td class="text-center"><?= status_badge($st) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>
