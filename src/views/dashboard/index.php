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

  <!-- Filtros via AJAX -->
  <div class="d-flex align-items-center gap-2 flex-wrap">
    <select id="filtroAlm" class="form-select form-select-sm" style="min-width:180px" onchange="atualizarDashboard()">
      <option value="0">Todos os almoxarifados</option>
      <?php foreach ($almoxarifados as $a): ?>
      <option value="<?= $a['id'] ?>" <?= $almFiltro === (int)$a['id'] ? 'selected' : '' ?>>
        <?= h($a['nome']) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <div class="btn-group btn-group-sm" role="group">
      <button type="button" onclick="setPeriodo(7)"  id="btn7"  class="btn btn-outline-secondary">7 dias</button>
      <button type="button" onclick="setPeriodo(30)" id="btn30" class="btn btn-primary">30 dias</button>
      <button type="button" onclick="setPeriodo(60)" id="btn60" class="btn btn-outline-secondary">60 dias</button>
    </div>
  </div>
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
      <div class="pc-card-val" id="val-saidas"><?= number_format($saidaTotal) ?></div>
      <div class="pc-card-sub" id="val-media"><?= $mediaDia ?> por dia em média</div>
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
    <div class="pc-card <?= $abaixoMin > 0 ? 'pc-card--warn' : '' ?>" id="card-abaixo">
      <div class="pc-card-label">Abaixo do Mínimo</div>
      <div class="pc-card-val" id="val-abaixo" style="<?= $abaixoMin > 0 ? 'color:#ef4444' : '' ?>">
        <?= $abaixoMin ?>
      </div>
      <div class="pc-card-sub" id="val-abaixo-sub"><?= $abaixoMin > 0 ? 'precisam de reposição' : 'estoque ok' ?></div>
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
        <div style="min-width:160px" id="legendaInsumos">
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
        <div style="min-width:160px" id="legendaColabs">
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
  border: 1px solid var(--border, #e5e7eb);
  border-radius: 14px;
  padding: 20px 18px;
  position: relative;
  overflow: hidden;
  transition: box-shadow .2s;
  color: var(--text, #111);
}
.pc-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.07); }
.pc-card--warn { border-color: rgba(239,68,68,.3); background: rgba(239,68,68,.03); }
.pc-card-label { font-size:.75rem; color:var(--text-muted,#6b7280); font-weight:600; text-transform:uppercase; letter-spacing:.04em; margin-bottom:6px; }
.pc-card-val   { font-size:2.2rem; font-weight:900; line-height:1; margin-bottom:4px; color: var(--text, #111); }
.pc-card-sub   { font-size:.75rem; color:var(--text-muted,#6b7280); }
.pc-card-icon  { position:absolute; top:16px; right:16px; font-size:1.6rem; opacity:.15; }

.pc-panel {
  background: var(--card-bg, #fff);
  border: 1px solid var(--border, #e5e7eb);
  border-radius: 14px;
  overflow: hidden;
  color: var(--text, #111);
}
.pc-panel-header {
  padding: 16px 24px;
  border-bottom: 1px solid var(--border, #e5e7eb);
}
.pc-panel-body { padding: 16px 24px; }

/* Dark mode */
html[data-theme="dark"] .pc-card,
html[data-theme="dark"] .pc-panel {
  background: #161b22;
  border-color: #30363d;
  color: #e2e8f0;
}
html[data-theme="dark"] .pc-card-val   { color: #e2e8f0 !important; }
html[data-theme="dark"] .pc-card-label { color: #8b949e !important; }
html[data-theme="dark"] .pc-card-sub   { color: #8b949e !important; }
html[data-theme="dark"] .pc-panel-header { border-color: #30363d; color: #e2e8f0; }
html[data-theme="dark"] .pc-card--warn { background: rgba(239,68,68,.06); }
</style>

<!-- ── Chart.js local ─────────────────────────────────────────────────────── -->
<script src="/assets/js/chart.min.js"></script>
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
    animation: { duration: 1300, easing: 'easeInOutQuart' },
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

// ── AJAX: Atualização sem recarregar ──────────────────────────────────────
let periodoAtual = <?= $periodo ?>;
let almAtual     = <?= $almFiltro ?>;
let donutInsumos = null;
let donutColabs  = null;
const CORES = <?= json_encode($coresGrafico) ?>;

function setPeriodo(p) {
  periodoAtual = p;
  // Atualiza botões
  [7, 30, 60].forEach(v => {
    const btn = document.getElementById('btn' + v);
    if (btn) {
      btn.className = v === p
        ? 'btn btn-primary btn-sm'
        : 'btn btn-outline-secondary btn-sm';
    }
  });
  atualizarDashboard();
}

function atualizarDashboard() {
  almAtual = parseInt(document.getElementById('filtroAlm')?.value || 0);

  // Animação de saída — fade + slide up
  animarSaida();

  fetch(`/api/dashboard?periodo=${periodoAtual}&alm=${almAtual}`)
    .then(r => r.json())
    .then(d => {
      // Atualiza cards com contador animado
      animarContador('val-saidas', d.saida_total);
      animarContador('val-abaixo', d.abaixo_min);

      const mediaEl = document.getElementById('val-media');
      if (mediaEl) mediaEl.textContent = d.media_dia + ' por dia em média';

      const abaixoSub = document.getElementById('val-abaixo-sub');
      if (abaixoSub) abaixoSub.textContent = d.abaixo_min > 0 ? 'precisam de reposição' : 'estoque ok';

      const cardAbaixo = document.getElementById('card-abaixo');
      const valAbaixo  = document.getElementById('val-abaixo');
      if (cardAbaixo) cardAbaixo.classList.toggle('pc-card--warn', d.abaixo_min > 0);
      if (valAbaixo)  valAbaixo.style.color = d.abaixo_min > 0 ? '#ef4444' : '';

      // Atualiza gráfico de linha com animação
      const labelsFormatados = d.grafico_labels.map(dt => {
        const [y,m,dia] = dt.split('-');
        return `${dia}/${m}`;
      });
      graficoLinha.data.labels = labelsFormatados;
      graficoLinha.data.datasets[0].data = d.grafico_data;
      graficoLinha.options.animation = { duration: 800, easing: 'easeInOutQuart' };
      graficoLinha.update();

      // Atualiza donuts
      atualizarDonut('donutInsumos', d.top5_insumos.map(i => i.nome), d.top5_insumos.map(i => parseFloat(i.total)));
      atualizarDonut('donutColabs',  d.top5_colabs.map(c => c.colaborador), d.top5_colabs.map(c => parseFloat(c.total)));

      // Atualiza legendas dos donuts
      atualizarLegenda('legendaInsumos', d.top5_insumos.map(i => i.nome));
      atualizarLegenda('legendaColabs',  d.top5_colabs.map(c => c.colaborador));

      // Animação de entrada
      animarEntrada();
    })
    .catch(err => console.error('Erro ao atualizar dashboard:', err));
}

function atualizarDonut(canvasId, labels, data) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;

  // Destrói o donut existente se houver
  const chart = Chart.getChart(canvas);
  if (chart) chart.destroy();

  if (!labels.length) return;

  new Chart(canvas.getContext('2d'), {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data,
        backgroundColor: CORES,
        borderWidth: 0,
        hoverOffset: 6,
      }]
    },
    options: {
      responsive: true,
      cutout: '65%',
      animation: { duration: 950, easing: 'easeInOutBack' },
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } }
      }
    }
  });
}

function atualizarLegenda(id, nomes) {
  const el = document.getElementById(id);
  if (!el) return;
  el.innerHTML = nomes.map((n, i) =>
    `<div class="d-flex align-items-center gap-2 mb-2">
      <span style="width:10px;height:10px;border-radius:50%;background:${CORES[i]};flex-shrink:0"></span>
      <span style="font-size:.78rem;color:var(--text-muted)" title="${n}">${n.length > 22 ? n.slice(0,22)+'…' : n}</span>
    </div>`
  ).join('');
}

// Contador animado (conta de 0 até o valor)
function animarContador(id, destino) {
  const el = document.getElementById(id);
  if (!el) return;
  const inicio = parseInt(el.textContent.replace(/\D/g,'')) || 0;
  const duracao = 650;
  const start = performance.now();
  function step(agora) {
    const prog = Math.min((agora - start) / duracao, 1);
    const ease = 1 - Math.pow(1 - prog, 3);
    el.textContent = Math.round(inicio + (destino - inicio) * ease).toLocaleString('pt-BR');
    if (prog < 1) requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
}

// Fade + translateY para saída
function animarSaida() {
  const alvos = ['val-saidas','val-media','val-abaixo','graficoConsumo','donutInsumos','donutColabs'];
  alvos.forEach(id => {
    const el = document.getElementById(id);
    if (el) {
      el.style.transition = 'opacity .2s ease, transform .2s ease';
      el.style.opacity = '0.3';
      el.style.transform = 'translateY(-4px)';
    }
  });
}

// Fade + translateY para entrada
function animarEntrada() {
  const alvos = ['val-saidas','val-media','val-abaixo','graficoConsumo','donutInsumos','donutColabs'];
  alvos.forEach((id, i) => {
    const el = document.getElementById(id);
    if (el) {
      setTimeout(() => {
        el.style.transition = 'opacity .4s ease, transform .4s ease';
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
      }, i * 80);
    }
  });
}
</script>
