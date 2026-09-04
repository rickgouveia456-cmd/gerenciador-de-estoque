<?php /* views/catalogo/importar.php */ ?>
<div class="d-flex align-items-center gap-2 mb-3">
  <a href="/catalogo" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
  <h5 class="fw-bold mb-0">Importar Catálogo de Insumos</h5>
</div>

<div class="row justify-content-center">
  <div class="col-md-7">
    <div class="card">
      <div class="card-body">
        <div class="alert alert-info small py-2 mb-3">
          <i class="bi bi-info-circle me-1"></i>
          O arquivo deve ser <strong>CSV</strong> com colunas:
          <code>Nome; Código Ref; Unidade; Categoria; CA; Valor Unitário</code><br>
          Separador: <strong>;</strong> ou <strong>,</strong> — UTF-8 ou Excel BR (Windows-1252).<br>
          <a href="/catalogo/modelo_csv" class="fw-semibold">Baixar modelo CSV →</a>
        </div>

        <form method="POST" action="/catalogo/importar" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="mb-4">
            <label class="form-label fw-semibold">Arquivo CSV / Excel exportado como CSV</label>
            <input type="file" name="arquivo" class="form-control" accept=".csv,.txt,.xls,.xlsx" required>
            <div class="form-text">Arquivos .xlsx não são suportados diretamente — exporte como CSV pelo Excel antes.</div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Importar</button>
            <a href="/catalogo" class="btn btn-outline-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabela de exemplo -->
    <div class="card mt-3">
      <div class="card-header fw-semibold small">Exemplo de formato</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0 small">
          <thead class="table-light"><tr><th>Nome</th><th>Código Ref</th><th>Unidade</th><th>Categoria</th><th>CA</th><th>Valor Unit.</th></tr></thead>
          <tbody>
            <tr><td>Capacete Amarelo</td><td>8707</td><td>UND</td><td>epi</td><td></td><td>45.90</td></tr>
            <tr><td>Luva Flextactil</td><td>7794</td><td>un</td><td>epi</td><td>CA-12345</td><td>8.50</td></tr>
            <tr><td>FITA CREPE 50MMX50M</td><td>4828</td><td>un</td><td>geral</td><td></td><td>12.00</td></tr>
            <tr><td>Eletroduto Laranja 20mm</td><td>8426</td><td>m</td><td>eletrica</td><td></td><td>3.20</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>