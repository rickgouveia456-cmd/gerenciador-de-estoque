<div class="mb-3"><a href="/catalogo" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a></div>
<?php $isNew=!isset($ins)||!$ins;$action=$isNew?'/catalogo/novo':"/catalogo/{$ins['id']}/editar"; ?>
<div class="row justify-content-center"><div class="col-md-8">
  <div class="card"><div class="card-header"><h6 class="mb-0"><?= $isNew?'Novo Insumo':'Editar Insumo' ?></h6></div>
  <div class="card-body"><form method="POST" action="<?= $action ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-8"><label class="form-label fw-semibold">Nome *</label><input type="text" name="nome" class="form-control" required value="<?= h($ins['nome']??'') ?>"></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Código Ref.</label><input type="text" name="codigo_ref" class="form-control" value="<?= h($ins['codigo_ref']??'') ?>"></div>
      <div class="col-md-3"><label class="form-label fw-semibold">Unidade *</label><input type="text" name="unidade" class="form-control" required value="<?= h($ins['unidade']??'un') ?>"></div>
      <div class="col-md-3"><label class="form-label fw-semibold">Categoria</label><select name="categoria" class="form-select"><?php foreach($categorias as $c): ?><option value="<?= $c ?>" <?= ($ins['categoria']??'geral')===$c?'selected':'' ?>><?= categoria_label($c) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="form-label fw-semibold">CA (EPI)</label><input type="text" name="ca" class="form-control" value="<?= h($ins['ca']??'') ?>"></div>
      <div class="col-md-3"><label class="form-label fw-semibold">Valor Unitário</label><input type="text" name="valor_unitario" class="form-control" value="<?= $ins['valor_unitario']??'' ?>" placeholder="0.00"></div>
      <div class="col-12"><label class="form-label fw-semibold">Descrição</label><textarea name="descricao" class="form-control" rows="2"><?= h($ins['descricao']??'') ?></textarea></div>
    </div>
    <div class="d-flex gap-2 mt-3"><button type="submit" class="btn btn-primary"><?= $isNew?'Adicionar':'Salvar' ?></button><a href="/catalogo" class="btn btn-outline-secondary">Cancelar</a></div>
  </form></div></div>
</div></div>
