<?php /* views/dashboard/index.php — redesign clean */ ?>

<!-- ── Saudação ────────────────────────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h1 style="font-size:1.5rem;font-weight:800;margin:0">
      <?php
        $h = (int)date('H');
        echo $h < 12 ? 'Bom dia' : ($h < 18 ? 'Boa tarde' : 'Boa noite');
      ?>, <?= h(explode(' ', $u['nome'])[0]) ?> 👋
    </h1>
    <p class="text-muted mb-0" style="font-size:.88rem">
      <?= date('l, d \d\e F \d\e Y') ?>
    </p>
  </div>
  <a href="/movimentacao/lote" class="btn btn-primary btn-sm px-3">
    <i class="bi bi-arrow-left-right me-1"></i> Nova Movimentação
  </a>
</div>

<!-- ── Cards de métricas ───────────────────────────────────────────────────── -->
<div class="row g-3 mb-4" id="stats-row">

  <div class="col-6 col-xl-3">
    <div class="dash-card">
      <div class="dash-card-icon" style="--c:#0ea5e9">
        <i class="bi bi-buildings"></i>
      </div>
      <div class="dash-card-val"><?= $stats['total_almoxarifados'] ?></div>
      <div class="dash-card-label">Almoxarifados</div>
    </div>
  </div>

  <div class="col-6 col-xl-3">
    <div class="dash-card">
      <div class="dash-card-icon" style="--c:#8b5cf6">
        <i class="bi bi-box-seam"></i>
      </div>
      <div class="dash-card-val"><?= number_format($stats['total_itens']) ?></div>
      <div class="dash-card-label">Itens em Estoque</div>
    </div>
  </div>

  <div class="col-6 col-xl-3">
    <div class="dash-card <?= $stats['itens_alerta'] > 0 ? 'dash-card--warn' : '' ?>">
      <div class="dash-card-icon" style="--c:#f59e0b">
        <i class="bi bi-exclamation-triangle"></i>
      </div>
      <div class="dash-card-val" style="<?= $stats['itens_alerta'] > 0 ? 'color:#f59e0b' : '' ?>">
        <?= $stats['itens_alerta'] ?>
      </div>
      <div class="dash-card-label">Em Alerta</div>
      <?php if ($stats['itens_alerta'] > 0): ?>
      <a href="/relatorios/alertas" class="dash-card-action">Ver →</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-6 col-xl-3">
    <div class="dash-card <?= $stats['itens_criticos'] > 0 ? 'dash-card--danger' : '' ?>">
      <div class="dash-card-icon" style="--c:#ef4444">
        <i class="bi bi-exclamation-octagon"></i>
      </div>
      <div class="dash-card-val" style="<?= $stats['itens_criticos'] > 0 ? 'color:#ef4444' : '' ?>">
        <?= $stats['itens_criticos'] ?>
      </div>
      <div class="dash-card-label">Zerados</div>
      <?php if ($stats['itens_criticos'] > 0): ?>
      <a href="/relatorios/alertas" class="dash-card-action">Resolver →</a>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- ── Conteúdo principal ─────────────────────────────────────────────────── -->
<div class="row g-4">

  <!-- Alertas de estoque -->
  <div class="col-12 col-xl-8">
    <div class="dash-panel">
      <div class="dash-panel-header">
        <span><i class="bi bi-bell me-2 text-warning"></i>Alertas de Estoque</span>
        <a href="/relatorios/alertas" class="dash-panel-link">Ver todos →</a>
      </div>

      <?php if (empty($alertas)): ?>
      <div class="dash-empty">
        <i class="bi bi-check-circle-fill text-success"></i>
        <span>Estoque saudável — nenhum alerta no momento</span>
      </div>
      <?php else: ?>
      <div class="dash-alert-list">
        <?php foreach (array_slice($alertas, 0, 8) as $al):
          $st  = status_item((float)$al['quantidade'], (float)$al['estoque_minimo']);
          $cor = $st === 'critico' ? '#ef4444' : '#f59e0b';
        ?>
        <a href="/item/<?= $al['id'] ?>" class="dash-alert-row">
          <div class="dash-alert-dot" style="background:<?= $cor ?>"></div>
          <div class="dash-alert-info">
            <span class="dash-alert-nome"><?= h($al['nome']) ?></span>
            <span class="dash-alert-alm"><?= h($al['alm_nome']) ?></span>
          </div>
          <div class="dash-alert-qtd" style="color:<?= $cor ?>">
            <?= fmt_qtd((float)$al['quantidade']) ?>
            <span style="color:var(--text-muted);font-weight:400"><?= h($al['unidade']) ?></span>
          </div>
          <?php echo status_badge($st) ?>
        </a>
        <?php endforeach; ?>
        <?php if (count($alertas) > 8): ?>
        <a href="/relatorios/alertas" class="dash-ver-mais">
          + <?= count($alertas) - 8 ?> itens a mais →
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Ações rápidas + previsão -->
  <div class="col-12 col-xl-4 d-flex flex-column gap-4">

    <!-- Ações rápidas -->
    <div class="dash-panel">
      <div class="dash-panel-header">
        <span><i class="bi bi-lightning me-2 text-primary"></i>Ações Rápidas</span>
      </div>
      <div class="d-flex flex-column gap-2 p-3">
        <a href="/movimentacao/lote"    class="dash-action-btn"><i class="bi bi-arrow-left-right"></i> Registrar Movimentação</a>
        <a href="/requisicoes/mestre/nova" class="dash-action-btn"><i class="bi bi-clipboard-plus"></i> Nova Requisição</a>
        <a href="/relatorios/consumo"   class="dash-action-btn"><i class="bi bi-graph-down"></i> Relatório de Consumo</a>
        <a href="/epi_modulo"           class="dash-action-btn"><i class="bi bi-shield-check"></i> Módulo EPI</a>
      </div>
    </div>

    <!-- Previsão de ruptura -->
    <?php if (!empty($ruptura)): ?>
    <div class="dash-panel">
      <div class="dash-panel-header">
        <span><i class="bi bi-graph-down-arrow me-2 text-danger"></i>Previsão de Ruptura</span>
      </div>
      <div class="dash-alert-list">
        <?php foreach (array_slice($ruptura, 0, 5) as $r): ?>
        <a href="/item/<?= $r['item']['id'] ?>" class="dash-alert-row">
          <div class="dash-alert-dot" style="background:#f97316"></div>
          <div class="dash-alert-info">
            <span class="dash-alert-nome"><?= h($r['item']['nome']) ?></span>
            <span class="dash-alert-alm" style="color:#f97316">
              Zera em ~<?= $r['dias_ate_ruptura'] ?> dias
            </span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<!-- ── Estilos do dashboard ───────────────────────────────────────────────── -->
<style>
/* Cards de métrica */
.dash-card {
  background: var(--card-bg, #fff);
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 14px;
  padding: 20px 18px;
  position: relative;
  transition: box-shadow .2s, transform .2s;
}
.dash-card:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(0,0,0,.07); }
.dash-card--warn   { border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.04); }
.dash-card--danger { border-color: rgba(239,68,68,.35);  background: rgba(239,68,68,.04); }

.dash-card-icon {
  width: 40px; height: 40px; border-radius: 10px;
  background: color-mix(in srgb, var(--c) 12%, transparent);
  color: var(--c);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; margin-bottom: 12px;
}
.dash-card-val   { font-size: 2rem; font-weight: 800; line-height: 1; margin-bottom: 4px; }
.dash-card-label { font-size: .78rem; color: var(--text-muted, #6b7280); font-weight: 500; }
.dash-card-action {
  position: absolute; top: 16px; right: 16px;
  font-size: .75rem; font-weight: 700; color: var(--accent, #f97316);
  text-decoration: none; opacity: .8;
}
.dash-card-action:hover { opacity: 1; }

/* Painéis */
.dash-panel {
  background: var(--card-bg, #fff);
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 14px; overflow: hidden;
}
.dash-panel-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 18px;
  border-bottom: 1px solid var(--border-color, #e5e7eb);
  font-size: .88rem; font-weight: 700;
}
.dash-panel-link {
  font-size: .78rem; color: var(--accent, #f97316);
  text-decoration: none; font-weight: 600; opacity: .8;
}
.dash-panel-link:hover { opacity: 1; }

/* Lista de alertas */
.dash-alert-list { display: flex; flex-direction: column; }
.dash-alert-row {
  display: flex; align-items: center; gap: 12px;
  padding: 11px 18px;
  border-bottom: 1px solid var(--border-color, #f3f4f6);
  text-decoration: none; color: inherit;
  transition: background .15s;
}
.dash-alert-row:last-child { border-bottom: none; }
.dash-alert-row:hover { background: rgba(0,0,0,.025); }

.dash-alert-dot {
  width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}
.dash-alert-info {
  flex: 1; min-width: 0;
  display: flex; flex-direction: column; gap: 1px;
}
.dash-alert-nome {
  font-size: .85rem; font-weight: 600;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.dash-alert-alm { font-size: .72rem; color: var(--text-muted, #6b7280); }
.dash-alert-qtd { font-size: .88rem; font-weight: 700; flex-shrink: 0; }

.dash-ver-mais {
  display: block; text-align: center;
  padding: 10px; font-size: .8rem;
  color: var(--accent, #f97316); text-decoration: none;
  border-top: 1px solid var(--border-color, #f3f4f6);
}

/* Vazio */
.dash-empty {
  display: flex; align-items: center; gap: 10px;
  justify-content: center; padding: 32px;
  font-size: .88rem; color: var(--text-muted, #6b7280);
}
.dash-empty i { font-size: 1.4rem; }

/* Ações rápidas */
.dash-action-btn {
  display: flex; align-items: center; gap: 10px;
  padding: 11px 14px; border-radius: 10px;
  border: 1px solid var(--border-color, #e5e7eb);
  text-decoration: none; color: inherit;
  font-size: .85rem; font-weight: 500;
  transition: all .15s;
}
.dash-action-btn:hover {
  border-color: var(--accent, #f97316);
  color: var(--accent, #f97316);
  background: rgba(249,115,22,.04);
}
.dash-action-btn i { font-size: 1rem; opacity: .7; }

/* Dark mode */
html[data-theme="dark"] .dash-card,
html[data-theme="dark"] .dash-panel {
  background: #161b22;
  border-color: #30363d;
}
html[data-theme="dark"] .dash-alert-row:hover { background: rgba(255,255,255,.04); }
html[data-theme="dark"] .dash-action-btn { border-color: #30363d; color: #e2e8f0; }
html[data-theme="dark"] .dash-alert-row { border-color: #21262d; }
html[data-theme="dark"] .dash-panel-header { border-color: #30363d; }
html[data-theme="dark"] .dash-card--warn   { background: rgba(245,158,11,.06); }
html[data-theme="dark"] .dash-card--danger { background: rgba(239,68,68,.06); }
</style>
