<?php /* views/almoxarifado/show.php */

// Contar itens por categoria
$catContadores = [];
foreach ($itens as $it) {
    if (!$it['ativo']) continue;
    $cat = $it['categoria'] ?? 'geral';
    $catContadores[$cat] = ($catContadores[$cat] ?? 0) + 1;
}
$totalAtivos = array_sum($catContadores);

$catConfig = [
    'geral'      => ['emoji' => '📦', 'label' => 'Geral'],
    'epi'        => ['emoji' => '🪖', 'label' => 'EPI'],
    'eletrica'   => ['emoji' => '⚡', 'label' => 'Elétrica'],
    'hidraulica' => ['emoji' => '💧', 'label' => 'Hidráulica'],
    'gas'        => ['emoji' => '🔥', 'label' => 'Gás'],
    'maquinario' => ['emoji' => '⚙️', 'label' => 'Maquinário'],
];

$isAdminAlm  = $u['perfil'] === 'admin';
$isAlmox     = in_array($u['perfil'], ['admin', 'almoxarife']);
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
  <div>
    <h5 class="fw-bold mb-1"><?= h($alm['nome']) ?></h5>
    <div class="text-muted small">
      <?php if ($alm['obra']): ?><i class="bi bi-geo-alt me-1"></i><?= h($alm['obra']) ?><?php endif; ?>
      <?php if ($alm['cidade']): ?> · <i class="bi bi-building me-1"></i><?= h($alm['cidade']) ?><?php endif; ?>
    </div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <?php if ($isAlmox): ?>
    <a href="/item/novo?alm=<?= $id ?>" class="btn btn-primary btn-sm">
      <i class="bi bi-plus me-1"></i>Novo Item
    </a>
    <a href="/almoxarifado/<?= $id ?>/importar" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-upload me-1"></i>Importar
    </a>
    <?php endif; ?>
    <a href="/almoxarifado/<?= $id ?>/exportar" class="btn btn-sm btn-outline-success">
      <i class="bi bi-file-earmark-excel me-1"></i>Exportar
    </a>
    <?php if ($isAdminAlm): ?>
    <a href="/almoxarifado/<?= $id ?>/editar" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-pencil me-1"></i>Editar
    </a>
    <button type="button" class="btn btn-sm btn-outline-warning" onclick="toggleModoAdmin()" id="btnModoAdmin">
      <i class="bi bi-check2-square me-1"></i>Selecionar
    </button>
    <?php elseif ($isAlmox): ?>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleModoSelecao()" id="btnModoSel">
      <i class="bi bi-check2-square me-1"></i>Selecionar
    </button>
    <?php endif; ?>
  </div>
</div>

<!-- Badges resumo -->
<div class="d-flex flex-wrap gap-2 mb-3">
  <span class="badge rounded-pill bg-info p-2 px-3">
    <i class="bi bi-box-seam me-1"></i><?= $totalAtivos ?> itens ativos
  </span>
  <?php if ($valorTotal > 0): ?>
  <span class="badge rounded-pill bg-success p-2 px-3">
    <i class="bi bi-currency-dollar me-1"></i><?= fmt_dinheiro($valorTotal) ?>
  </span>
  <?php endif; ?>
</div>

<!-- Filtros por categoria (pills) -->
<div class="d-flex flex-wrap gap-2 mb-3" id="pillsCat">
  <button class="btn btn-sm rounded-pill pill-cat active-cat"
          id="pill-cat-todos"
          onclick="filtrarCat('todos')"
          style="background:var(--accent-light);color:var(--accent);border:1px solid var(--accent);font-weight:700">
    📋 Todos (<?= $totalAtivos ?>)
  </button>
  <?php foreach ($catConfig as $catKey => $catCfg):
    $n = $catContadores[$catKey] ?? 0;
    if ($n === 0) continue;
  ?>
  <button class="btn btn-sm rounded-pill pill-cat"
          id="pill-cat-<?= $catKey ?>"
          onclick="filtrarCat('<?= $catKey ?>')"
          data-cat="<?= $catKey ?>"
          style="background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0">
    <?= $catCfg['emoji'] ?> <?= $catCfg['label'] ?> (<?= $n ?>)
  </button>
  <?php endforeach; ?>
</div>

<!-- Barra de busca -->
<div class="d-flex gap-2 mb-3">
  <div class="input-group input-group-sm" style="max-width:360px">
    <span class="input-group-text"><i class="bi bi-search"></i></span>
    <input type="text" id="buscaItem" class="form-control"
           placeholder="Buscar item por nome ou código..."
           oninput="filtrarItens(this.value)"
           value="<?= h($filtro) ?>">
    <button class="btn btn-outline-secondary" type="button" onclick="limparBusca()">
      <i class="bi bi-x"></i>
    </button>
  </div>
</div>

<!-- Tabela desktop -->
<div class="card d-none d-md-block mb-3">
  <form method="POST" action="/almoxarifado/<?= $id ?>/deletar-lote" id="formLote">
    <?= csrf_field() ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th id="thCheck" style="width:40px;display:none">
              <input type="checkbox" class="form-check-input" id="checkAll"
                     onchange="toggleTodos(this.checked)">
            </th>
            <th style="width:12%">Código</th>
            <th>Item</th>
            <th style="width:9%">Unidade</th>
            <th style="width:10%" class="text-center">Qtd. Atual</th>
            <th style="width:10%" class="text-center">Mínimo</th>
            <th style="width:12%" class="text-center">Nível</th>
            <th style="width:9%" class="text-center">Status</th>
            <?php if ($isAlmox): ?>
            <th style="width:10%" class="text-center">Valor Unit.</th>
            <?php endif; ?>
            <th style="width:8%" class="text-center">Ações</th>
          </tr>
        </thead>
        <tbody id="tbodyItens">
        <?php if (empty($itens)): ?>
          <tr><td colspan="9" class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-2 mb-2 d-block"></i>Nenhum item encontrado.
          </td></tr>
        <?php endif; ?>
        <?php foreach ($itens as $it):
          $st     = status_item((float)$it['quantidade'], (float)$it['estoque_minimo']);
          $rowCls = !$it['ativo'] ? 'table-secondary opacity-50' : '';
          $cat    = $it['categoria'] ?? 'geral';
          $minimo = (float)$it['estoque_minimo'];
          $qtd    = (float)$it['quantidade'];
          $pct    = $minimo > 0 ? min(100, round($qtd / $minimo * 100)) : ($qtd > 0 ? 100 : 0);
          $pctCor = $pct <= 0 ? 'danger' : ($pct <= 50 ? 'warning' : 'success');
          $qtdCor = $st === 'critico' ? 'text-danger fw-bold' : ($st === 'alerta' ? 'text-warning fw-bold' : 'text-success fw-bold');
          $buscaAttr = strtolower($it['nome'] . ' ' . $it['codigo']);
        ?>
          <tr class="item-row <?= $rowCls ?>"
              data-cat="<?= h($cat) ?>"
              data-busca="<?= h($buscaAttr) ?>">
            <td id="tdCheck-<?= $it['id'] ?>" style="display:none">
              <input type="checkbox" class="form-check-input check-item"
                     name="item_ids[]" value="<?= $it['id'] ?>"
                     onchange="atualizarBarraLote()">
            </td>
            <td class="text-muted small font-monospace"><?= h($it['codigo']) ?></td>
            <td>
              <a href="/item/<?= $it['id'] ?>"
                 class="fw-semibold text-decoration-none text-dark"><?= h($it['nome']) ?></a>
              <?php if ($it['fixado']): ?>
              <i class="bi bi-pin-fill text-warning ms-1" title="Fixado"></i>
              <?php endif; ?>
              <?php if (!$it['ativo']): ?>
              <span class="badge bg-secondary ms-1">Desativado</span>
              <?php endif; ?>
              <span class="badge ms-1 rounded-pill"
                    style="background:#f1f5f9;color:#64748b;font-size:0.68rem;border:1px solid #e2e8f0">
                <?= categoria_label($cat) ?>
              </span>
            </td>
            <td class="text-muted small"><?= h($it['unidade']) ?></td>
            <td class="text-center <?= $qtdCor ?>"><?= fmt_qtd($qtd) ?></td>
            <td class="text-center text-muted small"><?= fmt_qtd($minimo) ?></td>
            <td class="text-center" style="min-width:80px">
              <div class="d-flex align-items-center gap-1">
                <div class="progress flex-grow-1" style="height:6px;border-radius:4px">
                  <div class="progress-bar bg-<?= $pctCor ?>" style="width:<?= $pct ?>%"></div>
                </div>
                <small class="text-<?= $pctCor ?>" style="width:34px;text-align:right;font-size:0.72rem">
                  <?= $pct ?>%
                </small>
              </div>
            </td>
            <td class="text-center"><?= status_badge($st) ?></td>
            <?php if ($isAlmox): ?>
            <td class="text-center">
              <?php if ($it['valor_unitario'] > 0): ?>
                <span class="fw-semibold text-success small"><?= fmt_dinheiro((float)$it['valor_unitario']) ?></span>
              <?php else: ?>
                <a href="/item/<?= $it['id'] ?>/editar" class="text-muted small" title="Adicionar valor">
                  <i class="bi bi-plus-circle me-1"></i>—
                </a>
              <?php endif; ?>
            </td>
            <?php endif; ?>
            <td class="text-center">
              <a href="/item/<?= $it['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver">
                <i class="bi bi-eye"></i>
              </a>
              <?php if ($isAlmox): ?>
              <a href="/item/<?= $it['id'] ?>/editar" class="btn btn-sm btn-outline-secondary ms-1" title="Editar">
                <i class="bi bi-pencil"></i>
              </a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </form>
</div>

<!-- Cards mobile -->
<div class="d-md-none mb-3">
  <?php if (empty($itens)): ?>
  <div class="card p-4 text-center text-muted">
    <i class="bi bi-inbox fs-2 mb-2 d-block"></i>Nenhum item.
  </div>
  <?php endif; ?>
  <?php foreach ($itens as $it):
    $st     = status_item((float)$it['quantidade'], (float)$it['estoque_minimo']);
    $cat    = $it['categoria'] ?? 'geral';
    $qtd    = (float)$it['quantidade'];
    $minimo = (float)$it['estoque_minimo'];
    $pct    = $minimo > 0 ? min(100, round($qtd / $minimo * 100)) : ($qtd > 0 ? 100 : 0);
    $pctCor = $pct <= 0 ? 'danger' : ($pct <= 50 ? 'warning' : 'success');
    $deficit = max(0, $minimo - $qtd);
    $buscaAttr = strtolower($it['nome'] . ' ' . $it['codigo']);
  ?>
  <div class="card mb-2 item-row <?= !$it['ativo'] ? 'opacity-50' : '' ?>"
       data-cat="<?= h($cat) ?>"
       data-busca="<?= h($buscaAttr) ?>">
    <div class="card-body p-3">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
          <a href="/item/<?= $it['id'] ?>" class="fw-bold text-decoration-none text-dark">
            <?= h($it['nome']) ?>
          </a>
          <div class="text-muted font-monospace" style="font-size:0.75rem"><?= h($it['codigo']) ?></div>
        </div>
        <?= status_badge($st) ?>
      </div>
      <div class="d-flex gap-4 mb-2">
        <div>
          <div class="text-muted" style="font-size:0.72rem">Quantidade</div>
          <div class="fw-bold text-<?= $pctCor ?>"><?= fmt_qtd($qtd) ?> <?= h($it['unidade']) ?></div>
        </div>
        <?php if ($deficit > 0): ?>
        <div>
          <div class="text-muted" style="font-size:0.72rem">Déficit</div>
          <div class="fw-bold text-danger">-<?= fmt_qtd($deficit) ?></div>
        </div>
        <?php endif; ?>
      </div>
      <div class="progress mb-2" style="height:6px">
        <div class="progress-bar bg-<?= $pctCor ?>" style="width:<?= $pct ?>%"></div>
      </div>
      <div class="d-flex gap-2">
        <a href="/item/<?= $it['id'] ?>" class="btn btn-sm btn-outline-primary flex-grow-1">
          <i class="bi bi-eye me-1"></i>Ver
        </a>
        <?php if ($isAlmox): ?>
        <a href="/item/<?= $it['id'] ?>/editar" class="btn btn-sm btn-outline-secondary flex-grow-1">
          <i class="bi bi-pencil me-1"></i>Editar
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div id="semItens" class="text-center py-5 d-none">
  <i class="bi bi-box-seam fs-1 text-muted mb-3"></i>
  <div class="fw-semibold text-muted">Nenhum item encontrado para este filtro.</div>
</div>

<!-- Botões admin -->
<?php if ($isAdminAlm): ?>
<div class="mt-2 d-flex gap-2 flex-wrap">
  <a href="/admin/reativar_itens?alm=<?= $id ?>" class="btn btn-outline-warning btn-sm">
    <i class="bi bi-arrow-counterclockwise me-1"></i>Itens Desativados
  </a>
</div>
<?php endif; ?>

<!-- Barra flutuante de seleção (almoxarife / modo simples) -->
<div id="barraLote"
     style="display:none;position:fixed;bottom:28px;left:50%;transform:translateX(-50%);
            z-index:1050;background:#fff;border:1px solid var(--border);border-radius:16px;
            box-shadow:0 8px 32px rgba(0,0,0,0.18);padding:14px 24px;
            align-items:center;gap:16px;min-width:320px">
  <div>
    <span class="fw-semibold" id="textoLote">0 selecionado(s)</span>
    <div class="text-muted small">Ação em lote</div>
  </div>
  <?php if ($isAdminAlm): ?>
  <!-- Admin: modal de ação -->
  <button type="button" class="btn btn-warning btn-sm ms-auto"
          data-bs-toggle="modal" data-bs-target="#modalAcaoLote">
    <i class="bi bi-sliders me-1"></i>Ação
  </button>
  <?php else: ?>
  <!-- Almoxarife: excluir direto -->
  <button type="button" class="btn btn-danger btn-sm ms-auto"
          onclick="confirmarLote()">
    <i class="bi bi-trash3 me-1"></i>Excluir Selecionados
  </button>
  <?php endif; ?>
  <button type="button" class="btn btn-outline-secondary btn-sm"
          onclick="limparSelecaoLote()">
    <i class="bi bi-x"></i>
  </button>
</div>

<!-- Modal Admin: Transferir ou Excluir -->
<?php if ($isAdminAlm): ?>
<div class="modal fade" id="modalAcaoLote" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-sliders me-2"></i>Escolher Ação</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="d-grid gap-3">
          <button type="button" class="btn btn-outline-primary"
                  data-bs-dismiss="modal"
                  data-bs-toggle="modal" data-bs-target="#modalTransferir">
            <i class="bi bi-arrow-left-right me-2"></i>Transferir para outro almoxarifado
          </button>
          <button type="button" class="btn btn-outline-danger"
                  data-bs-dismiss="modal"
                  onclick="confirmarLote()">
            <i class="bi bi-trash3 me-2"></i>Excluir definitivamente
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Transferir -->
<div class="modal fade" id="modalTransferir" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-arrow-left-right me-2"></i>Transferir Itens</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/admin/transferir_itens">
        <?= csrf_field() ?>
        <input type="hidden" name="origem_id" value="<?= $id ?>">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Destino</label>
            <select name="destino_id" class="form-select" required>
              <option value="">Selecione o destino...</option>
              <?php
              $outros = db()->query('SELECT id,nome FROM almoxarifado ORDER BY nome')->fetchAll();
              foreach ($outros as $o) {
                  if ($o['id'] == $id) continue;
                  echo '<option value="' . $o['id'] . '">' . h($o['nome']) . '</option>';
              }
              ?>
            </select>
          </div>
          <div style="max-height:300px;overflow-y:auto;border:1px solid var(--border);border-radius:8px">
            <?php foreach ($itens as $it):
              if (!$it['ativo']) continue;
            ?>
            <div class="d-flex align-items-center gap-2 p-2 border-bottom">
              <input type="checkbox" name="item_ids[]" value="<?= $it['id'] ?>"
                     class="form-check-input mt-0 check-transferir"
                     id="transf-<?= $it['id'] ?>">
              <label for="transf-<?= $it['id'] ?>" class="flex-grow-1 mb-0" style="cursor:pointer">
                <span class="fw-semibold small"><?= h($it['nome']) ?></span>
                <span class="text-muted small ms-2">
                  <?= fmt_qtd((float)$it['quantidade']) ?> <?= h($it['unidade']) ?>
                </span>
              </label>
              <input type="number" name="qtd_<?= $it['id'] ?>"
                     step="0.01" min="0.01" max="<?= $it['quantidade'] ?>"
                     placeholder="Qtd"
                     class="form-control form-control-sm" style="width:80px">
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-arrow-left-right me-1"></i>Transferir
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
let _catAtual  = 'todos';
let _buscaItem = '';
let _modoSel   = false;

function filtrarCat(cat) {
  _catAtual = cat;
  // Atualizar pills
  document.querySelectorAll('.pill-cat').forEach(el => {
    el.style.fontWeight = '';
    el.style.background = '#f1f5f9';
    el.style.color      = '#64748b';
    el.style.border     = '1px solid #e2e8f0';
  });
  const pa = document.getElementById('pill-cat-' + cat);
  if (pa) {
    pa.style.background  = 'var(--accent-light)';
    pa.style.color       = 'var(--accent)';
    pa.style.border      = '1px solid var(--accent)';
    pa.style.fontWeight  = '700';
  }
  aplicarFiltrosItens();
}

function filtrarItens(q) {
  _buscaItem = q.toLowerCase().trim();
  aplicarFiltrosItens();
}

function limparBusca() {
  document.getElementById('buscaItem').value = '';
  _buscaItem = '';
  aplicarFiltrosItens();
}

function aplicarFiltrosItens() {
  const rows = document.querySelectorAll('.item-row');
  let vis = 0;
  rows.forEach(row => {
    const catOk   = _catAtual === 'todos' || row.dataset.cat === _catAtual;
    const buscaOk = !_buscaItem || row.dataset.busca.includes(_buscaItem);
    if (catOk && buscaOk) { row.style.display = ''; vis++; }
    else row.style.display = 'none';
  });
  document.getElementById('semItens').classList.toggle('d-none', vis > 0);
}

// Modo seleção (almoxarife)
function toggleModoSelecao() {
  _modoSel = !_modoSel;
  const btn = document.getElementById('btnModoSel');
  const th  = document.getElementById('thCheck');
  if (btn) btn.classList.toggle('btn-warning', _modoSel);
  document.querySelectorAll('[id^="tdCheck-"]').forEach(td => td.style.display = _modoSel ? '' : 'none');
  if (th) th.style.display = _modoSel ? '' : 'none';
  if (!_modoSel) limparSelecaoLote();
}

// Modo admin
function toggleModoAdmin() {
  _modoSel = !_modoSel;
  const btn = document.getElementById('btnModoAdmin');
  if (btn) btn.classList.toggle('btn-warning', _modoSel);
  const th = document.getElementById('thCheck');
  document.querySelectorAll('[id^="tdCheck-"]').forEach(td => td.style.display = _modoSel ? '' : 'none');
  if (th) th.style.display = _modoSel ? '' : 'none';
  if (!_modoSel) limparSelecaoLote();
}

function toggleTodos(checked) {
  document.querySelectorAll('.check-item').forEach(cb => cb.checked = checked);
  atualizarBarraLote();
}

function atualizarBarraLote() {
  const n = document.querySelectorAll('.check-item:checked').length;
  const barra = document.getElementById('barraLote');
  const texto = document.getElementById('textoLote');
  if (n > 0) {
    barra.style.display = 'flex';
    texto.textContent   = n + ' item(ns) selecionado(s)';
  } else {
    barra.style.display = 'none';
  }
}

function limparSelecaoLote() {
  document.querySelectorAll('.check-item:checked').forEach(cb => cb.checked = false);
  const ca = document.getElementById('checkAll');
  if (ca) ca.checked = false;
  document.getElementById('barraLote').style.display = 'none';
}

function confirmarLote() {
  const n = document.querySelectorAll('.check-item:checked').length;
  if (!n) return;
  if (!confirm('Excluir ' + n + ' item(ns) selecionado(s)?\n\nEsta ação não pode ser desfeita.')) return;
  document.getElementById('formLote').submit();
}
</script>
