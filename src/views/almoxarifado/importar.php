<div class="row justify-content-center"><div class="col-md-7">
  <div class="card"><div class="card-header"><h6 class="mb-0">Importar Itens — <?= h($alm['nome']) ?></h6></div>
  <div class="card-body">
    <p class="text-muted small">Envie um arquivo CSV com colunas: <code>Codigo;Nome;Categoria;Unidade;Quantidade;Estoque Minimo</code></p>
    <form method="POST" action="/almoxarifado/<?= $id ?>/importar" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label fw-semibold">Arquivo CSV *</label><input type="file" name="arquivo" class="form-control" accept=".csv" required></div>
      <div class="d-flex gap-2"><button type="submit" class="btn btn-primary">Importar</button><a href="/almoxarifado/<?= $id ?>/modelo_excel" class="btn btn-outline-secondary">Baixar Modelo</a><a href="/almoxarifado/<?= $id ?>" class="btn btn-outline-secondary">Cancelar</a></div>
    </form>
  </div></div>
</div></div>
