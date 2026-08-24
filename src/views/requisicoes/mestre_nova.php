<?php /* views/requisicoes/mestre_nova.php */ ?>
<div class="card"><div class="card-header"><h6 class="mb-0"><i class="bi bi-clipboard-plus me-2"></i>Nova Requisição de Obra</h6></div>
<div class="card-body">
  <form method="POST" action="/requisicoes/mestre/nova">
    <?= csrf_field() ?>
    <div class="row g-3 mb-3">
      <div class="col-md-4"><label class="form-label fw-semibold">Colaborador que vai buscar *</label><input type="text" name="colaborador" class="form-control" required placeholder="Nome completo..."></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Almoxarifado *</label><select name="almoxarifado_id" id="selAlmMestre" class="form-select" required><option value="">Selecione...</option><?php foreach($almoxarifados as $a): ?><option value="<?= $a['id'] ?>"><?= h($a['nome']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Observação</label><input type="text" name="observacao" class="form-control" placeholder="Observação geral..."></div>
    </div>
    <div class="table-responsive mb-3"><table class="table table-sm"><thead><tr><th>Item</th><th style="width:120px">Qtd</th><th>Obs.</th><th style="width:40px"></th></tr></thead><tbody id="linhasMestre"></tbody></table></div>
    <div class="d-flex gap-2"><button type="button" class="btn btn-outline-secondary btn-sm" id="btnAddMestre"><i class="bi bi-plus me-1"></i>Adicionar item</button><button type="submit" class="btn btn-primary">Enviar Requisição</button></div>
  </form>
</div></div>
<script>
const itensMestre=<?= json_encode($itensJson,JSON_UNESCAPED_UNICODE) ?>;
let idxM=0;
document.getElementById('btnAddMestre')?.addEventListener('click',()=>{
  const almId=document.getElementById('selAlmMestre')?.value;
  if(!almId){alert('Selecione o almoxarifado.');return;}
  const itens=itensMestre[almId]||[];
  const opts=itens.map(i=>`<option value="${i.id}">${i.nome} (${i.quantidade} ${i.unidade})</option>`).join('');
  const tr=document.createElement('tr');
  tr.innerHTML=`<td><select name="item_id_${idxM}" class="form-select form-select-sm" required><option value="">Selecione...</option>${opts}</select></td><td><input type="number" name="quantidade_${idxM}" class="form-control form-control-sm" step="0.01" min="0.01" required></td><td><input type="text" name="observacao_${idxM}" class="form-control form-control-sm" placeholder="Obs..."></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>`;
  document.getElementById('linhasMestre').appendChild(tr);idxM++;
});
</script>
