<div class="page-header d-flex align-items-center gap-3 mb-4">
  <div class="page-header-icon" style="background:linear-gradient(135deg,#059669,#10b981)">
    <i class="bi bi-activity"></i>
  </div>
  <div>
    <h1 class="page-title">Saúde do Sistema</h1>
    <p class="page-sub">Monitoramento em tempo real — atualizado em <?= date('d/m/Y H:i:s') ?></p>
  </div>
  <button class="btn btn-sm btn-outline-secondary ms-auto" onclick="location.reload()">
    <i class="bi bi-arrow-clockwise me-1"></i> Atualizar
  </button>
</div>

<!-- ── STATUS GERAL ──────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">

  <!-- Banco de dados -->
  <div class="col-md-3">
    <div class="card border-0 shadow-sm h-100" style="border-left:4px solid <?= $db_ok ? '#10b981' : '#ef4444' ?> !important">
      <div class="card-body">
        <div class="d-flex align-items-center gap-2 mb-2">
          <span style="font-size:1.4rem"><?= $db_ok ? '🟢' : '🔴' ?></span>
          <span class="fw-bold">Banco de Dados</span>
        </div>
        <div class="small text-muted">MariaDB <?= h($db_info) ?></div>
        <div class="small text-muted"><?= h($db_size) ?> MB em uso</div>
        <span class="badge mt-2 <?= $db_ok ? 'bg-success' : 'bg-danger' ?>">
          <?= $db_ok ? 'Online' : 'Offline' ?>
        </span>
      </div>
    </div>
  </div>

  <!-- Itens críticos -->
  <div class="col-md-3">
    <div class="card border-0 shadow-sm h-100" style="border-left:4px solid <?= $criticos > 0 ? '#ef4444' : '#10b981' ?> !important">
      <div class="card-body">
        <div class="d-flex align-items-center gap-2 mb-2">
          <span style="font-size:1.4rem"><?= $criticos > 0 ? '🔴' : '🟢' ?></span>
          <span class="fw-bold">Estoque Crítico</span>
        </div>
        <div style="font-size:2rem;font-weight:800;color:<?= $criticos > 0 ? '#ef4444' : '#10b981' ?>"><?= $criticos ?></div>
        <div class="small text-muted">itens zerados</div>
        <?php if ($alertas > 0): ?>
        <div class="small text-warning mt-1">⚠️ <?= $alertas ?> em alerta</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Requisições pendentes -->
  <div class="col-md-3">
    <div class="card border-0 shadow-sm h-100" style="border-left:4px solid <?= $req_pendentes > 0 ? '#f59e0b' : '#10b981' ?> !important">
      <div class="card-body">
        <div class="d-flex align-items-center gap-2 mb-2">
          <span style="font-size:1.4rem"><?= $req_pendentes > 0 ? '🟡' : '🟢' ?></span>
          <span class="fw-bold">Requisições</span>
        </div>
        <div style="font-size:2rem;font-weight:800;color:<?= $req_pendentes > 0 ? '#f59e0b' : '#10b981' ?>"><?= $req_pendentes ?></div>
        <div class="small text-muted">aguardando aprovação</div>
        <a href="/requisicoes/mestre" class="small text-primary mt-1 d-block">Ver requisições →</a>
      </div>
    </div>
  </div>

  <!-- Integração Sienge -->
  <div class="col-md-3">
    <div class="card border-0 shadow-sm h-100" style="border-left:4px solid <?= $sienge_ativo ? '#10b981' : '#6b7280' ?> !important">
      <div class="card-body">
        <div class="d-flex align-items-center gap-2 mb-2">
          <span style="font-size:1.4rem"><?= $sienge_ativo ? '🟢' : '⚪' ?></span>
          <span class="fw-bold">Sienge</span>
        </div>
        <div class="small text-muted">Integração ERP</div>
        <span class="badge mt-2 <?= $sienge_ativo ? 'bg-success' : 'bg-secondary' ?>">
          <?= $sienge_ativo ? 'Conectado' : 'Não configurado' ?>
        </span>
        <a href="/integracao" class="small text-primary mt-1 d-block">Configurar →</a>
      </div>
    </div>
  </div>

</div>

<!-- ── ATIVIDADE 24H + TOTAIS ────────────────────────────────────────────────── -->
<div class="row g-4 mb-4">

  <!-- Atividade hoje -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header fw-bold">
        <i class="bi bi-clock-history me-2 text-muted"></i>Últimas 24 horas
      </div>
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <span class="text-muted small">Movimentações</span>
          <strong class="text-primary"><?= $movs_hoje ?></strong>
        </div>
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <span class="text-muted small">Novas Requisições</span>
          <strong class="text-warning"><?= $reqs_hoje ?></strong>
        </div>
        <div class="d-flex justify-content-between align-items-center py-2">
          <span class="text-muted small">Usuários Ativos</span>
          <strong class="text-success"><?= $usuarios_ativos ?></strong>
        </div>
        <?php if ($data_primeiro_uso): ?>
        <div class="small text-muted mt-3 pt-2 border-top">
          <i class="bi bi-calendar3 me-1"></i>Em uso desde <?= date('d/m/Y', strtotime($data_primeiro_uso)) ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Totais do banco -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header fw-bold">
        <i class="bi bi-database me-2 text-muted"></i>Totais no Banco
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
          <tbody>
            <?php foreach ($totais as $label => $n): ?>
            <tr>
              <td class="ps-3 text-muted small"><?= h($label) ?></td>
              <td class="pe-3 text-end fw-bold"><?= is_numeric($n) ? number_format((int)$n) : $n ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Últimas movimentações -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header fw-bold">
        <i class="bi bi-arrow-left-right me-2 text-muted"></i>Últimas Movimentações
      </div>
      <div class="card-body p-0">
        <?php if (empty($ultimas_movs)): ?>
        <div class="text-center py-4 text-muted small">Nenhuma movimentação ainda.</div>
        <?php else: ?>
        <div class="list-group list-group-flush">
          <?php foreach ($ultimas_movs as $m): ?>
          <div class="list-group-item px-3 py-2">
            <div class="d-flex justify-content-between align-items-start">
              <div style="font-size:.8rem">
                <span class="badge <?= $m['tipo'] === 'entrada' ? 'bg-success' : 'bg-danger' ?> me-1">
                  <?= $m['tipo'] === 'entrada' ? '↑' : '↓' ?>
                </span>
                <strong><?= h($m['item_nome']) ?></strong>
                <div class="text-muted"><?= h($m['alm_nome']) ?></div>
              </div>
              <small class="text-muted text-nowrap ms-2"><?= tempo_relativo($m['data']) ?></small>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<!-- ── ERROS DE INTEGRAÇÃO ────────────────────────────────────────────────────── -->
<?php if (!empty($erros_integracao)): ?>
<div class="card border-0 shadow-sm border-danger mb-4" style="border-left:4px solid #ef4444 !important">
  <div class="card-header fw-bold text-danger">
    <i class="bi bi-exclamation-triangle me-2"></i>Últimos Erros de Integração
  </div>
  <div class="card-body p-0">
    <table class="table table-sm mb-0">
      <thead class="table-light">
        <tr><th>Data</th><th>Operação</th><th>Erro</th></tr>
      </thead>
      <tbody>
        <?php foreach ($erros_integracao as $e): ?>
        <tr>
          <td class="small text-nowrap"><?= date('d/m H:i', strtotime($e['criado_em'])) ?></td>
          <td class="small"><?= h($e['operacao']) ?></td>
          <td class="small text-danger"><?= h($e['erro_msg'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ── LINKS RÁPIDOS ────────────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
  <div class="card-header fw-bold">
    <i class="bi bi-lightning me-2 text-muted"></i>Ações Rápidas de Administração
  </div>
  <div class="card-body d-flex flex-wrap gap-2">
    <a href="/admin/configuracao"   class="btn btn-outline-primary btn-sm"><i class="bi bi-gear me-1"></i>Configurações</a>
    <a href="/integracao"           class="btn btn-outline-secondary btn-sm"><i class="bi bi-plug me-1"></i>Integrações</a>
    <a href="/admin/backup"         class="btn btn-outline-success btn-sm"><i class="bi bi-cloud-download me-1"></i>Backup</a>
    <a href="/relatorios/alertas"   class="btn btn-outline-warning btn-sm"><i class="bi bi-bell me-1"></i>Alertas de Estoque</a>
    <a href="/admin/reativar_itens" class="btn btn-outline-info btn-sm"><i class="bi bi-arrow-repeat me-1"></i>Reativar Itens</a>
    <a href="/usuarios"             class="btn btn-outline-dark btn-sm"><i class="bi bi-people me-1"></i>Usuários</a>
  </div>
</div>

<script>
// Auto-refresh a cada 60 segundos
setTimeout(() => location.reload(), 60000);
</script>
