<?php /* views/almoxarifado/importar.php */ ?>
<div class="d-flex align-items-center gap-2 mb-3">
  <a href="/almoxarifado/<?= $id ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Voltar
  </a>
  <h5 class="fw-bold mb-0">Importar Itens — <?= h($alm["nome"]) ?></h5>
</div>

<div class="row justify-content-center">
  <div class="col-md-9">
    <div class="card mb-3">
      <div class="card-body">
        <div class="alert alert-info small py-2 mb-3">
          <i class="bi bi-info-circle me-1"></i>
          <strong>Formato aceito:</strong> CSV com separador <code>;</code> ou <code>,</code> — UTF-8 ou Windows-1252 (Excel BR).<br>
          <strong>Colunas:</strong> <code>Codigo ; Nome ; Categoria ; Unidade ; Quantidade ; Estoque Minimo ; CA ; Valor Unitario</code><br>
          <strong>Categorias válidas:</strong> <code>geral</code> · <code>epi</code> · <code>eletrica</code> · <code>hidraulica</code> · <code>gas</code> · <code>maquinario</code>
        </div>
        <form method="POST" action="/almoxarifado/<?= $id ?>/importar" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label fw-semibold">Arquivo CSV <span class="text-danger">*</span></label>
            <input type="file" name="arquivo" class="form-control" accept=".csv,.txt" required>
            <div class="form-text">Arquivos .xlsx: abra no Excel → Arquivo → Salvar Como → <strong>CSV UTF-8</strong></div>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Importar</button>
            <a href="/almoxarifado/<?= $id ?>/modelo_excel" class="btn btn-outline-success">
              <i class="bi bi-file-earmark-arrow-down me-1"></i>Baixar Modelo CSV
            </a>
            <a href="/almoxarifado/<?= $id ?>" class="btn btn-outline-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabela de exemplo -->
    <div class="card">
      <div class="card-header fw-semibold small">
        <i class="bi bi-table me-1"></i>Exemplo de formato esperado
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Codigo</th>
              <th>Nome</th>
              <th>Categoria</th>
              <th>Unidade</th>
              <th>Quantidade</th>
              <th>Estoque Minimo</th>
              <th>CA (EPI)</th>
              <th>Valor Unitario</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>7954</code></td>
              <td>CALÇA BRIM CORDÃO ELASTICO M</td>
              <td><span class="badge bg-warning text-dark">epi</span></td>
              <td>un</td><td>53</td><td>20</td><td></td><td>35.00</td>
            </tr>
            <tr>
              <td><code>9047</code></td>
              <td>Óculos de Proteção incolor</td>
              <td><span class="badge bg-warning text-dark">epi</span></td>
              <td>UND</td><td>100</td><td>20</td><td>CA-12345</td><td>12.50</td>
            </tr>
            <tr>
              <td><code>9725</code></td>
              <td>Martelo tipo Unha 27mm C/ Cabo Fibra</td>
              <td><span class="badge bg-secondary">geral</span></td>
              <td>un</td><td>20</td><td>10</td><td></td><td>45.90</td>
            </tr>
            <tr>
              <td><code>8426</code></td>
              <td>Eletroduto Reforçado Laranja 20mm</td>
              <td><span class="badge bg-info">eletrica</span></td>
              <td>m</td><td>5500</td><td>2000</td><td></td><td>3.20</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="card-footer small text-muted">
        <i class="bi bi-lightbulb me-1"></i>
        Colunas <strong>CA</strong> e <strong>Valor Unitário</strong> são opcionais. 
        Se o código já existir no almoxarifado, a quantidade será <strong>somada</strong> ao estoque atual.
      </div>
    </div>
  </div>
</div>