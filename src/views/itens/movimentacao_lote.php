<?php /* views/itens/movimentacao_lote.php — redesign estilo Lovable */ ?>

<!-- ── Cabeçalho ─────────────────────────────────────────────────────────── -->
<div class="mb-6">
  <h1 class="mov-titulo">Registro de Movimentação</h1>
  <p class="mov-sub">Lance entradas e saídas de insumos no almoxarifado</p>
</div>

<?php if (!empty($_SESSION['flash'])): foreach ($_SESSION['flash'] as $fl): unset($_SESSION['flash']); ?>
<div class="mov-alert mov-alert--<?= $fl['tipo'] === 'success' ? 'ok' : 'err' ?> mb-4">
  <?= h($fl['msg']) ?>
</div>
<?php endforeach; endif; ?>

<!-- ── Formulário ────────────────────────────────────────────────────────── -->
<form method="POST" action="/movimentacao/lote" id="frmMovLote" class="mov-card mb-6">
  <?= csrf_field() ?>

  <!-- Linha 1: Almoxarifado / Tipo / Data -->
  <div class="mov-grid3 mb-4">

    <div class="mov-field">
      <label class="mov-label">Almoxarifado <span class="mov-req">*</span></label>
      <select name="almoxarifado_id" id="selAlm" class="mov-input" required onchange="carregarItens(this.value)">
        <option value="">Selecione...</option>
        <?php foreach ($almoxarifados as $alm): ?>
        <option value="<?= $alm['id'] ?>"><?= h($alm['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mov-field">
      <label class="mov-label">Tipo <span class="mov-req">*</span></label>
      <div class="mov-tipo-toggle">
        <button type="button" id="btnEntrada" onclick="setTipo('entrada')" class="mov-tipo-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m17 7-10 10M7 7h10v10"/></svg>
          Entrada
        </button>
        <button type="button" id="btnSaida" onclick="setTipo('saida')" class="mov-tipo-btn mov-tipo-btn--active">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17 17 7M7 7h10v10"/></svg>
          Saída
        </button>
      </div>
      <input type="hidden" name="tipo" id="inputTipo" value="saida">
    </div>

    <div class="mov-field">
      <label class="mov-label">Data <span class="mov-req">*</span></label>
      <input type="date" name="data_mov" class="mov-input" value="<?= date('Y-m-d') ?>" required>
    </div>

  </div>

  <!-- Linha 2: Responsável / Observação -->
  <div class="mov-grid2 mb-4">
    <div class="mov-field">
      <label class="mov-label">Responsável</label>
      <input type="text" name="responsavel" class="mov-input" placeholder="Quem está liberando...">
    </div>
    <div class="mov-field">
      <label class="mov-label">Observação</label>
      <input type="text" name="observacao" class="mov-input" placeholder="Opcional...">
    </div>
  </div>

  <!-- Linhas de itens -->
  <div class="mov-itens-box mb-4">
    <div class="mov-itens-header">
      <span class="mov-itens-titulo">Itens da movimentação</span>
      <button type="button" onclick="addLinha()" class="mov-btn-add">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Adicionar linha
      </button>
    </div>

    <div id="linhasItens">
      <!-- Linha inicial -->
      <div class="mov-linha" id="linha_0">
        <div class="mov-linha-grid">

          <div class="mov-field mov-field--item">
            <label class="mov-label">Item <span class="mov-req">*</span></label>
            <div class="mov-busca-wrap">
              <svg class="mov-busca-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
              <input type="text" class="mov-input mov-input--busca" placeholder="Buscar item..."
                     oninput="filtrarItens(this, 0)" autocomplete="off">
              <input type="hidden" name="item_id_0" id="item_id_0">
              <ul class="mov-sugestoes" id="sugestoes_0"></ul>
            </div>
          </div>

          <div class="mov-field mov-field--qtd">
            <label class="mov-label">Qtd <span class="mov-req">*</span></label>
            <input type="number" name="quantidade_0" class="mov-input" step="any" min="0.01" placeholder="0">
          </div>

          <div class="mov-field mov-field--colab">
            <label class="mov-label">Colaborador</label>
            <input type="text" name="colaborador_0" class="mov-input" placeholder="Nome...">
          </div>

          <div class="mov-field mov-field--lib">
            <label class="mov-label">Liberado por</label>
            <input type="text" name="liberado_por_0" class="mov-input" placeholder="Opcional">
          </div>

          <div class="mov-field mov-field--del">
            <label class="mov-label">&nbsp;</label>
            <button type="button" onclick="removeLinha(0)" class="mov-btn-del" disabled>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
            </button>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- Botões -->
  <div class="mov-footer">
    <button type="button" onclick="resetForm()" class="mov-btn-cancel">Cancelar</button>
    <button type="submit" class="mov-btn-confirm">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
      Confirmar Movimentação
    </button>
  </div>

</form>

<!-- ── Histórico ─────────────────────────────────────────────────────────── -->
<div class="mov-card">
  <div class="mov-hist-header">
    <div>
      <h2 class="mov-hist-titulo">Histórico de movimentações</h2>
      <p class="mov-hist-sub">Últimos 50 registros</p>
    </div>
  </div>

  <div class="table-responsive">
    <table class="mov-tabela">
      <thead>
        <tr>
          <th>Data</th>
          <th>Item</th>
          <th>Almoxarifado</th>
          <th>Tipo</th>
          <th class="text-end">Qtd.</th>
          <th>Colaborador</th>
          <th>Liberado por</th>
          <th>Devolvido</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($historico)): ?>
        <tr><td colspan="8" class="mov-tabela-vazio">Nenhuma movimentação registrada ainda.</td></tr>
        <?php else: ?>
        <?php foreach ($historico as $m): ?>
        <tr>
          <td class="mov-data"><?= date('d/m/Y H:i', strtotime($m['data'])) ?></td>
          <td class="mov-item-nome"><?= h($m['item_nome'] ?? '—') ?></td>
          <td class="text-muted"><?= h($m['alm_nome'] ?? '—') ?></td>
          <td>
            <?php if ($m['tipo'] === 'entrada'): ?>
              <span class="mov-badge mov-badge--entrada">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m17 7-10 10M7 7h10v10"/></svg>
                Entrada
              </span>
            <?php else: ?>
              <span class="mov-badge mov-badge--saida">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 17 17 7M7 7h10v10"/></svg>
                Saída
              </span>
            <?php endif; ?>
          </td>
          <td class="text-end mov-qtd"><?= fmt_qtd((float)$m['quantidade']) ?> <span class="mov-und"><?= h($m['unidade'] ?? '') ?></span></td>
          <td class="text-muted"><?= h($m['responsavel'] ?? '—') ?></td>
          <td class="text-muted">—</td>
          <td>
            <?php if ($m['devolvido']): ?>
              <span class="mov-badge mov-badge--ok">Sim</span>
            <?php else: ?>
              <span class="mov-badge mov-badge--no">Não</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Estilos ────────────────────────────────────────────────────────────── -->
<style>
/* Reset da página */
.mov-titulo { font-size:1.75rem; font-weight:800; letter-spacing:-.03em; color:var(--bs-body-color,#111); margin:0; }
.mov-sub    { font-size:.85rem; color:#6b7280; margin-top:4px; }

/* Cards */
.mov-card {
  background:#fff; border:1px solid #e5e7eb; border-radius:16px;
  padding:24px; box-shadow:0 1px 4px rgba(0,0,0,.06);
}
html[data-theme="dark"] .mov-card { background:#161b22; border-color:#30363d; }

/* Grids */
.mov-grid3 { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
.mov-grid2 { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; }
@media(max-width:640px) { .mov-grid3,.mov-grid2 { grid-template-columns:1fr; } }

/* Campos */
.mov-field { display:flex; flex-direction:column; gap:5px; }
.mov-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#6b7280; }
.mov-req   { color:#f97316; }
.mov-input {
  height:40px; border:1px solid #e5e7eb; border-radius:8px;
  padding:0 12px; font-size:.875rem; color:#111; background:#fff;
  outline:none; transition:border-color .15s, box-shadow .15s; width:100%;
}
.mov-input:focus { border-color:#f97316; box-shadow:0 0 0 3px rgba(249,115,22,.12); }
html[data-theme="dark"] .mov-input { background:#0d1117; color:#e2e8f0; border-color:#30363d; }
html[data-theme="dark"] .mov-input:focus { border-color:#f97316; }

/* Toggle Entrada/Saída */
.mov-tipo-toggle {
  display:flex; height:40px; border:1px solid #e5e7eb; border-radius:8px;
  background:#f9fafb; padding:3px; gap:3px;
}
html[data-theme="dark"] .mov-tipo-toggle { background:#0d1117; border-color:#30363d; }
.mov-tipo-btn {
  flex:1; display:flex; align-items:center; justify-content:center; gap:6px;
  border:none; border-radius:6px; font-size:.82rem; font-weight:600;
  cursor:pointer; transition:all .15s; background:transparent; color:#6b7280;
}
.mov-tipo-btn:hover { color:#111; }
.mov-tipo-btn--active { background:#f97316 !important; color:#fff !important; box-shadow:0 1px 4px rgba(249,115,22,.35); }
.mov-tipo-btn--entrada-active { background:#22c55e !important; color:#fff !important; box-shadow:0 1px 4px rgba(34,197,94,.35); }

/* Caixa de itens */
.mov-itens-box {
  border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb; padding:16px;
}
html[data-theme="dark"] .mov-itens-box { background:#0d1117; border-color:#30363d; }
.mov-itens-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
.mov-itens-titulo { font-size:.875rem; font-weight:700; color:#111; }
html[data-theme="dark"] .mov-itens-titulo { color:#e2e8f0; }

.mov-btn-add {
  display:inline-flex; align-items:center; gap:6px;
  border:1px solid #e5e7eb; border-radius:8px; background:#fff;
  padding:6px 12px; font-size:.8rem; font-weight:600; cursor:pointer;
  color:#374151; transition:all .15s;
}
.mov-btn-add:hover { border-color:#f97316; color:#f97316; }
html[data-theme="dark"] .mov-btn-add { background:#161b22; border-color:#30363d; color:#e2e8f0; }

/* Linha de item */
.mov-linha { margin-bottom:10px; }
.mov-linha-grid {
  display:grid;
  grid-template-columns: 1fr 100px 1fr 1fr 44px;
  gap:10px; align-items:end;
}
@media(max-width:768px) { .mov-linha-grid { grid-template-columns:1fr 1fr; } }
.mov-field--del { display:flex; align-items:flex-end; }

/* Busca de item */
.mov-busca-wrap { position:relative; }
.mov-busca-icon { position:absolute; left:10px; top:12px; color:#9ca3af; pointer-events:none; }
.mov-input--busca { padding-left:34px; }
.mov-sugestoes {
  position:absolute; z-index:100; top:calc(100% + 4px); left:0; right:0;
  background:#fff; border:1px solid #e5e7eb; border-radius:8px;
  box-shadow:0 4px 16px rgba(0,0,0,.1); list-style:none; padding:4px; margin:0;
  max-height:200px; overflow-y:auto; display:none;
}
.mov-sugestoes.aberto { display:block; }
.mov-sugestoes li button {
  width:100%; text-align:left; padding:8px 10px; font-size:.82rem;
  border:none; background:transparent; cursor:pointer; border-radius:6px;
  color:#111; transition:background .1s;
}
.mov-sugestoes li button:hover { background:#f3f4f6; }
html[data-theme="dark"] .mov-sugestoes { background:#161b22; border-color:#30363d; }
html[data-theme="dark"] .mov-sugestoes li button { color:#e2e8f0; }
html[data-theme="dark"] .mov-sugestoes li button:hover { background:#21262d; }

/* Botão deletar linha */
.mov-btn-del {
  width:40px; height:40px; border:1px solid #e5e7eb; border-radius:8px;
  background:#fff; cursor:pointer; color:#9ca3af; display:grid; place-items:center;
  transition:all .15s;
}
.mov-btn-del:hover:not(:disabled) { border-color:#ef4444; color:#ef4444; }
.mov-btn-del:disabled { opacity:.3; cursor:not-allowed; }
html[data-theme="dark"] .mov-btn-del { background:#0d1117; border-color:#30363d; }

/* Rodapé do form */
.mov-footer { display:flex; justify-content:flex-end; gap:10px; }
.mov-btn-cancel {
  padding:10px 20px; border:1px solid #e5e7eb; border-radius:8px;
  background:#fff; font-size:.875rem; font-weight:600; cursor:pointer; color:#374151;
  transition:background .15s;
}
.mov-btn-cancel:hover { background:#f3f4f6; }
html[data-theme="dark"] .mov-btn-cancel { background:#161b22; border-color:#30363d; color:#e2e8f0; }

.mov-btn-confirm {
  display:inline-flex; align-items:center; gap:8px;
  padding:10px 22px; border:none; border-radius:8px;
  background:#f97316; color:#fff; font-size:.875rem; font-weight:700;
  cursor:pointer; transition:all .15s; box-shadow:0 1px 4px rgba(249,115,22,.35);
}
.mov-btn-confirm:hover { background:#ea6c0a; }

/* Histórico */
.mov-hist-header { margin-bottom:16px; }
.mov-hist-titulo { font-size:1.1rem; font-weight:700; margin:0; }
.mov-hist-sub    { font-size:.78rem; color:#6b7280; margin:2px 0 0; }

.mov-tabela { width:100%; font-size:.82rem; border-collapse:collapse; }
.mov-tabela thead tr { border-bottom:1px solid #e5e7eb; }
.mov-tabela thead th {
  padding:8px 12px; font-size:10px; font-weight:700; text-transform:uppercase;
  letter-spacing:.07em; color:#6b7280; white-space:nowrap;
}
.mov-tabela tbody tr { border-bottom:1px solid #f3f4f6; transition:background .1s; }
.mov-tabela tbody tr:hover { background:#fafafa; }
html[data-theme="dark"] .mov-tabela tbody tr:hover { background:#21262d; }
.mov-tabela td { padding:10px 12px; }
.mov-tabela-vazio { text-align:center; padding:40px; color:#6b7280; }

.mov-data      { color:#6b7280; white-space:nowrap; font-size:.78rem; }
.mov-item-nome { font-weight:600; color:#111; }
html[data-theme="dark"] .mov-item-nome { color:#e2e8f0; }
.mov-qtd       { font-weight:700; font-family:monospace; }
.mov-und       { font-weight:400; color:#6b7280; font-size:.72rem; }
.text-muted    { color:#6b7280 !important; }
.text-end      { text-align:right; }

/* Badges */
.mov-badge {
  display:inline-flex; align-items:center; gap:4px;
  padding:3px 10px; border-radius:999px; font-size:.72rem; font-weight:700;
}
.mov-badge--entrada { background:#dcfce7; color:#16a34a; }
.mov-badge--saida   { background:#fff7ed; color:#ea580c; }
.mov-badge--ok      { background:#dcfce7; color:#16a34a; }
.mov-badge--no      { background:#f3f4f6; color:#6b7280; }

/* Alertas */
.mov-alert { padding:12px 16px; border-radius:10px; font-size:.875rem; font-weight:500; }
.mov-alert--ok  { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.mov-alert--err { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
</style>

<!-- ── JavaScript ─────────────────────────────────────────────────────────── -->
<script>
// Dados dos itens por almoxarifado (injetados pelo controller)
const itensJson = <?= $itens_json ?? '{}' ?>;
let linhaCount = 0;

// ── Toggle Entrada/Saída ──────────────────────────────────────────────────
function setTipo(tipo) {
  document.getElementById('inputTipo').value = tipo;
  const btnE = document.getElementById('btnEntrada');
  const btnS = document.getElementById('btnSaida');
  btnE.className = 'mov-tipo-btn' + (tipo === 'entrada' ? ' mov-tipo-btn--entrada-active' : '');
  btnS.className = 'mov-tipo-btn' + (tipo === 'saida'   ? ' mov-tipo-btn--active' : '');
}

// ── Carregar itens do almoxarifado ────────────────────────────────────────
function carregarItens(almId) {
  // Limpa buscas existentes
  document.querySelectorAll('[id^="item_id_"]').forEach(i => {
    i.value = '';
    const idx = i.id.replace('item_id_','');
    const busca = document.querySelector(`#linha_${idx} .mov-input--busca`);
    if (busca) busca.value = '';
  });
}

// ── Filtrar itens na busca ────────────────────────────────────────────────
function filtrarItens(input, idx) {
  const almId = document.getElementById('selAlm').value;
  const lista = itensJson[almId] || [];
  const termo  = input.value.trim().toLowerCase();
  const ul     = document.getElementById(`sugestoes_${idx}`);

  if (!termo || !almId) { ul.innerHTML = ''; ul.classList.remove('aberto'); return; }

  const filtrado = lista.filter(i => i.nome.toLowerCase().includes(termo)).slice(0, 8);
  if (!filtrado.length) { ul.innerHTML = ''; ul.classList.remove('aberto'); return; }

  ul.innerHTML = filtrado.map(i =>
    `<li><button type="button" onclick="selecionarItem(${idx}, ${i.id}, '${i.nome.replace(/'/g,"\\'")} (${i.categoria || 'geral'})')">
      ${i.nome}
    </button></li>`
  ).join('');
  ul.classList.add('aberto');
}

function selecionarItem(idx, id, nome) {
  document.getElementById(`item_id_${idx}`).value = id;
  document.querySelector(`#linha_${idx} .mov-input--busca`).value = nome;
  document.getElementById(`sugestoes_${idx}`).classList.remove('aberto');
}

// Fecha sugestões ao clicar fora
document.addEventListener('click', e => {
  if (!e.target.closest('.mov-busca-wrap')) {
    document.querySelectorAll('.mov-sugestoes').forEach(u => u.classList.remove('aberto'));
  }
});

// ── Adicionar linha ───────────────────────────────────────────────────────
function addLinha() {
  linhaCount++;
  const idx = linhaCount;
  const div = document.createElement('div');
  div.className = 'mov-linha';
  div.id = `linha_${idx}`;
  div.innerHTML = `
    <div class="mov-linha-grid">
      <div class="mov-field mov-field--item">
        <label class="mov-label">Item <span class="mov-req">*</span></label>
        <div class="mov-busca-wrap">
          <svg class="mov-busca-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input type="text" class="mov-input mov-input--busca" placeholder="Buscar item..."
                 oninput="filtrarItens(this, ${idx})" autocomplete="off">
          <input type="hidden" name="item_id_${idx}" id="item_id_${idx}">
          <ul class="mov-sugestoes" id="sugestoes_${idx}"></ul>
        </div>
      </div>
      <div class="mov-field mov-field--qtd">
        <label class="mov-label">Qtd <span class="mov-req">*</span></label>
        <input type="number" name="quantidade_${idx}" class="mov-input" step="any" min="0.01" placeholder="0">
      </div>
      <div class="mov-field mov-field--colab">
        <label class="mov-label">Colaborador</label>
        <input type="text" name="colaborador_${idx}" class="mov-input" placeholder="Nome...">
      </div>
      <div class="mov-field mov-field--lib">
        <label class="mov-label">Liberado por</label>
        <input type="text" name="liberado_por_${idx}" class="mov-input" placeholder="Opcional">
      </div>
      <div class="mov-field mov-field--del">
        <label class="mov-label">&nbsp;</label>
        <button type="button" onclick="removeLinha(${idx})" class="mov-btn-del">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
        </button>
      </div>
    </div>`;
  document.getElementById('linhasItens').appendChild(div);
  atualizarBotoesDeletar();
  div.querySelector('.mov-input--busca').focus();
}

function removeLinha(idx) {
  document.getElementById(`linha_${idx}`)?.remove();
  atualizarBotoesDeletar();
}

function atualizarBotoesDeletar() {
  const linhas = document.querySelectorAll('.mov-linha');
  linhas.forEach(l => {
    const btn = l.querySelector('.mov-btn-del');
    if (btn) btn.disabled = linhas.length <= 1;
  });
}

// ── Reset ─────────────────────────────────────────────────────────────────
function resetForm() {
  document.getElementById('frmMovLote').reset();
  setTipo('saida');
  // Mantém só a primeira linha
  const linhas = document.querySelectorAll('.mov-linha');
  linhas.forEach((l, i) => { if (i > 0) l.remove(); });
  document.getElementById('item_id_0').value = '';
  document.querySelector('#linha_0 .mov-input--busca').value = '';
  linhaCount = 0;
}

// Inicializa
atualizarBotoesDeletar();
</script>
