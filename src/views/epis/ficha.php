<?php /* views/epis/ficha.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0">Ficha EPI — <?= h($colaborador) ?></h5>
  <a href="/relatorios" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
</div>
<?php if(empty($movs)): ?>
<div class="alert alert-warning">Nenhuma retirada de EPI encontrada.</div>
<?php else: ?>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Data</th><th>EPI</th><th>CA</th><th>Quantidade</th><th>Almoxarifado</th></tr></thead>
      <tbody>
      <?php foreach($movs as $m): ?>
      <tr>
        <td><?= fmt_data($m['data'],'d/m/Y') ?></td>
        <td><?= h($m['item_nome']) ?></td>
        <td class="text-muted small"><?= h($m['ca']??'—') ?></td>
        <td><?= fmt_qtd((float)$m['quantidade']) ?> <?= h($m['unidade']) ?></td>
        <td class="text-muted small"><?= h($m['alm_nome']) ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
