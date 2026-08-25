<?php /* views/relatorios/alertas.php */ ?>
<?php
$totalCritico = count(array_filter($alertas, fn($a) => $a['urgencia'] === 'critico'));
$totalAlerta  = count(array_filter($alertas, fn($a) => $a['urgencia'] === 'alerta'));

$statusLabels = [
    'pendente'        => ['label' => 'Pendente',         'badge' => 'bg-danger'],
    'verificando'     => ['label' => 'Verificando',      'badge' => 'bg-warning'],
    'pedido_efetuado' => ['label' => 'Pedido Efetuado',  'badge' => 'bg-primary'],
    'pedido_rota'     => ['label' => 'Em Rota',          'badge' => 'bg-info'],
    'recebido'        => ['label' => 'Recebido',         'badge' => 'bg-success'],
];

function fmt_consumo_diario(float $cd, string $unidade): string {
    $inteiras = ['un', 'und', 'uni', 'pct', 'pc', 'par', 'unid', 'unidade', 'uni.'];
    $u = strtolower(trim($unidade));
    if (in_array($u, $inteiras)) {
        if ($cd <= 0) return '0';
        if ($cd < 1)  return '< 1';
        return (string)(int)round($cd);
    }
    // metro, kg, litro — 1 casa decimal
    if ($cd <= 0) return '0';
    if ($cd < 0.1) return '< 0,1';
    return number_format($cd, 1, ',', '');
}
?>

<!-- Cabeçalho com stats -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0"><i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>Alertas / Pedidos de Compra</h5>
  <a href="/relatorios" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Relatórios</a>
</div>

<!-- Cards de summary -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center" style="border-left: 4px solid var(--danger) !important;">
      <div class="fw-bold fs-3 text-danger"><?= $totalCritico ?></div>
      <div class="text-muted small">Estoque Zerado</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center" style="border-left: 4px solid var(--warning) !important;">
      <div class="fw-bold fs-3 text-warning"><?= $totalAlerta ?></div>
      <div class="text-muted small">Abaixo do Mínimo</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center" style="border-left: 4px solid var(--accent) !important;">
      <div class="fw-bold fs-3" style="color:var(--accent)"><?= count($alertas) ?></div>
      <div class="text-muted small">Total de Alertas</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center" style="border-left: 4px solid var(--success) !important;">
      <div class="fw-bold fs-3 text-success">
        <?= count(array_filter($alertas, fn($a) => ($a['item']['status_compra'] ?? 'pendente') === 'recebido')) ?>
      </div>
      <div class="text-muted small">Recebidos</div>
    </div>
  </div>
</div>

<?php if (empty($alertas)): ?>
<div class="card p-5 text-center">
  <i class="bi bi-check-circle-fill fs-1 text-success mb-3"></i>
  <h5 class="fw-semibold">Nenhum alerta de estoque</h5>
  <p class="text-muted">Todos os itens estão acima do estoque mínimo.</p>
</div>
<?php else: ?>

<!-- Barra de busca -->
<div class="card p-3 mb-3">
  <div class="input-group input-group-sm" style="max-width:400px">
    <span class="input-group-text"><i class="bi bi-search"></i></span>
    <input type="text" id="filtroAlerta" class="form-control" placeholder="Filtrar por item ou almoxarifado…">
  </div>
</div>

<!-- Tabela -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold"><i class="bi bi-table me-1"></i><?= count($alertas) ?> item(s) em alerta</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover table-sm mb-0" id="tabelaAlertas">
      <thead>
        <tr>
          <th>Item</th>
          <th>Almoxarifado</th>
          <th class="text-center">Qtd Atual</th>
          <th class="text-center">Mínimo</th>
          <th class="text-center">Déficit</th>
          <th class="text-center">Previsão (dias)</th>
          <th class="text-center">Status Compra</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($alertas as $row):
        $it  = $row['item'];
        $sc  = $it['status_compra'] ?? 'pendente';
        $info = $statusLabels[$sc] ?? $statusLabels['pendente'];
        $rowClass = (float)$it['quantidade'] <= 0 ? 'table-danger' : 'table-warning';
      ?>
      <tr class="<?= $rowClass ?>" data-filtro="<?= strtolower(h($it['nome']).' '.$row['alm_nome']) ?>">
        <td>
          <a href="/item/<?= $it['id'] ?>" class="fw-semibold text-decoration-none text-dark">
            <?= h($it['nome']) ?>
          </a>
          <?php if ($it['codigo']): ?>
          <div class="font-monospace text-muted" style="font-size:0.75rem"><?= h($it['codigo']) ?></div>
          <?php endif; ?>
        </td>
        <td class="text-muted small"><?= h($row['alm_nome']) ?></td>
        <td class="text-center fw-bold <?= (float)$it['quantidade'] <= 0 ? 'text-danger' : 'text-warning' ?>">
          <?= fmt_qtd((float)$it['quantidade']) ?> <span class="text-muted fw-normal" style="font-size:0.75rem"><?= h($it['unidade']) ?></span>
        </td>
        <td class="text-center text-muted small"><?= fmt_qtd((float)$it['estoque_minimo']) ?></td>
        <td class="text-center">
          <?php if ($row['deficit'] > 0): ?>
          <span class="badge" style="background:var(--danger-light);color:var(--danger);border-radius:20px">
            +<?= fmt_qtd($row['deficit']) ?>
          </span>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td class="text-center">
          <?php if ($row['dias_ate_zero'] > 0): ?>
            <span class="badge rounded-pill <?= $row['dias_ate_zero'] <= 3 ? 'bg-danger' : ($row['dias_ate_zero'] <= 7 ? 'bg-warning' : 'bg-secondary') ?>"
                  title="Consumo: <?= fmt_consumo_diario((float)$row['consumo_diario'], $it['unidade'] ?? '') ?>/dia">
              <?= $row['dias_ate_zero'] ?>d
            </span>
          <?php elseif ((float)$it['quantidade'] <= 0): ?>
            <span class="badge bg-danger rounded-pill">Zerado</span>
          <?php else: ?>
            <span class="text-muted small">—</span>
          <?php endif; ?>
        </td>
        <td class="text-center">
          <span class="badge <?= $info['badge'] ?> rounded-pill status-badge-<?= $it['id'] ?>"
                style="border-radius:20px;min-width:110px">
            <?= $info['label'] ?>
          </span>
        </td>
        <td class="text-end pe-3">
          <select class="form-select form-select-sm status-select"
                  style="width:140px;display:inline-block;border-radius:8px"
                  data-item-id="<?= $it['id'] ?>"
                  onchange="atualizarStatus(this)">
            <?php foreach ($statusLabels as $val => $sl): ?>
            <option value="<?= $val ?>" <?= $sc === $val ? 'selected' : '' ?>><?= $sl['label'] ?></option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

<script>
// Filtro em tempo real
document.getElementById('filtroAlerta')?.addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('#tabelaAlertas tbody tr').forEach(tr => {
    tr.style.display = tr.dataset.filtro?.includes(q) ? '' : 'none';
  });
});

// Atualizar status via fetch sem recarregar
function atualizarStatus(sel) {
  const itemId = sel.dataset.itemId;
  const novoStatus = sel.value;
  const badges = {
    pendente:        {label:'Pendente',        cls:'bg-danger'},
    verificando:     {label:'Verificando',     cls:'bg-warning'},
    pedido_efetuado: {label:'Pedido Efetuado', cls:'bg-primary'},
    pedido_rota:     {label:'Em Rota',         cls:'bg-info'},
    recebido:        {label:'Recebido',        cls:'bg-success'},
  };
  fetch('/relatorios/alertas', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? ''
    },
    body: new URLSearchParams({
      item_id:      itemId,
      status_compra: novoStatus,
      csrf_token:   '<?= csrf_token() ?>'
    })
  })
  .then(r => {
    if (r.ok) {
      const badge = document.querySelector('.status-badge-' + itemId);
      if (badge) {
        const b = badges[novoStatus];
        badge.className = 'badge ' + b.cls + ' rounded-pill status-badge-' + itemId;
        badge.style.borderRadius = '20px';
        badge.style.minWidth = '110px';
        badge.textContent = b.label;
      }
      // Flash visual
      sel.style.outline = '2px solid var(--success)';
      setTimeout(() => sel.style.outline = '', 1200);
    }
  })
  .catch(() => {});
}
</script>
