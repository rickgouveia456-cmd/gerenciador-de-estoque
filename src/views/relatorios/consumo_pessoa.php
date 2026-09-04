<?php /* views/relatorios/consumo_pessoa.php */
$totalPessoas = count($porPessoa);
$totalSaidas  = array_sum(array_column($porPessoa, 'total'));
$totalMovs    = array_sum(array_map(fn($p) => count($p['movs']), $porPessoa));
$isAdmin      = ($u['perfil'] === 'admin');

// Calcular participação de cada pessoa
$maxTotal     = $totalSaidas > 0 ? $totalSaidas : 1;
?>

<!-- Header -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div>
    <h5 class="fw-bold mb-1"><i class="bi bi-person-lines-fill me-2"></i>Consumo por Pessoa</h5>
    <div class="text-muted small">Relatório de saídas agrupadas por colaborador</div>
  </div>
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

<!-- Stats inline -->
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
      <div class="fw-bold fs-3 text-warning" style="font-size:1rem !important">
        <?= fmt_data($dataIni, 'd/m') ?> – <?= fmt_data($dataFim, 'd/m') ?>
      </div>
      <div class="text-muted small">Período</div>
    </div>
  </div>
</div>

<!-- Filtros em linha -->
<form method="GET" class="mb-4">
  <div class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label small fw-semibold mb-1">Almoxarifado</label>
      <select name="almoxarifado_id" class="form-select form-select-sm">
        <option value="">Todos</option>
        <?php foreach ($almoxarifados as $a): ?>
        <option value="<?= $a['id'] ?>" <?= $almId == $a['id'] ? 'selected' : '' ?>>
          <?= h($a['nome']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small fw-semibold mb-1">Data Início</label>
      <input type="date" name="data_ini" class="form-control form-control-sm" value="<?= h($dataIni) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small fw-semibold mb-1">Data Fim</label>
      <input type="date" name="data_fim" class="form-control form-control-sm" value="<?= h($dataFim) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold mb-1">Pessoa / Responsável</label>
      <div class="input-group input-group-sm">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" name="responsavel" class="form-control" placeholder="Nome..."
               value="<?= h($filtroResp) ?>">
      </div>
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary btn-sm w-100">
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

<?php if ($isAdmin): ?>
<form method="POST" action="/movimentacoes/excluir" id="formExcluir">
  <?= csrf_field() ?>
<?php endif; ?>

<!-- Tabela principal -->
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <?php if ($isAdmin): ?><th style="width:40px"></th><?php endif; ?>
          <th style="width:40px">#</th>
          <th>Pessoa</th>
          <th class="text-center">
            <span class="badge rounded-pill" style="background:var(--accent-light);color:var(--accent)">
              Retiradas
            </span>
          </th>
          <th class="text-center">Itens Distintos</th>
          <th style="min-width:140px">Participação</th>
          <th class="text-center" style="width:80px">Detalhe</th>
        </tr>
      </thead>
      <tbody>
      <?php $idx = 0; foreach ($porPessoa as $pessoa => $dados):
        $idx++;
        $n        = count($dados['movs']);
        $itensSet = array_unique(array_column($dados['movs'], 'item_nome'));
        $nDistinct = count($itensSet);
        $pct      = round($dados['total'] / $maxTotal * 100);
        $inicialP = strtoupper(mb_substr(trim($pessoa), 0, 1));
      ?>
      <tr class="pessoa-row" id="row-p-<?= $idx ?>" style="cursor:pointer"
          onclick="toggleDetalhe(<?= $idx ?>)">
        <?php if ($isAdmin): ?>
        <td onclick="event.stopPropagation()">
          <input type="checkbox"
                 class="form-check-input check-pessoa"
                 id="checkPessoa<?= $idx ?>"
                 data-idx="<?= $idx ?>"
                 onchange="togglePessoaCheck(<?= $idx ?>, this.checked)">
        </td>
        <?php endif; ?>
        <td class="text-muted small"><?= $idx ?></td>
        <td>
          <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                 style="width:36px;height:36px;background:var(--gradient-primary);font-size:0.85rem">
              <?= h($inicialP) ?>
            </div>
            <div>
              <div class="fw-semibold"><?= h($pessoa) ?></div>
              <div class="text-muted small"><?= $n ?> movimentação(ões)</div>
            </div>
          </div>
        </td>
        <td class="text-center">
          <span class="badge rounded-pill" style="background:var(--accent-light);color:var(--accent);font-size:0.85rem;padding:5px 12px">
            <?= fmt_qtd($dados['total']) ?>
          </span>
        </td>
        <td class="text-center">
          <span class="badge rounded-pill bg-info" style="font-size:0.82rem;padding:4px 10px">
            <?= $nDistinct ?>
          </span>
        </td>
        <td>
          <div class="d-flex align-items-center gap-2">
            <div class="progress flex-grow-1" style="height:8px;border-radius:6px">
              <div class="progress-bar"
                   style="width:<?= $pct ?>%;background:var(--gradient-primary)"></div>
            </div>
            <small class="text-muted" style="width:36px;text-align:right"><?= $pct ?>%</small>
          </div>
        </td>
        <td class="text-center" onclick="event.stopPropagation()">
          <button type="button" class="btn btn-sm btn-outline-primary"
                  onclick="toggleDetalhe(<?= $idx ?>)" title="Ver itens">
            <i class="bi bi-chevron-down" id="ico-det-<?= $idx ?>"></i>
          </button>
        </td>
      </tr>
      <!-- Linha de detalhe (expandível) -->
      <tr id="det-<?= $idx ?>" style="display:none">
        <td colspan="<?= $isAdmin ? 7 : 6 ?>" class="p-0">
          <div style="background:var(--bg);border-top:2px solid var(--accent)">
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead style="background:var(--primary-light)">
                  <tr>
                    <?php if ($isAdmin): ?><th style="width:40px"></th><?php endif; ?>
                    <th class="ps-4">Data</th>
                    <th>Item</th>
                    <th>Almoxarifado</th>
                    <th class="text-center">Qtd</th>
                    <th>Observação</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($dados['movs'] as $mov): ?>
                <tr>
                  <?php if ($isAdmin): ?>
                  <td class="text-center">
                    <input type="checkbox"
                           class="form-check-input check-mov check-mov-<?= $idx ?>"
                           name="mov_ids[]"
                           value="<?= (int)$mov['id'] ?>"
                           onchange="atualizarBotaoExcluir()"
                           onclick="event.stopPropagation()">
                  </td>
                  <?php endif; ?>
                  <td class="small text-muted ps-4"><?= fmt_data($mov['data'], 'd/m/Y H:i') ?></td>
                  <td>
                    <span class="fw-semibold small"><?= h($mov['item_nome']) ?></span>
                    <?php if (!empty($mov['codigo'])): ?>
                    <div class="font-monospace text-muted" style="font-size:0.72rem"><?= h($mov['codigo']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="text-muted small"><?= h($mov['alm_nome']) ?></td>
                  <td class="text-center fw-semibold small">
                    <?= fmt_qtd((float)$mov['quantidade']) ?>
                    <span class="text-muted fw-normal"><?= h($mov['unidade']) ?></span>
                  </td>
                  <td class="small text-muted"><?= h($mov['observacao'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($isAdmin): ?>
</form>

<!-- Barra flutuante de exclusão -->
<div id="barraExcluir"
     style="display:none;position:fixed;bottom:28px;left:50%;transform:translateX(-50%);
            z-index:1050;background:#fff;border:1px solid var(--border);border-radius:16px;
            box-shadow:0 8px 32px rgba(0,0,0,0.18);padding:14px 24px;
            align-items:center;gap:16px;min-width:320px">
  <div>
    <span class="fw-semibold text-danger" id="textoSelecionadas">0 selecionada(s)</span>
    <div class="text-muted small">O estoque será revertido automaticamente</div>
  </div>
  <button type="button" class="btn btn-danger btn-sm ms-auto"
          onclick="confirmarExclusao()">
    <i class="bi bi-trash3 me-1"></i>Excluir Selecionadas
  </button>
  <button type="button" class="btn btn-outline-secondary btn-sm"
          onclick="limparSelecao()">
    <i class="bi bi-x"></i>
  </button>
</div>
<?php endif; ?>

<?php endif; ?>

<script>
function toggleDetalhe(idx) {
  const det = document.getElementById('det-' + idx);
  const ico = document.getElementById('ico-det-' + idx);
  if (!det) return;
  const isOpen = det.style.display !== 'none';
  det.style.display = isOpen ? 'none' : '';
  if (ico) ico.className = isOpen ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
}

<?php if ($isAdmin): ?>
function togglePessoaCheck(idx, checked) {
  document.querySelectorAll('.check-mov-' + idx).forEach(cb => cb.checked = checked);
  atualizarBotaoExcluir();
}

function atualizarBotaoExcluir() {
  const n     = document.querySelectorAll('input[name="mov_ids[]"]:checked').length;
  const barra = document.getElementById('barraExcluir');
  const texto = document.getElementById('textoSelecionadas');
  if (n > 0) {
    barra.style.display = 'flex';
    texto.textContent   = n + ' movimentação(ões) selecionada(s)';
  } else {
    barra.style.display = 'none';
  }
}

function confirmarExclusao() {
  const n = document.querySelectorAll('input[name="mov_ids[]"]:checked').length;
  if (!confirm(`Excluir ${n} movimentação(ões)?\n\nO estoque de cada item será revertido automaticamente.\n\nEssa ação não pode ser desfeita.`)) return;
  document.getElementById('formExcluir').submit();
}

function limparSelecao() {
  document.querySelectorAll('input[name="mov_ids[]"]:checked, .check-pessoa:checked')
    .forEach(cb => cb.checked = false);
  atualizarBotaoExcluir();
}
<?php endif; ?>
</script>
