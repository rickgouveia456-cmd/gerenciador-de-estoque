<?php /* views/requisicoes/mestre_nova.php */ ?>

<div class="d-flex align-items-center gap-2 mb-4">
  <a href="/requisicoes/mestre" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left"></i>
  </a>
  <div>
    <h5 class="fw-bold mb-0"><i class="bi bi-clipboard-plus me-2"></i>Solicitar Materiais</h5>
    <div class="text-muted small">Preencha os dados abaixo para enviar a requisição</div>
  </div>
</div>

<form method="POST" action="/requisicoes/mestre/nova" id="formReq">
  <?= csrf_field() ?>

  <div class="row g-4">
    <!-- Coluna principal -->
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header py-3"
             style="background:linear-gradient(135deg,#ff6b35,#ff8a50) !important;border-bottom:none !important">
          <h6 class="mb-0 text-white fw-bold">
            <i class="bi bi-clipboard-plus me-2"></i>Dados da Requisição
          </h6>
        </div>
        <div class="card-body p-4">

          <?php if ($u['perfil'] === 'mestre'): ?>
          <div class="alert alert-info d-flex gap-2 align-items-start mb-4" style="font-size:0.88rem">
            <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
            <div>
              <strong>Atenção:</strong> EPIs não estão disponíveis neste formulário.
              Para requisições de EPI, utilize o Módulo EPI.
            </div>
          </div>
          <?php endif; ?>

          <div class="row g-3 mb-4">
            <!-- Almoxarifado -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Almoxarifado <span class="text-danger">*</span></label>
              <?php if (count($almoxarifados) === 1): ?>
              <input type="hidden" name="almoxarifado_id" value="<?= $almoxarifados[0]['id'] ?>">
              <div class="form-control bg-light d-flex align-items-center gap-2" style="cursor:default">
                <i class="bi bi-building text-muted"></i>
                <span><?= h($almoxarifados[0]['nome']) ?></span>
              </div>
              <script>window._almIdFixo = <?= $almoxarifados[0]['id'] ?>;</script>
              <?php else: ?>
              <select name="almoxarifado_id" id="selAlmoxarifado" class="form-select" required
                      onchange="onAlmChange(this.value)">
                <option value="">Selecione o almoxarifado...</option>
                <?php foreach ($almoxarifados as $alm): ?>
                <option value="<?= $alm['id'] ?>"><?= h($alm['nome']) ?></option>
                <?php endforeach; ?>
              </select>
              <?php endif; ?>
            </div>

            <!-- Colaborador com autocomplete -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Colaborador que vai buscar <span class="text-danger">*</span></label>
              <div class="position-relative">
                <input type="text" name="colaborador" id="inputColaborador"
                       class="form-control" required autocomplete="off"
                       placeholder="Digite o nome do colaborador...">
                <div id="autocompleteList"
                     class="position-absolute w-100 bg-white border rounded-2 shadow-sm"
                     style="top:100%;left:0;z-index:300;max-height:200px;overflow-y:auto;display:none">
                </div>
              </div>
              <div class="form-text">
                <i class="bi bi-lightbulb me-1"></i>Digite pelo menos 1 letra para buscar colaboradores cadastrados.
              </div>
            </div>

            <!-- Observação -->
            <div class="col-12">
              <label class="form-label fw-semibold">Observação</label>
              <textarea name="observacao" class="form-control" rows="2"
                        placeholder="Alguma observação geral para esta requisição..."></textarea>
            </div>
          </div>

          <!-- Seção de itens -->
          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="form-label fw-semibold mb-0">
                Materiais <span class="text-danger">*</span>
              </label>
              <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddItem"
                      onclick="addLinha()">
                <i class="bi bi-plus me-1"></i>Adicionar Item
              </button>
            </div>

            <div id="linhasItens">
              <!-- Mensagem inicial -->
              <div id="msgVazio" class="text-center py-4 text-muted border rounded-2"
                   style="border-style:dashed !important">
                <i class="bi bi-box-seam fs-2 mb-2 d-block"></i>
                Clique em <strong>Adicionar Item</strong> para incluir materiais.
              </div>
            </div>
          </div>

          <div class="alert alert-warning d-flex gap-2 align-items-start" style="font-size:0.85rem">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
            <span>Verifique os itens e quantidades antes de enviar. A requisição será analisada pelo almoxarifado.</span>
          </div>
        </div>

        <div class="card-footer bg-transparent d-flex gap-2 justify-content-end py-3 px-4">
          <a href="/requisicoes/mestre" class="btn btn-outline-secondary">
            <i class="bi bi-x me-1"></i>Cancelar
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-send me-1"></i>Enviar Requisição
          </button>
        </div>
      </div>
    </div>

    <!-- Coluna resumo -->
    <div class="col-lg-4">
      <div class="card" style="position:sticky;top:80px">
        <div class="card-header py-3">
          <h6 class="mb-0 fw-bold"><i class="bi bi-list-check me-2"></i>Resumo da Requisição</h6>
        </div>
        <div class="card-body p-3">
          <div id="resumoVazio" class="text-center py-3 text-muted small">
            <i class="bi bi-inbox fs-2 mb-2 d-block text-muted"></i>
            Nenhum item adicionado ainda.
          </div>
          <ul class="list-group list-group-flush" id="resumoLista" style="display:none">
          </ul>
        </div>
        <div class="card-footer bg-transparent py-2 px-3">
          <div class="d-flex justify-content-between align-items-center text-muted small">
            <span>Total de itens:</span>
            <span class="fw-bold" id="resumoTotal" style="color:var(--accent)">0</span>
          </div>
        </div>
      </div>
    </div>
  </div><!-- /row -->
</form>

<script>
const _itensMestre = <?= json_encode($itensJson, JSON_UNESCAPED_UNICODE) ?>;
let _idxLinha = 0;

// Se almoxarifado fixo, inicializar imediatamente
<?php if (count($almoxarifados) === 1): ?>
window._almAtual = '<?= $almoxarifados[0]['id'] ?>';
<?php else: ?>
window._almAtual = '';
function onAlmChange(v) { window._almAtual = v; }
<?php endif; ?>

function getItensAlm() {
  return _itensMestre[window._almAtual] || [];
}

function addLinha() {
  const almId = window._almAtual;
  if (!almId) {
    const sel = document.getElementById('selAlmoxarifado');
    if (sel) {
      sel.classList.add('is-invalid');
      setTimeout(() => sel.classList.remove('is-invalid'), 2500);
    }
    alert('Selecione o almoxarifado antes de adicionar itens.');
    return;
  }
  const itens = getItensAlm();
  if (itens.length === 0) {
    alert('Nenhum item disponível neste almoxarifado.');
    return;
  }

  document.getElementById('msgVazio').style.display = 'none';

  const opts = itens.map(i =>
    `<option value="${i.id}" data-nome="${i.nome.replace(/"/g,'&quot;')}" data-unidade="${i.unidade}">
      ${i.nome} — ${parseFloat(i.quantidade).toFixed(0)} ${i.unidade} disponíveis
    </option>`
  ).join('');

  const idx = _idxLinha++;
  const div = document.createElement('div');
  div.className = 'linha-item d-flex gap-2 align-items-start mb-2 p-2 rounded-2';
  div.id = 'linha-' + idx;
  div.style.background = 'var(--bg)';
  div.innerHTML = `
    <div class="flex-grow-1">
      <select name="item_id_${idx}" class="form-select form-select-sm mb-1" required
              onchange="onItemChange(${idx},this)">
        <option value="">Selecione o item...</option>${opts}
      </select>
    </div>
    <div style="width:100px">
      <input type="number" name="quantidade_${idx}" class="form-control form-control-sm"
             placeholder="Qtd" step="0.01" min="0.01" required
             oninput="atualizarResumo(${idx})">
    </div>
    <div class="flex-grow-1">
      <input type="text" name="observacao_${idx}" class="form-control form-control-sm"
             placeholder="Obs. desta linha...">
    </div>
    <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0"
            onclick="removerLinha(${idx})">
      <i class="bi bi-trash"></i>
    </button>`;
  document.getElementById('linhasItens').appendChild(div);
  atualizarResumoGeral();
}

function onItemChange(idx, sel) {
  atualizarResumo(idx);
}

function removerLinha(idx) {
  const el = document.getElementById('linha-' + idx);
  if (el) el.remove();
  // Mostrar mensagem vazio se não há linhas
  const linhas = document.querySelectorAll('.linha-item');
  if (linhas.length === 0) document.getElementById('msgVazio').style.display = '';
  atualizarResumoGeral();
}

function atualizarResumo(idx) { atualizarResumoGeral(); }

function atualizarResumoGeral() {
  const linhas = document.querySelectorAll('.linha-item');
  const lista  = document.getElementById('resumoLista');
  const vazio  = document.getElementById('resumoVazio');
  const total  = document.getElementById('resumoTotal');
  lista.innerHTML = '';
  let n = 0;
  linhas.forEach(div => {
    const sel = div.querySelector('select');
    const qtd = div.querySelector('input[type="number"]');
    if (!sel || !sel.value) return;
    const opt = sel.options[sel.selectedIndex];
    const nome = opt ? opt.dataset.nome || opt.text : '?';
    const unid = opt ? opt.dataset.unidade || '' : '';
    const q = qtd ? parseFloat(qtd.value) || 0 : 0;
    if (!sel.value || q <= 0) return;
    n++;
    const li = document.createElement('li');
    li.className = 'list-group-item px-2 py-2 d-flex justify-content-between align-items-start';
    li.innerHTML = `<span class="small fw-semibold text-truncate me-2" style="max-width:65%">${nome}</span>
                    <span class="badge rounded-pill" style="background:var(--accent-light);color:var(--accent);flex-shrink:0">
                      ${q} ${unid}
                    </span>`;
    lista.appendChild(li);
  });
  if (n > 0) { lista.style.display = ''; vazio.style.display = 'none'; }
  else        { lista.style.display = 'none'; vazio.style.display = ''; }
  total.textContent = n;
}

// Autocomplete colaboradores
let _acTimer = null;
const _inputColab = document.getElementById('inputColaborador');
const _acList     = document.getElementById('autocompleteList');

if (_inputColab) {
  _inputColab.addEventListener('input', function() {
    clearTimeout(_acTimer);
    const q = this.value.trim();
    if (q.length < 1) { _acList.style.display = 'none'; return; }
    _acTimer = setTimeout(() => fetchColaboradores(q), 250);
  });
  _inputColab.addEventListener('blur', function() {
    setTimeout(() => { _acList.style.display = 'none'; }, 180);
  });
}

function fetchColaboradores(q) {
  fetch('/api/colaboradores?q=' + encodeURIComponent(q))
    .then(r => r.json())
    .then(data => {
      if (!data.length) { _acList.style.display = 'none'; return; }
      _acList.innerHTML = '';
      data.slice(0, 8).forEach(item => {
        const d = document.createElement('div');
        d.className = 'px-3 py-2 d-flex align-items-center gap-2';
        d.style.cssText = 'cursor:pointer;font-size:0.88rem;border-bottom:1px solid var(--border)';
        d.innerHTML = `<i class="bi bi-person-fill text-muted" style="font-size:0.75rem"></i>
                       <span>${item.nome}</span>`;
        d.onmousedown = () => {
          _inputColab.value = item.nome;
          _acList.style.display = 'none';
        };
        d.onmouseover = () => d.style.background = 'var(--accent-light)';
        d.onmouseout  = () => d.style.background = '';
        _acList.appendChild(d);
      });
      _acList.style.display = '';
    })
    .catch(() => { _acList.style.display = 'none'; });
}
</script>
