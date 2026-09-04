<?php $isNew=!isset($it)||!$it;
$voltarUrl = $isNew ? ("/almoxarifado/".($almPresel??0)) : "/almoxarifado/{$it['almoxarifado_id']}"; ?>
<div class="mb-3">
  <a href="<?= $voltarUrl ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
</div>
<?php $action=$isNew?'/item/novo':"/item/{$it['id']}/editar"; ?>
<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header"><h6 class="mb-0"><?= $isNew?'Novo Item':'Editar Item' ?></h6></div>
      <div class="card-body">
        <form method="POST" action="<?= $action ?>">
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label fw-semibold">Nome *</label>
              <input type="text" name="nome" class="form-control" required value="<?= h($it['nome']??'') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Código *</label>
              <input type="text" name="codigo" class="form-control" required value="<?= h($it['codigo']??'') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Unidade *</label>
              <input type="text" name="unidade" class="form-control" required value="<?= h($it['unidade']??'un') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Quantidade</label>
              <input type="number" name="quantidade" class="form-control" step="0.01" value="<?= $it['quantidade']??0 ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Estoque Mínimo</label>
              <input type="number" name="estoque_minimo" class="form-control" step="0.01" value="<?= $it['estoque_minimo']??0 ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Almoxarifado *</label>
              <select name="almoxarifado_id" class="form-select" required>
                <option value="">Selecione...</option>
                <?php foreach($almoxarifados as $a): ?>
                <option value="<?= $a['id'] ?>" <?= ($it['almoxarifado_id']??$almPresel??0)==$a['id']?'selected':'' ?>><?= h($a['nome']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Categoria</label>
              <select name="categoria" class="form-select">
                <?php foreach(['geral','epi','maquinario','eletrica','hidraulica','gas'] as $cat): ?>
                <option value="<?= $cat ?>" <?= ($it['categoria']??'geral')===$cat?'selected':'' ?>><?= categoria_label($cat) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">CA (EPI)</label>
              <input type="text" name="ca" class="form-control" value="<?= h($it['ca']??'') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">
                <i class="bi bi-currency-dollar me-1 text-success"></i>Valor Unitário (R$)
              </label>
              <input type="number" name="valor_unitario" class="form-control" step="0.01" min="0"
                     placeholder="0,00"
                     value="<?= $it['valor_unitario']??'' ?>">
              <div class="form-text">Opcional — usado no relatório de valor em estoque</div>
            </div>
          </div>
          <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><?= $isNew?'Cadastrar':'Salvar' ?></button>
            <?php if(!$isNew): ?><a href="/almoxarifado/<?= $it['almoxarifado_id'] ?>" class="btn btn-outline-secondary">Cancelar</a><?php else: ?><a href="/" class="btn btn-outline-secondary">Cancelar</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
