<?php /* views/dashboard/index.php */ ?>
<div class="row g-3 mb-4">
  <!-- Stat Cards -->
  <div class="col-6 col-md-3">
    <div class="card card-stat p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 p-2" style="background:var(--accent-light)">
          <i class="bi bi-buildings fs-4" style="color:var(--accent)"></i>
        </div>
        <div>
          <div class="fw-bold fs-4"><?= $stats['total_almoxarifados'] ?></div>
          <div class="text-muted small">Almoxarifados</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card card-stat p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 p-2" style="background:var(--info-light)">
          <i class="bi bi-box-seam fs-4" style="color:var(--info)"></i>
        </div>
        <div>
          <div class="fw-bold fs-4"><?= $stats['total_itens'] ?></div>
          <div class="text-muted small">Itens cadastrados</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card card-stat p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 p-2" style="background:var(--warning-light)">
          <i class="bi bi-exclamation-triangle fs-4" style="color:var(--warning)"></i>
        </div>
        <div>
          <div class="fw-bold fs-4"><?= $stats['itens_alerta'] ?></div>
          <div class="text-muted small">Em alerta</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card card-stat p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 p-2" style="background:var(--danger-light)">
          <i class="bi bi-exclamation-octagon fs-4" style="color:var(--danger)"></i>
        </div>
        <div>
          <div class="fw-bold fs-4"><?= $stats['itens_criticos'] ?></div>
          <div class="text-muted small">Críticos (zerados)</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Almoxarifados -->
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-buildings me-2"></i>Almoxarifados</h6>
        <?php if ($u['perfil'] === 'admin'): ?>
        <a href="/almoxarifado/novo" class="btn btn-sm btn-primary">
          <i class="bi bi-plus me-1"></i>Novo
        </a>
        <?php endif; ?>
      </div>
      <div class="card-body p-0">
        <?php if (empty($almoxarifados)): ?>
        <div class="p-4 text-center text-muted">Nenhum almoxarifado encontrado.</div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Nome</th>
                <th>Obra</th>
                <th>Cidade</th>
                <th class="text-center">Itens</th>
                <th class="text-center">Saúde</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($almoxarifados as $alm):
              $nItens = (int)db()->prepare("SELECT COUNT(*) FROM item WHERE almoxarifado_id=? AND ativo=1")->execute([$alm['id']]) ? 0 : 0;
              $stmtI = db()->prepare("SELECT COUNT(*) FROM item WHERE almoxarifado_id=? AND ativo=1");
              $stmtI->execute([$alm['id']]);
              $nItens = (int)$stmtI->fetchColumn();
              $stmtOK = db()->prepare("SELECT COUNT(*) FROM item WHERE almoxarifado_id=? AND ativo=1 AND quantidade > estoque_minimo");
              $stmtOK->execute([$alm['id']]);
              $nOK = (int)$stmtOK->fetchColumn();
              $pct = $nItens > 0 ? round($nOK / $nItens * 100) : 100;
              $pctColor = $pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger');
            ?>
              <tr onclick="location.href='/almoxarifado/<?= $alm['id'] ?>'" style="cursor:pointer">
                <td class="fw-semibold"><?= h($alm['nome']) ?></td>
                <td class="text-muted small"><?= h($alm['obra'] ?? '—') ?></td>
                <td class="text-muted small"><?= h($alm['cidade'] ?? '—') ?></td>
                <td class="text-center"><span class="badge bg-info"><?= $nItens ?></span></td>
                <td style="min-width:100px">
                  <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height:6px">
                      <div class="progress-bar bg-<?= $pctColor ?>" style="width:<?= $pct ?>%"></div>
                    </div>
                    <small class="text-<?= $pctColor ?>"><?= $pct ?>%</small>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Alertas de estoque -->
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-bell me-2 text-warning"></i>Alertas de Estoque</h6>
      </div>
      <div class="card-body p-0" style="max-height:340px;overflow-y:auto">
        <?php if (empty($alertas)): ?>
        <div class="p-4 text-center text-muted">
          <i class="bi bi-check-circle fs-3 text-success"></i>
          <div class="mt-2">Estoque saudável!</div>
        </div>
        <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($alertas as $al):
            $st = status_item((float)$al['quantidade'], (float)$al['estoque_minimo']);
            $icon = $st === 'critico' ? 'exclamation-octagon text-danger' : 'exclamation-triangle text-warning';
          ?>
          <li class="list-group-item d-flex justify-content-between align-items-start py-2">
            <div class="flex-grow-1 overflow-hidden me-2">
              <div class="fw-semibold text-truncate small"><?= h($al['nome']) ?></div>
              <div class="text-muted" style="font-size:0.72rem"><?= h($al['alm_nome']) ?></div>
            </div>
            <div class="text-end flex-shrink-0">
              <i class="bi bi-<?= $icon ?>"></i>
              <div class="text-muted" style="font-size:0.72rem">
                <?= fmt_qtd((float)$al['quantidade']) ?>/<?= fmt_qtd((float)$al['estoque_minimo']) ?> <?= h($al['unidade']) ?>
              </div>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>

    <!-- Requisicoes pendentes -->
    <?php if ($stats['req_pendentes'] > 0): ?>
    <div class="card mt-3 border-warning">
      <div class="card-body py-2 px-3 d-flex align-items-center gap-3">
        <i class="bi bi-clipboard-check fs-4 text-warning"></i>
        <div class="flex-grow-1">
          <div class="fw-semibold"><?= $stats['req_pendentes'] ?> requisição(ões) pendente(s)</div>
          <div class="text-muted small">Aguardando aprovação</div>
        </div>
        <a href="/requisicoes/mestre" class="btn btn-sm btn-warning">Ver</a>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Movimentacoes recentes -->
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Movimentações Recentes</h6>
        <a href="/movimentacao/lote" class="btn btn-sm btn-outline-primary">
          <i class="bi bi-plus me-1"></i>Nova
        </a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Data</th>
              <th>Item</th>
              <th>Almoxarifado</th>
              <th>Tipo</th>
              <th>Qtd</th>
              <th>Responsável</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($movRecentes)): ?>
            <tr><td colspan="6" class="text-center text-muted py-3">Nenhuma movimentação recente.</td></tr>
          <?php else: ?>
          <?php foreach ($movRecentes as $m): ?>
            <tr>
              <td class="text-muted small"><?= fmt_data($m['data']) ?></td>
              <td><?= h($m['item_nome']) ?></td>
              <td class="text-muted small"><?= h($m['alm_nome']) ?></td>
              <td>
                <?php if ($m['tipo'] === 'entrada'): ?>
                  <span class="badge bg-success"><i class="bi bi-arrow-down me-1"></i>Entrada</span>
                <?php else: ?>
                  <span class="badge bg-warning"><i class="bi bi-arrow-up me-1"></i>Saída</span>
                <?php endif; ?>
              </td>
              <td><?= fmt_qtd((float)$m['quantidade']) ?> <?= h($m['unidade']) ?></td>
              <td class="text-muted small"><?= h($m['responsavel'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
