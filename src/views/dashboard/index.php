<?php
// ── Dados para o Painel de Consumo ────────────────────────────────────────────
$periodo   = (int)($_GET['periodo'] ?? 30);
$almFiltro = (int)($_GET['alm'] ?? 0);
if (!in_array($periodo, [7, 30, 60])) $periodo = 30;

$almIdsConsulta = $almIds;
if ($almFiltro && in_array($almFiltro, $almIds)) {
    $almIdsConsulta = [$almFiltro];
}
$almIdsConsultaStr = $almIdsConsulta ? implode(',', array_map('intval', $almIdsConsulta)) : '0';
$corte = (new DateTime())->modify("-$periodo days")->format('Y-m-d H:i:s');

// Saídas totais no período
$saidaTotal = (int)db()->query(
    "SELECT COALESCE(SUM(m.quantidade),0)
     FROM movimentacao m JOIN item i ON i.id=m.item_id
     WHERE m.tipo='saida' AND m.data>='{$corte}'
       AND i.almoxarifado_id IN ($almIdsConsultaStr)"
)->fetchColumn();

// Consumo diário por data (para o gráfico de linha)
$stmt = db()->prepare(
    "SELECT DATE(m.data) as dia, COALESCE(SUM(m.quantidade),0) as total
     FROM movimentacao m JOIN item i ON i.id=m.item_id
     WHERE m.tipo='saida' AND m.data>=?
       AND i.almoxarifado_id IN ($almIdsConsultaStr)
     GROUP BY DATE(m.data) ORDER BY dia ASC"
);
$stmt->execute([$corte]);
$consumoDiario = $stmt->fetchAll();

// Preenche dias sem movimentação com 0
$diasMap = [];
for ($i = $periodo - 1; $i >= 0; $i--) {
    $d = (new DateTime())->modify("-$i days")->format('Y-m-d');
    $diasMap[$d] = 0;
}
foreach ($consumoDiario as $r) {
    $diasMap[$r['dia']] = round((float)$r['total'], 1);
}
$graficoLabels = json_encode(array_keys($diasMap));
$graficoData   = json_encode(array_values($diasMap));
$mediaDia      = count($diasMap) > 0 ? round($saidaTotal / $periodo, 1) : 0;

// Top 5 insumos mais saídos
$stmt2 = db()->prepare(
    "SELECT i.nome, COALESCE(SUM(m.quantidade),0) as total
     FROM movimentacao m JOIN item i ON i.id=m.item_id
     WHERE m.tipo='saida' AND m.data>=?
       AND i.almoxarifado_id IN ($almIdsConsultaStr)
     GROUP BY i.id ORDER BY total DESC LIMIT 5"
);
$stmt2->execute([$corte]);
$top5Insumos = $stmt2->fetchAll();

// Top 5 colaboradores
$stmt3 = db()->prepare(
    "SELECT
       REGEXP_REPLACE(
         REGEXP_REPLACE(m.responsavel, '\\\\s*\\\\|.*', ''),
         '(?i)liberado\\s*[Pp]/\\s*', ''
       ) as colaborador,
       COALESCE(SUM(m.quantidade),0) as total
     FROM movimentacao m JOIN item i ON i.id=m.item_id
     WHERE m.tipo='saida' AND m.data>=?
       AND i.almoxarifado_id IN ($almIdsConsultaStr)
       AND m.responsavel IS NOT NULL AND m.responsavel != ''
     GROUP BY colaborador ORDER BY total DESC LIMIT 5"
);
$stmt3->execute([$corte]);
$top5Colabs = $stmt3->fetchAll();

// Itens abaixo do mínimo
$abaixoMin = (int)db()->query(
    "SELECT COUNT(*) FROM item
     WHERE ativo=1 AND quantidade <= estoque_minimo
       AND almoxarifado_id IN ($almIdsConsultaStr)"
)->fetchColumn();

// Cores para os gráficos
$coresGrafico = ['#f97316','#0ea5e9','#10b981','#8b5cf6','#f59e0b'];
?>

<!-- ── Cabeçalho do painel ────────────────────────────────────────────────── -->
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
  <div>
    <h1 class="fw-800 mb-1" style="font-size:1.6rem">Painel de Consumo</h1>
    <p class="text-muted mb-0" style="font-size:.85rem">Gestão de almoxarifado de obra</p>
  </div>

  <!-- Filtros -->
  <form method="GET" action="/" class="d-flex align-items-center gap-2 flex-wrap">
    <select name="alm" class="form-select form-select-sm" style="min-width:180px" onchange="this.form.submit()">
      <option value="0" <?= $almFiltro === 0 ? 'selected' : '' ?>>Todos os almoxarifados</option>
      <?php foreach ($almoxarifados as $a): ?>
      <option value="<?= $a['id'] ?>" <?= $almFiltro === (int)$a['id'] ? 'selected' : '' ?>>
        <?= h($a['nome']) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <div class="btn-group btn-group-sm" role="group">
      <?php foreach ([7 => '7 dias', 30 => '30 dias', 60 => '60 dias'] as $v => $l): ?>
      <button type="submit" name="periodo" value="<?= $v ?>"
              class="btn <?= $periodo === $v ? 'btn-primary' : 'btn-outline-secondary' ?>">
        <?= $l ?>
      </button>
      <?php endforeach; ?>
    </div>
  </form>
</div>

<!-- ── Cards de métricas ───────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="pc-card">
      <div class="pc-card-label">Almoxarifados</div>
      <div class="pc-card-val"><?= count($almoxarifados) ?></div>
      <div class="pc-card-icon text-info"><i class="bi bi-buildings"></i></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="pc-card">
      <div class="pc-card-label">Saídas no período</div>
      <div class="pc-card-val"><?= number_format($saidaTotal) ?></div>
      <div class="pc-card-sub"><?= $mediaDia ?> por dia em média</div>
      <div class="pc-card-icon text-warning"><i class="bi bi-box-arrow-up-right"></i></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="pc-card">
      <div class="pc-card-label">Itens Cadastrados</div>
      <div class="pc-card-val"><?= number_format($stats['total_itens']) ?></div>
      <div class="pc-card-icon text-success"><i class="bi bi-box-seam"></i></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="pc-card <?= $abaixoMin > 0 ? 'pc-card--warn' : '' ?>">
      <div class="pc-card-label">Abaixo do Mínimo</div>
      <div class="pc-card-val" style="<?= $abaixoMin > 0 ? 'color:#ef4444' : '' ?>">
        <?= $abaixoMin ?>
      </div>
      <div class="pc-card-sub"><?= $abaixoMin > 0 ? 'precisam de reposição' : 'estoque ok' ?></div>
      <div class="pc-card-icon text-danger"><i class="bi bi-exclamation-triangle"></i></div>
    </div>
  </div>
</div>

<!-- ── Gráfico de consumo diário ──────────────────────────────────────────── -->
<div class="pc-panel mb-4">
  <div class="pc-panel-header">
    <div>
      <div class="fw-700">Consumo diário</div>
      <div class="text-muted" style="font-size:.78rem">Quantidade de material retirado por dia</div>
    </div>
  </div>
  <div class="pc-panel-body" style="padding:20px 24px">
    <canvas id="graficoConsumo" height="80"></canvas>
  </div>
</div>

<!-- ── Donuts ─────────────────────────────────────────────────────────────── -->
<div class="row g-4">

  <!-- Top 5 insumos -->
  <div class="col-12 col-md-6">
    <div class="pc-panel h-100">
      <div class="pc-panel-header">
        <div>
          <div class="fw-700">🏆 Top 5 insumos que mais saem</div>
          <div class="text-muted" style="font-size:.78rem">Maior volume retirado no período</div>
        </div>
      </div>
      <div class="pc-panel-body d-flex align-items-center justify-content-center gap-4 flex-wrap p-4">
        <?php if (!empty($top5Insumos)): ?>
        <div style="width:180px;height:180px;flex-shrink:0">
          <canvas id="donutInsumos"></canvas>
        </div>
        <div style="min-width:160px">
          <?php foreach ($top5Insumos as $i => $ins): ?>
          <div class="d-flex align-items-center gap-2 mb-2">
            <span style="width:10px;height:10px;border-radius:50%;background:<?= $coresGrafico[$i] ?>;flex-shrink:0"></span>
            <span style="font-size:.78rem;color:var(--text-muted)" title="<?= h($ins['nome']) ?>">
              <?= h(mb_strimwidth($ins['nome'], 0, 22, '…')) ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center text-muted py-4 w-100">
          <i class="bi bi-bar-chart d-block fs-2 mb-2"></i>Sem dados no período
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Top 5 colaboradores -->
  <div class="col-12 col-md-6">
    <div class="pc-panel h-100">
      <div class="pc-panel-header">
        <div>
          <div class="fw-700">👷 Top 5 colaboradores</div>
          <div class="text-muted" style="font-size:.78rem">Quem mais retira material</div>
        </div>
      </div>
      <div class="pc-panel-body d-flex align-items-center justify-content-center gap-4 flex-wrap p-4">
        <?php if (!empty($top5Colabs)): ?>
        <div style="width:180px;height:180px;flex-shrink:0">
          <canvas id="donutColabs"></canvas>
        </div>
        <div style="min-width:160px">
          <?php foreach ($top5Colabs as $i => $col): ?>
          <div class="d-flex align-items-center gap-2 mb-2">
            <span style="width:10px;height:10px;border-radius:50%;background:<?= $coresGrafico[$i] ?>;flex-shrink:0"></span>
            <span style="font-size:.78rem;color:var(--text-muted)" title="<?= h($col['colaborador']) ?>">
              <?= h(mb_strimwidth($col['colaborador'] ?? 'Sem nome', 0, 22, '…')) ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center text-muted py-4 w-100">
          <i class="bi bi-people d-block fs-2 mb-2"></i>Sem dados no período
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<!-- ── Estilos ────────────────────────────────────────────────────────────── -->
<style>
.pc-card {
  background: var(--card-bg, #fff);
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 14px;
  padding: 20px 18px;
  position: relative;
  overflow: hidden;
  transition: box-shadow .2s;
}
.pc-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.07); }
.pc-card--warn { border-color: rgba(239,68,68,.3); background: rgba(239,68,68,.03); }
.pc-card-label { font-size:.75rem; color:var(--text-muted,#6b7280); font-weight:600; text-transform:uppercase; letter-spacing:.04em; margin-bottom:6px; }
.pc-card-val   { font-size:2.2rem; font-weight:900; line-height:1; margin-bottom:4px; }
.pc-card-sub   { font-size:.75rem; color:var(--text-muted,#6b7280); }
.pc-card-icon  { position:absolute; top:16px; right:16px; font-size:1.6rem; opacity:.15; }

.pc-panel {
  background: var(--card-bg, #fff);
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 14px;
  overflow: hidden;
}
.pc-panel-header {
  padding: 16px 24px;
  border-bottom: 1px solid var(--border-color, #e5e7eb);
}
.pc-panel-body { padding: 16px 24px; }

/* Dark mode */
html[data-theme="dark"] .pc-card,
html[data-theme="dark"] .pc-panel {
  background: #161b22;
  border-color: #30363d;
}
html[data-theme="dark"] .pc-panel-header { border-color: #30363d; }
html[data-theme="dark"] .pc-card--warn { background: rgba(239,68,68,.06); }
</style>

<!-- ── Chart.js ───────────────────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark';
const gridColor  = () => isDark() ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.06)';
const labelColor = () => isDark() ? '#8b949e' : '#6b7280';

// ── Gráfico de linha ──────────────────────────────────────────────────────
const labels = <?= $graficoLabels ?>;
const data   = <?= $graficoData ?>;

// Formata datas para dd/mm
const labelsFormatados = labels.map(d => {
  const [y,m,dia] = d.split('-');
  return `${dia}/${m}`;
});

const ctxLinha = document.getElementById('graficoConsumo').getContext('2d');
const gradiente = ctxLinha.createLinearGradient(0, 0, 0, 200);
gradiente.addColorStop(0, 'rgba(249,115,22,.25)');
gradiente.addColorStop(1, 'rgba(249,115,22,0)');

const graficoLinha = new Chart(ctxLinha, {
  type: 'line',
  data: {
    labels: labelsFormatados,
    datasets: [{
      label: 'Saídas',
      data: data,
      borderColor: '#f97316',
      backgroundColor: gradiente,
      borderWidth: 2,
      pointRadius: 0,
      pointHoverRadius: 4,
      fill: true,
      tension: 0.4,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
    scales: {
      x: { grid: { color: gridColor() }, ticks: { color: labelColor(), maxTicksLimit: 10 } },
      y: { grid: { color: gridColor() }, ticks: { color: labelColor() }, beginAtZero: true },
    }
  }
});

// ── Donut insumos ─────────────────────────────────────────────────────────
<?php if (!empty($top5Insumos)): ?>
const ctxIns = document.getElementById('donutInsumos').getContext('2d');
new Chart(ctxIns, {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_column($top5Insumos, 'nome')) ?>,
    datasets: [{
      data: <?= json_encode(array_map(fn($r) => round((float)$r['total'],1), $top5Insumos)) ?>,
      backgroundColor: <?= json_encode($coresGrafico) ?>,
      borderWidth: 0,
      hoverOffset: 6,
    }]
  },
  options: {
    responsive: true,
    cutout: '65%',
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } }
    }
  }
});
<?php endif; ?>

// ── Donut colaboradores ───────────────────────────────────────────────────
<?php if (!empty($top5Colabs)): ?>
const ctxCol = document.getElementById('donutColabs').getContext('2d');
new Chart(ctxCol, {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_column($top5Colabs, 'colaborador')) ?>,
    datasets: [{
      data: <?= json_encode(array_map(fn($r) => round((float)$r['total'],1), $top5Colabs)) ?>,
      backgroundColor: <?= json_encode($coresGrafico) ?>,
      borderWidth: 0,
      hoverOffset: 6,
    }]
  },
  options: {
    responsive: true,
    cutout: '65%',
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } }
    }
  }
});
<?php endif; ?>

// Atualiza cores dos gráficos ao trocar dark mode
document.getElementById('darkModeToggle')?.addEventListener('click', () => {
  setTimeout(() => {
    graficoLinha.options.scales.x.grid.color = gridColor();
    graficoLinha.options.scales.y.grid.color = gridColor();
    graficoLinha.options.scales.x.ticks.color = labelColor();
    graficoLinha.options.scales.y.ticks.color = labelColor();
    graficoLinha.update();
  }, 100);
});
</script>
