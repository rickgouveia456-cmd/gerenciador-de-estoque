<?php $isNew=!isset($f)||!$f; $action=$isNew?"/ferramentas/nova?alm={$alm['id']}":"/ferramentas/{$f['id']}/editar"; ?>
<div class="row justify-content-center">
  <div class="col-md-7">
    <div class="card">
      <div class="card-header"><h6 class="mb-0"><?= $isNew?'Nova Ferramenta':'Editar Ferramenta' ?></h6></div>
      <div class="card-body">
        <form method="POST" action="<?= $action ?>">
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label fw-semibold">ID/Patrimônio *</label><input type="text" name="identificacao" class="form-control" required value="<?= h($f['identificacao']??'') ?>"></div>
            <div class="col-md-8"><label class="form-label fw-semibold">Nome *</label><input type="text" name="nome" class="form-control" required value="<?= h($f['nome']??'') ?>"></div>
            <div class="col-md-6"><label class="form-label fw-semibold">Empresa</label><input type="text" name="empresa" class="form-control" value="<?= h($f['empresa']??'') ?>"></div>
            <div class="col-md-6"><label class="form-label fw-semibold">Local</label><input type="text" name="local" class="form-control" value="<?= h($f['local']??'') ?>"></div>
            <div class="col-12"><label class="form-label fw-semibold">Observação</label><input type="text" name="observacao" class="form-control" value="<?= h($f['observacao']??'') ?>"></div>
          </div>
          <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><?= $isNew?'Cadastrar':'Salvar' ?></button>
            <a href="/ferramentas<?= $alm?'?alm='.$alm['id']:'' ?>" class="btn btn-outline-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
