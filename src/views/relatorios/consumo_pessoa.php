<?php /* views/relatorios/consumo_pessoa.php */ ?>
<?php
$totalPessoas = count($porPessoa);
$totalSaidas  = array_sum(array_column($porPessoa, 'total'));
$totalMovs    = array_sum(array_map(fn($p) => count($p['movs']), $porPessoa));
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0"><i class="bi bi-person-lines-fill me-2"></i>Consumo por Pessoa</h5>
  <div class="d-flex gap-2">
    <?php
    $qs = http_build_query([
        'almoxarifado_id' => $almId,
        'data_ini'        => $dataIni,
        'data_fim'        => $dataFim,
        'responsavel'     => $filtroResp,
        'exportar'        => 1,
    ]);
    ?>
    <a href="/relatorios/consumo-por-pessoa?<?= $qs ?>" class="btn btn-sm btn-outline-success">
      <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar CSV
    </a>
    <a href="/relatorios" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Relatórios
    </a>
  </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center" style="border-left:4px solid var(--accent) !important">
      <div class="fw-bold fs-3" style="color:var(--accent)"><?= $totalPessoas ?></div>
      <div class="text-muted small">Pessoas</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center" style="border-left:4px solid var(--info) !important">
      <div class="fw-bold fs-3 text-info"><?= $totalMovs ?></div>
      <div class="text-muted small">Movimentações</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center" style="border-left:4px solid var(--success) !important">
      <div class="fw-bold fs-3 text-success"><?= fmt_qtd($totalSaidas) ?></div>
      <div class="text-muted small">Total Retirado</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center" style="border-left:4px solid var(--warning) !important">
      <div class="fw-bold fs-3 text-warning"><?= fmt_data($dataIni, 'd/m') ?> – <?= fmt_data($dataFim, 'd/m') ?></div>
      <div class="text-muted small">Período</div>
    </div>
  </div>
</div>

<!-- Filtros -->
<form method="GET" class="card p-3 mb-4">
  <div class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label small">Almoxarifado</label>
      <select name="almoxarifado_id" class="form-select form-select-sm">
        <option value="">Todos</option>
        <?php foreach ($almoxarifados as $a): ?>
        <option value="<?= $a['id'] ?>" <?= $almId == $a['id'] ? 'selected' : '' ?>><?= h($a['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small">Data Início</label>
      <input type="date" name="data_ini" class="form-control form-control-sm" value="<?= h($dataIni) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small">Data Fim</label>
      <input type="date" name="data_fim" class="form-control form-control-sm" value="<?= h($dataFim) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small">Pessoa / Responsável</label>
      <input type="text" name="responsavel" class="form-control form-control-sm" placeholder="Nome…" value="<?= h($filtroResp) ?>">
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary btn-sm w-100 mt-3">
        <i class="bi bi-funnel me-1"></i>Filtrar
      </button>
    </div>
  </div>
</form>

<?php if (empty($porPessoa)): ?>
<div class="card p-5 text-center">
  <i class="bi bi-inbox fs-1 text-muted mb-3"></i>
  <h5 class="fw-semibold text-muted">Nenhuma saída no período selecionado.</h5>
</div>
<?php else: ?>

<div class="accordion" id="accordionPessoas">
<?php $idx = 0; foreach ($porPessoa as $pessoa => $dados): $idx++; ?>
<div class="card mb-2" style="border-radius:12px;overflow:hidden">
  <div class="card-header d-flex justify-content-between align-items-center"
       style="cursor:pointer;user-select:none"
       onclick="togglePessoa(<?= $idx ?>)">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
           style="width:36px;height:36px;background:var(--gradient-primary);flex-shrink:0;font-size:0.85rem">
        <?= strtoupper(mb_substr($pessoa, 0, 1)) ?>
      </div>
      <div>
        <div class="fw-semibold"><?= h($pessoa) ?></div>
        <div class="text-muted small"><?= count($dados['movs']) ?> movimentação(ões)</div>
      </div>
    </div>
    <div class="d-flex align-items-center gap-3">
      <span class="badge rounded-pill" style="background:var(--accent-light);color:var(--accent);border-radius:20px;padding:6px 14px;font-size:0.85rem">
        <?= fmt_qtd($dados['total']) ?> itens
      </span>
      <i class="bi bi-chevron-down" id="ico-pessoa-<?= $idx ?>"></i>
    </div>
  </div>
  <div id="sub-pessoa-<?= $idx ?>" style="display:none">
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead>
          <tr>
            <th>Data</th>
            <th>Item</th>
            <th>Almoxarifado</th>
            <th class="text-center">Qtd</th>
            <th>Observação</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($dados['movs'] as $mov): ?>
        <tr>
          <td class="small text-muted"><?= fmt_data($mov['data'], 'd/m/Y H:i') ?></td>
          <td>
            <span class="fw-semibold"><?= h($mov['item_nome']) ?></span>
            <?php if (!empty($mov['codigo'])): ?>
            <div class="font-monospace text-muted" style="font-size:0.73rem"><?= h($mov['codigo']) ?></div>
            <?php endif; ?>
          </td>
          <td class="text-muted small"><?= h($mov['alm_nome']) ?></td>
          <td class="text-center fw-semibold"><?= fmt_qtd((float)$mov['quantidade']) ?> <span class="text-muted fw-normal" style="font-size:0.75rem"><?= h($mov['unidade']) ?></span></td>
          <td class="small text-muted"><?= h($mov['observacao'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>

<script>
function togglePessoa(idx) {
  const sub = document.getElementById('sub-pessoa-' + idx);
  const ico = document.getElementById('ico-pessoa-' + idx);
  if (!sub) return;
  const open = sub.style.display !== 'none';
  sub.style.display = open ? 'none' : '';
  if (ico) ico.className = open ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
}
</script>
