<?php /* views/ferramentas/index.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h5 class="fw-bold mb-0"><i class="bi bi-tools me-2"></i>Ferramentas</h5>
  <?php if(in_array($u['perfil'],['admin','almoxarife'])): ?>
  <div class="d-flex gap-2">
    <form method="GET" class="d-flex gap-2">
      <select name="alm" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">Todos</option>
        <?php foreach($almoxarifados as $a): ?><option value="<?= $a['id'] ?>" <?= $almId==$a['id']?'selected':'' ?>><?= h($a['nome']) ?></option><?php endforeach; ?>
      </select>
    </form>
    <a href="/ferramentas/nova?alm=<?= $almId ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus me-1"></i>Nova</a>
  </div>
  <?php endif; ?>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>ID</th><th>Nome</th><th>Almoxarifado</th><th>Empresa</th><th class="text-center">Status</th><th class="text-center">Responsável</th><?php if(in_array($u['perfil'],['admin','almoxarife'])): ?><th class="text-center">Ações</th><?php endif; ?></tr></thead>
      <tbody>
      <?php if(empty($ferramentas)): ?><tr><td colspan="7" class="text-center text-muted py-3">Nenhuma ferramenta.</td></tr><?php endif; ?>
      <?php foreach($ferramentas as $f):
        $cls=['disponivel'=>'success','em_uso'=>'warning','manutencao'=>'danger'][$f['status']]??'secondary';
        $lbl=['disponivel'=>'Disponível','em_uso'=>'Em Uso','manutencao'=>'Manutenção'][$f['status']]??$f['status'];
      ?>
      <tr>
        <td class="font-monospace small"><?= h($f['identificacao']) ?></td>
        <td class="fw-semibold"><?= h($f['nome']) ?></td>
        <td class="text-muted small"><?= h($f['alm_nome']) ?></td>
        <td class="text-muted small"><?= h($f['empresa']??'—') ?></td>
        <td class="text-center"><span class="badge bg-<?= $cls ?>"><?= $lbl ?></span></td>
        <td class="text-center text-muted small"><?= h($f['responsavel_atual']??'—') ?></td>
        <?php if(in_array($u['perfil'],['admin','almoxarife'])): ?>
        <td class="text-center">
          <?php if($f['status']==='disponivel'): ?>
          <button class="btn btn-sm btn-warning" onclick="usarFerramenta(<?= $f['id'] ?>)"><i class="bi bi-arrow-right-circle"></i></button>
          <?php elseif($f['status']==='em_uso'): ?>
          <button class="btn btn-sm btn-success" onclick="devolverFerramenta(<?= $f['id'] ?>,this)"><i class="bi bi-arrow-return-left"></i></button>
          <button class="btn btn-sm btn-danger ms-1" onclick="manutencaoFerramenta(<?= $f['id'] ?>)"><i class="bi bi-wrench"></i></button>
          <?php else: ?>
          <button class="btn btn-sm btn-success" onclick="devolverFerramenta(<?= $f['id'] ?>,this)"><i class="bi bi-check-circle"></i></button>
          <?php endif; ?>
          <a href="/ferramentas/<?= $f['id'] ?>/editar" class="btn btn-sm btn-outline-secondary ms-1"><i class="bi bi-pencil"></i></a>
        </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal usar ferramenta -->
<div class="modal fade" id="modalUsarFerr" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--accent)">
        <h6 class="modal-title text-white fw-bold"><i class="bi bi-person-plus me-2"></i>Registrar Uso</h6>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="ferrIdUsar">
        <label class="form-label fw-semibold">Colaborador que vai usar</label>
        <div class="position-relative">
          <input type="text" id="inputColabFerr" class="form-control" placeholder="Digite o nome..." autocomplete="off">
          <div id="acListFerr" class="position-absolute w-100 bg-white border rounded shadow-sm" style="top:100%;z-index:300;max-height:200px;overflow-y:auto;display:none"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="confirmarUsarFerr()"><i class="bi bi-check me-1"></i>Confirmar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal manutenção -->
<div class="modal fade" id="modalManutFerr" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h6 class="modal-title fw-bold"><i class="bi bi-wrench me-2"></i>Registrar Manutenção</h6>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="ferrIdManut">
        <label class="form-label fw-semibold">Motivo da manutenção</label>
        <textarea id="inputMotivoManut" class="form-control" rows="3" placeholder="Descreva o problema..."></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-warning" onclick="confirmarManutFerr()"><i class="bi bi-check me-1"></i>Confirmar</button>
      </div>
    </div>
  </div>
</div>

<form id="_csrfCarrier"><?= csrf_field() ?></form>

<script>
function getFerrToken() {
  return document.querySelector('#_csrfCarrier [name=csrf_token]')?.value || '';
}

function usarFerramenta(id) {
  document.getElementById('ferrIdUsar').value = id;
  document.getElementById('inputColabFerr').value = '';
  document.getElementById('acListFerr').style.display = 'none';
  new bootstrap.Modal(document.getElementById('modalUsarFerr')).show();
}

// Autocomplete colaboradores
document.getElementById('inputColabFerr')?.addEventListener('input', function() {
  const q = this.value.trim();
  const list = document.getElementById('acListFerr');
  if (q.length < 1) { list.style.display = 'none'; return; }
  fetch('/api/colaboradores?q=' + encodeURIComponent(q))
    .then(r => r.json())
    .then(data => {
      list.innerHTML = '';
      if (!data.length) { list.style.display = 'none'; return; }
      data.slice(0, 8).forEach(c => {
        const d = document.createElement('div');
        d.className = 'px-3 py-2';
        d.style.cssText = 'cursor:pointer;font-size:0.88rem;border-bottom:1px solid #eee';
        d.textContent = c.nome + (c.funcao ? ' — ' + c.funcao : '');
        d.onmousedown = () => {
          document.getElementById('inputColabFerr').value = c.nome;
          list.style.display = 'none';
        };
        d.onmouseover = () => d.style.background = 'var(--accent-light)';
        d.onmouseout  = () => d.style.background = '';
        list.appendChild(d);
      });
      list.style.display = '';
    })
    .catch(() => { list.style.display = 'none'; });
});

// Fechar autocomplete ao perder foco
document.getElementById('inputColabFerr')?.addEventListener('blur', function() {
  setTimeout(() => { document.getElementById('acListFerr').style.display = 'none'; }, 150);
});

function confirmarUsarFerr() {
  const id   = document.getElementById('ferrIdUsar').value;
  const resp = document.getElementById('inputColabFerr').value.trim();
  if (!resp) { alert('Informe o colaborador.'); return; }
  fetch('/ferramentas/' + id + '/usar', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'responsavel=' + encodeURIComponent(resp) + '&csrf_token=' + encodeURIComponent(getFerrToken())
  }).then(() => {
    bootstrap.Modal.getInstance(document.getElementById('modalUsarFerr')).hide();
    location.reload();
  });
}

function devolverFerramenta(id, btn) {
  if (!confirm('Confirmar devolução?')) return;
  fetch('/ferramentas/' + id + '/devolver', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'csrf_token=' + encodeURIComponent(getFerrToken())
  }).then(() => location.reload());
}

function manutencaoFerramenta(id) {
  document.getElementById('ferrIdManut').value = id;
  document.getElementById('inputMotivoManut').value = '';
  new bootstrap.Modal(document.getElementById('modalManutFerr')).show();
}

function confirmarManutFerr() {
  const id     = document.getElementById('ferrIdManut').value;
  const motivo = document.getElementById('inputMotivoManut').value.trim();
  fetch('/ferramentas/' + id + '/manutencao', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'motivo=' + encodeURIComponent(motivo) + '&csrf_token=' + encodeURIComponent(getFerrToken())
  }).then(() => {
    bootstrap.Modal.getInstance(document.getElementById('modalManutFerr')).hide();
    location.reload();
  });
}
</script>
