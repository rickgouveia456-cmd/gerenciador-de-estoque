<?php /* views/relatorios/consumo.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0"><i class="bi bi-arrow-left-right me-2"></i>Relatório de Consumo</h5>
</div>
<form method="GET" class="card p-3 mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label small">Almoxarifado</label><select name="almoxarifado_id" class="form-select form-select-sm"><option value="">Todos</option><?php foreach($almoxarifados as $a): ?><option value="<?= $a['id'] ?>" <?= $almId==$a['id']?'selected':'' ?>><?= h($a['nome']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><label class="form-label small">Data Início</label><input type="date" name="data_ini" class="form-control form-control-sm" value="<?= h($dataIni) ?>"></div>
    <div class="col-md-2"><label class="form-label small">Data Fim</label><input type="date" name="data_fim" class="form-control form-control-sm" value="<?= h($dataFim) ?>"></div>
    <div class="col-md-2"><label class="form-label small">Tipo</label><select name="aba" class="form-select form-select-sm"><option value="saidas" <?= $aba==='saidas'?'selected':'' ?>>Saídas</option><option value="entradas" <?= $aba==='entradas'?'selected':'' ?>>Entradas</option></select></div>
    <div class="col-md-2"><button class="btn btn-primary btn-sm w-100 mt-3">Filtrar</button></div>
  </div>
</form>
<div class="card">
  <div class="card-header d-flex justify-content-between"><span><?= count($movimentacoes) ?> registro(s)</span></div>
  <div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
      <thead><tr><th>Data</th><th>Código</th><th>Item</th><th>Almoxarifado</th><th class="text-center">Qtd</th><th>Responsável</th><th>Observação</th></tr></thead>
      <tbody>
      <?php if(empty($movimentacoes)): ?><tr><td colspan="7" class="text-center text-muted py-3">Nenhum registro.</td></tr><?php endif; ?>
      <?php foreach($movimentacoes as $m): ?>
      <tr>
        <td class="small"><?= fmt_data($m['data'],'d/m/Y H:i') ?></td>
        <td class="font-monospace small"><?= h($m['codigo']) ?></td>
        <td><?= h($m['item_nome']) ?></td>
        <td class="text-muted small"><?= h($m['alm_nome']) ?></td>
        <td class="text-center"><?= fmt_qtd((float)$m['quantidade']) ?> <?= h($m['unidade']) ?></td>
        <td class="small"><?= h($m['responsavel']??'—') ?></td>
        <td class="small text-muted"><?= h($m['observacao']??'') ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
