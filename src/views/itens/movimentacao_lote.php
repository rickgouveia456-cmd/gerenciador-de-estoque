<?php /* views/itens/movimentacao_lote.php */ ?>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-arrow-left-right me-2"></i>Nova Movimentacao</h6>
      </div>
      <div class="card-body">
        <form method="POST" action="/movimentacao/lote" id="frmMovLote">
          <?= csrf_field() ?>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Almoxarifado</label>
              <select name="almoxarifado_id" id="selAlm" class="form-select" required>
                <option value="">Selecione...</option>
                <?php foreach ($almoxarifados as $alm): ?>
                <option value="<?= $alm['id'] ?>"><?= h($alm['nome']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Tipo</label>
              <select name="tipo" class="form-select" required>
                <option value="saida">📤 Saída</option>
                <option value="entrada">📥 Entrada</option>
                <option value="devolucao_epi">🔄 Devolução EPI</option>
                <option value="devolucao_ferramenta">🔄 Devolução Ferramenta</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Responsável</label>
              <input type="text" name="responsavel" class="form-control" placeholder="Nome do responsavel...">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Observacao</label>
              <input type="text" name="observacao" class="form-control" placeholder="Observacao geral...">
            </div>
          </div>

          <!-- Linhas de itens -->
          <div class="table-responsive mb-3">
            <table class="table table-sm mb-0">
              <thead>
                <tr>
                  <th>Item</th>
                  <th style="width:120px">Quantidade</th>
                  <th style="width:160px">Colaborador</th>
                  <th style="width:50px"></th>
                </tr>
              </thead>
              <tbody id="linhasMovimentacao"></tbody>
            </table>
          </div>

          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnAddLinha">
              <i class="bi bi-plus me-1"></i>Adicionar item
            </button>
            <button type="submit" class="btn btn-primary btn-sm">
              <i class="bi bi-check-lg me-1"></i>Confirmar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Historico -->
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Ultimas Movimentacoes</h6>
      </div>
      <div class="card-body p-0" style="max-height:500px;overflow-y:auto">
        <?php if (empty($historico)): ?>
        <div class="p-4 text-center text-muted">Nenhuma movimentacao.</div>
        <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($historico as $m): ?>
          <li class="list-group-item py-2">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <span class="badge bg-<?= $m['tipo']==='entrada' ? 'success' : 'warning' ?> me-1">
                  <?= $m['tipo']==='entrada' ? '📥' : '📤' ?>
                </span>
                <span class="fw-semibold small"><?= h($m['item_nome']) ?></span>
              </div>
              <span class="text-muted small"><?= fmt_data($m['data'], 'd/m H:i') ?></span>
            </div>
            <div class="text-muted" style="font-size:0.72rem">
              <?= fmt_qtd((float)$m['quantidade']) ?> <?= h($m['unidade']) ?>
              <?php if ($m['responsavel']): ?> · <?= h($m['responsavel']) ?><?php endif; ?>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
const itensJson = <?= json_encode($itensJson, JSON_UNESCAPED_UNICODE) ?>;

document.getElementById('btnAddLinha')?.addEventListener('click', () => {
  const almId = document.getElementById('selAlm')?.value;
  if (!almId) { alert('Selecione o almoxarifado primeiro.'); return; }
  addLinhaMovimentacao(almId, itensJson);
});
</script>
