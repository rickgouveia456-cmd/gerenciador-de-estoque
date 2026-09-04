<?php /* views/kits/form.php */
$isNew=!isset($kit)||!$kit;
$action=$isNew?"/almoxarifado/{$almId}/kits/novo":"/almoxarifado/{$almId}/kits/{$kit["id"]}/editar";
?>
<div class="d-flex align-items-center gap-2 mb-4">
  <a href="/almoxarifado/<?= $almId ?>/kits" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
  <h5 class="fw-bold mb-0"><?= $isNew?"Novo Kit":"Editar Kit — ".h($kit["nome"]) ?></h5>
</div>
<form method="POST" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="card mb-3">
    <div class="card-header fw-bold text-white" style="background:var(--accent)"><i class="bi bi-box2-heart me-2"></i>Dados do Kit</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-semibold">Nome *</label><input type="text" name="nome" class="form-control" required value="<?= h($kit["nome"]??"") ?>"></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Descrição</label><input type="text" name="descricao" class="form-control" value="<?= h($kit["descricao"]??"") ?>" placeholder="Ex: Kit para armador..."></div>
      </div>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header fw-bold text-white d-flex justify-content-between align-items-center" style="background:var(--accent)">
      <span><i class="bi bi-list-ul me-2"></i>Itens do Kit</span>
      <button type="button" class="btn btn-sm btn-warning" onclick="addKitLine()"><i class="bi bi-plus-lg me-1"></i>Adicionar Item</button>
    </div>
    <div class="card-body p-2">
      <table class="table table-sm mb-0"><thead><tr><th>Item</th><th style="width:110px">Quantidade</th><th style="width:40px"></th></tr></thead>
      <tbody id="kitLinhas">
        <?php if(!$isNew&&!empty($kit["_itens"])): foreach($kit["_itens"] as $idx=>$ki): ?>
        <tr>
          <td><select name="item_id_<?= $idx ?>" class="form-select form-select-sm" required>
            <?php foreach($itens as $it): ?><option value="<?= $it["id"] ?>" <?= $it["id"]==$ki["item_id"]?"selected":"" ?>><?= h($it["nome"]) ?> (<?= fmt_qtd((float)$it["quantidade"]) ?> <?= h($it["unidade"]) ?>)</option><?php endforeach; ?>
          </select></td>
          <td><input type="number" name="qtd_<?= $idx ?>" class="form-control form-control-sm" value="<?= $ki["quantidade"] ?>" min="0.01" step="0.01"></td>
          <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest(\"tr\").remove()"><i class="bi bi-trash"></i></button></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody></table>
    </div>
  </div>
  <div class="d-flex gap-2 justify-content-end">
    <a href="/almoxarifado/<?= $almId ?>/kits" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i><?= $isNew?"Criar Kit":"Salvar" ?></button>
  </div>
</form>
<script>
const kitItens = <?= json_encode(array_map(fn($i)=>["id"=>$i["id"],"nome"=>$i["nome"],"unidade"=>$i["unidade"],"quantidade"=>$i["quantidade"]], $itens), JSON_UNESCAPED_UNICODE) ?>;
let kitIdx = <?= $isNew?0:count($kit["_itens"]??[]) ?>;
function addKitLine(){
  const opts = kitItens.map(i=>`<option value="${i.id}">${i.nome} (${i.quantidade} ${i.unidade})</option>`).join("");
  const tr=document.createElement("tr");
  tr.innerHTML=`<td><select name="item_id_${kitIdx}" class="form-select form-select-sm" required><option value="">Selecione...</option>${opts}</select></td><td><input type="number" name="qtd_${kitIdx}" class="form-control form-control-sm" value="1" min="0.01" step="0.01"></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>`;
  document.getElementById("kitLinhas").appendChild(tr); kitIdx++;
}
</script>