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
<script>
function usarFerramenta(id){
  const resp=prompt('Nome do colaborador:');
  if(!resp)return;
  fetch(`/ferramentas/${id}/usar`,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body:`responsavel=${encodeURIComponent(resp)}&csrf_token=${encodeURIComponent(document.querySelector('[name=csrf_token]')?.value||'')}`}).then(()=>location.reload());
}
function devolverFerramenta(id,btn){
  if(!confirm('Confirmar devolução?'))return;
  fetch(`/ferramentas/${id}/devolver`,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`csrf_token=${encodeURIComponent(document.querySelector('[name=csrf_token]')?.value||'')}`}).then(()=>location.reload());
}
function manutencaoFerramenta(id){
  const motivo=prompt('Motivo da manutenção:');
  if(motivo===null)return;
  fetch(`/ferramentas/${id}/manutencao`,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`motivo=${encodeURIComponent(motivo)}&csrf_token=${encodeURIComponent(document.querySelector('[name=csrf_token]')?.value||'')}`}).then(()=>location.reload());
}
</script>
<form id="_csrfCarrier"><?= csrf_field() ?></form>
