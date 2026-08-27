<?php /* views/catalogo/valor_estoque.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0"><i class="bi bi-currency-dollar me-2"></i>Valor em Estoque</h5>
  <span class="badge bg-success fs-6"><?= fmt_dinheiro($totalGeral) ?> total</span>
</div>

<?php if($totalGeral == 0): ?>
<div class="alert alert-warning">
  <i class="bi bi-exclamation-triangle me-2"></i>
  Nenhum item tem valor unitário cadastrado. Para calcular o valor, adicione o preço unitário dos itens pelo <a href="/catalogo">Catálogo de Insumos</a>.
</div>
<?php endif; ?>

<?php foreach($resumo as $r): ?>
<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold"><?= h($r["almoxarifado"]["nome"]) ?></span>
    <div class="d-flex align-items-center gap-2">
      <span class="text-muted small"><?= $r["total_itens"] ?> itens</span>
      <?php if($r["itens_sem_valor"]>0): ?>
      <span class="badge bg-warning text-dark"><?= $r["itens_sem_valor"] ?> sem valor</span>
      <?php endif; ?>
      <span class="badge bg-success"><?= fmt_dinheiro($r["valor_total"]) ?></span>
    </div>
  </div>
  <?php if(!empty($r["itens"])): ?>
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead>
        <tr><th>Item</th><th class="text-center">Qtd</th><th class="text-center">Valor Unit.</th><th class="text-center">Total</th></tr>
      </thead>
      <tbody>
      <?php foreach($r["itens"] as $row): ?>
      <tr>
        <td><?= h($row["item"]["nome"]) ?></td>
        <td class="text-center"><?= fmt_qtd((float)$row["item"]["quantidade"]) ?> <?= h($row["item"]["unidade"]) ?></td>
        <td class="text-center">
          <?php
          $vU = (float)$row["item"]["valor_unitario"];
          $uN = $row["item"]["unidade"] ?? "un";
          $und_l = strtolower(trim($uN));
          $cont = in_array($und_l, ["kg","kgs","l","lt","litro","litros","lts","m","ml","mt","metro","metros"]);
          $labels2 = ["kg"=>"kg","kgs"=>"kg","l"=>"L","lt"=>"L","litro"=>"L","litros"=>"L","lts"=>"L","m"=>"m","ml"=>"ml","mt"=>"m","metro"=>"m","metros"=>"m"];
          $lbl = $labels2[$und_l] ?? $uN;
          echo $cont ? 'R$ '.number_format($vU,2,',','.').'/'.$lbl : fmt_dinheiro($vU);
          ?>
        </td>
        <td class="text-center fw-semibold text-success"><?= fmt_dinheiro($row["valor_total"]) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if(count($r["itens"])>10): ?>
      <tr><td colspan="4" class="text-center text-muted small py-1">... e mais <?= count($r["itens"])-10 ?> itens com valor</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="card-body text-muted small py-2">
    <i class="bi bi-info-circle me-1"></i>Nenhum item com valor unitário neste almoxarifado.
    <a href="/catalogo" class="ms-1">Adicionar valores no catálogo →</a>
  </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>