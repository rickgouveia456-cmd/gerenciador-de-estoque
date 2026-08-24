<?php /* views/requisicoes/nova.php */ ?>
<div class="card"><div class="card-header"><h6 class="mb-0">Nova Requisição</h6></div>
<div class="card-body">
  <form method="POST" action="/requisicoes/nova">
    <?= csrf_field() ?>
    <div class="row g-3 mb-3">
      <div class="col-md-4"><label class="form-label fw-semibold">Colaborador *</label><input type="text" name="colaborador" class="form-control" required></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Almoxarifado *</label><select name="almoxarifado_id" id="selAlm2" class="form-select" required><option value="">Selecione...</option><?php foreach($almoxarifados as $a): ?><option value="<?= $a['id'] ?>"><?= h($a['nome']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Observação</label><input type="text" name="observacao" class="form-control"></div>
    </div>
    <div class="table-responsive mb-3"><table class="table table-sm"><thead><tr><th>Item</th><th style="width:120px">Qtd</th><th style="width:40px"></th></tr></thead><tbody id="linhasReq2"></tbody></table></div>
    <div class="d-flex gap-2"><button type="button" class="btn btn-outline-secondary btn-sm" id="btnAddReq"><i class="bi bi-plus me-1"></i>Adicionar</button><button type="submit" class="btn btn-primary btn-sm">Registrar</button></div>
  </form>
</div></div>
<script>
const itensReq=<?= json_encode($itensJson,JSON_UNESCAPED_UNICODE) ?>;
let idxR=0;
document.getElementById('btnAddReq')?.addEventListener('click',()=>{
  const almId=document.getElementById('selAlm2')?.value;
  if(!almId){alert('Selecione o almoxarifado.');return;}
  const itens=itensReq[almId]||[];
  const opts=itens.map(i=>`<option value="${i.id}">${i.nome} (${i.quantidade} ${i.unidade})</option>`).join('');
  const tr=document.createElement('tr');
  tr.innerHTML=`<td><select name="item_id_${idxR}" class="form-select form-select-sm" required><option value="">Selecione...</option>${opts}</select></td><td><input type="number" name="quantidade_${idxR}" class="form-control form-control-sm" step="0.01" min="0.01" required></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>`;
  document.getElementById('linhasReq2').appendChild(tr);idxR++;
});
</script>
