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
<form id="_csrf2"><?= csrf_field() ?></form>
<script>
function getToken(){return document.querySelector('#_csrf2 [name=csrf_token]')?.value||'';}
function usarEPI(id){const r=prompt('Colaborador:');if(!r)return;fetch(`/epis/${id}/usar`,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`responsavel=${encodeURIComponent(r)}&csrf_token=${encodeURIComponent(getToken())}`}).then(()=>location.reload());}
function devolverEPI(id){if(!confirm('Confirmar devolução?'))return;fetch(`/epis/${id}/devolver`,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`csrf_token=${encodeURIComponent(getToken())}`}).then(()=>location.reload());}
</script>
