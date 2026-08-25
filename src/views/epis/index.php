<?php /* views/epis/index.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h5 class="fw-bold mb-0"><i class="bi bi-shield-check me-2"></i>EPIs</h5>
  <div class="d-flex gap-2">
    <form method="GET" class="d-flex gap-2">
      <select name="alm" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">Todos</option>
        <?php foreach($almoxarifados as $a): ?><option value="<?= $a['id'] ?>" <?= $almId==$a['id']?'selected':'' ?>><?= h($a['nome']) ?></option><?php endforeach; ?>
      </select>
    </form>
    <?php if(in_array($u['perfil'],['admin','almoxarife'])): ?>
    <a href="/epis/novo?alm=<?= $almId ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus me-1"></i>Novo</a>
    <?php endif; ?>
  </div>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>ID</th><th>Nome</th><th>Tamanho</th><th>Almoxarifado</th><th class="text-center">Qtd</th><th class="text-center">Status</th><th class="text-center">Responsável</th><?php if(in_array($u['perfil'],['admin','almoxarife'])): ?><th class="text-center">Ações</th><?php endif; ?></tr></thead>
      <tbody>
      <?php if(empty($epis)): ?><tr><td colspan="8" class="text-center text-muted py-3">Nenhum EPI.</td></tr><?php endif; ?>
      <?php foreach($epis as $e): $cls=['disponivel'=>'success','em_uso'=>'warning','manutencao'=>'danger'][$e['status']]??'secondary'; ?>
      <tr>
        <td class="font-monospace small"><?= h($e['identificacao']) ?></td>
        <td class="fw-semibold"><?= h($e['nome']) ?><?php if($e['tamanho']): ?> <span class="badge bg-secondary"><?= h($e['tamanho']) ?></span><?php endif; ?></td>
        <td><?= h($e['tamanho']??'—') ?></td>
        <td class="text-muted small"><?= h($e['alm_nome']) ?></td>
        <td class="text-center"><?= $e['quantidade'] ?></td>
        <td class="text-center"><span class="badge bg-<?= $cls ?>"><?= ['disponivel'=>'Disponível','em_uso'=>'Em Uso','manutencao'=>'Manutenção'][$e['status']]??$e['status'] ?></span></td>
        <td class="text-center text-muted small"><?= h($e['responsavel_atual']??'—') ?></td>
        <?php if(in_array($u['perfil'],['admin','almoxarife'])): ?>
        <td class="text-center">
          <?php if($e['status']==='disponivel'): ?>
          <button class="btn btn-sm btn-warning" onclick="usarEPI(<?= $e['id'] ?>)"><i class="bi bi-person-plus"></i></button>
          <?php else: ?>
          <button class="btn btn-sm btn-success" onclick="devolverEPI(<?= $e['id'] ?>)"><i class="bi bi-arrow-return-left"></i></button>
          <?php endif; ?>
          <a href="/epis/<?= $e['id'] ?>/editar" class="btn btn-sm btn-outline-secondary ms-1"><i class="bi bi-pencil"></i></a>
        </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal usar EPI -->
<div class="modal fade" id="modalUsarEPI" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--accent)">
        <h6 class="modal-title text-white fw-bold"><i class="bi bi-person-plus me-2"></i>Registrar Uso de EPI</h6>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="epiIdUsar">
        <label class="form-label fw-semibold">Colaborador que vai usar</label>
        <div class="position-relative">
          <input type="text" id="inputColabEPI" class="form-control" placeholder="Digite o nome..." autocomplete="off">
          <div id="acListEPI" class="position-absolute w-100 bg-white border rounded shadow-sm" style="top:100%;z-index:300;max-height:200px;overflow-y:auto;display:none"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="confirmarUsarEPI()"><i class="bi bi-check me-1"></i>Confirmar</button>
      </div>
    </div>
  </div>
</div>

<form id="_csrf2"><?= csrf_field() ?></form>

<script>
function getEpiToken() {
  return document.querySelector('#_csrf2 [name=csrf_token]')?.value || '';
}

function usarEPI(id) {
  document.getElementById('epiIdUsar').value = id;
  document.getElementById('inputColabEPI').value = '';
  document.getElementById('acListEPI').style.display = 'none';
  new bootstrap.Modal(document.getElementById('modalUsarEPI')).show();
}

// Autocomplete colaboradores no modal EPI
document.getElementById('inputColabEPI')?.addEventListener('input', function() {
  const q = this.value.trim();
  const list = document.getElementById('acListEPI');
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
          document.getElementById('inputColabEPI').value = c.nome;
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

document.getElementById('inputColabEPI')?.addEventListener('blur', function() {
  setTimeout(() => { document.getElementById('acListEPI').style.display = 'none'; }, 150);
});

function confirmarUsarEPI() {
  const id   = document.getElementById('epiIdUsar').value;
  const resp = document.getElementById('inputColabEPI').value.trim();
  if (!resp) { alert('Informe o colaborador.'); return; }
  fetch('/epis/' + id + '/usar', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'responsavel=' + encodeURIComponent(resp) + '&csrf_token=' + encodeURIComponent(getEpiToken())
  }).then(() => {
    bootstrap.Modal.getInstance(document.getElementById('modalUsarEPI')).hide();
    location.reload();
  });
}

function devolverEPI(id) {
  if (!confirm('Confirmar devolução?')) return;
  fetch('/epis/' + id + '/devolver', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'csrf_token=' + encodeURIComponent(getEpiToken())
  }).then(() => location.reload());
}
</script>
