<?php /* views/catalogo/valor_estoque.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0"><i class="bi bi-currency-dollar me-2"></i>Valor em Estoque</h5>
  <span class="badge bg-success fs-6"><?= fmt_dinheiro($totalGeral) ?> total</span>
</div>
<?php foreach($resumo as $r): if($r['valor_total']<=0&&empty($r['itens'])) continue; ?>
<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold"><?= h($r['almoxarifado']['nome']) ?></span>
    <span class="badge bg-success"><?= fmt_dinheiro($r['valor_total']) ?></span>
  </div>
  <?php if(!empty($r['itens'])): ?>
  <div class="table-responsive"><table class="table table-sm mb-0">
    <thead><tr><th>Item</th><th class="text-center">Qtd</th><th class="text-center">Valor Unit.</th><th class="text-center">Valor Total</th></tr></thead>
    <tbody>
    <?php foreach(array_slice($r['itens'],0,10) as $row): ?>
    <tr><td><?= h($row['item']['nome']) ?></td><td class="text-center"><?= fmt_qtd((float)$row['item']['quantidade']) ?> <?= h($row['item']['unidade']) ?></td><td class="text-center"><?= fmt_dinheiro((float)$row['item']['valor_unitario']) ?></td><td class="text-center fw-semibold"><?= fmt_dinheiro($row['valor_total']) ?></td></tr>
    <?php endforeach; ?>
    <?php if(count($r['itens'])>10): ?><tr><td colspan="4" class="text-center text-muted">... e mais <?= count($r['itens'])-10 ?> itens</td></tr><?php endif; ?>
    </tbody>
  </table></div>
  <?php else: ?>
  <div class="card-body text-muted small">Nenhum item com valor cadastrado. <?= $r['itens_sem_valor'] ?> item(ns) sem valor unitário.</div>
  <?php endif; ?>
</div>
<?php endforeach; ?>
