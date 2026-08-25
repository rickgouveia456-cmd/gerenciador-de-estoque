<?php $isNew = !isset($alm) || !$alm; $action = $isNew ? '/almoxarifado/novo' : "/almoxarifado/{$alm['id']}/editar"; ?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><h6 class="mb-0"><?= $isNew ? 'Novo Almoxarifado' : 'Editar Almoxarifado' ?></h6></div>
      <div class="card-body">
        <form method="POST" action="<?= $action ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label fw-semibold">Nome *</label>
            <input type="text" name="nome" class="form-control" required value="<?= h($alm['nome'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Descrição</label>
            <input type="text" name="descricao" class="form-control" value="<?= h($alm['descricao'] ?? '') ?>">
          </div>
          <div class="row g-2">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Obra</label>
              <input type="text" name="obra" class="form-control" value="<?= h($alm['obra'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Cidade</label>
              <input type="text" name="cidade" class="form-control" value="<?= h($alm['cidade'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Região</label>
              <input type="text" name="regiao" class="form-control"
                     placeholder="Ex: Norte, Sul, Zona Leste..."
                     value="<?= h($alm['regiao'] ?? '') ?>">
            </div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><?= $isNew ? 'Criar' : 'Salvar' ?></button>
            <a href="/" class="btn btn-outline-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
