<?php /* views/almoxarifado/importar.php */ ?>
<div class="d-flex align-items-center gap-2 mb-3">
  <a href="/almoxarifado/<?= $id ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Voltar
  </a>
  <h5 class="fw-bold mb-0">Importar Itens — <?= h($alm["nome"]) ?></h5>
</div>

<div class="row justify-content-center">
  <div class="col-md-7">
    <div class="card">
      <div class="card-body">
        <div class="alert alert-info py-2 small mb-3">
          <i class="bi bi-info-circle me-1"></i>
          O arquivo CSV deve ter colunas: <code>Codigo;Nome;Categoria;Unidade;Quantidade;Estoque Minimo</code><br>
          Separador pode ser <strong>;</strong> ou <strong>,</strong> — UTF-8 ou Windows-1252 (Excel BR).
        </div>
        <form method="POST" action="/almoxarifado/<?= $id ?>/importar" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label fw-semibold">Arquivo CSV *</label>
            <input type="file" name="arquivo" class="form-control" accept=".csv,.txt" required>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Importar</button>
            <a href="/almoxarifado/<?= $id ?>/modelo_excel" class="btn btn-outline-success"><i class="bi bi-download me-1"></i>Baixar Modelo CSV</a>
            <a href="/almoxarifado/<?= $id ?>" class="btn btn-outline-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>