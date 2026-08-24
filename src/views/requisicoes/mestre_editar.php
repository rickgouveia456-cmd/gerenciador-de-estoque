<?php /* views/requisicoes/mestre_editar.php */ ?>
<div class="card"><div class="card-header"><h6 class="mb-0">Editar Req <?= h($req['protocolo']??'#'.$req['id']) ?></h6></div>
<div class="card-body">
  <form method="POST" action="/requisicoes/mestre/<?= $req['id'] ?>/editar">
    <?= csrf_field() ?>
    <div class="row g-3 mb-3">
      <div class="col-md-6"><label class="form-label fw-semibold">Colaborador</label><input type="text" name="colaborador" class="form-control" value="<?= h($req['colaborador']) ?>" required></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Observação</label><input type="text" name="observacao" class="form-control" value="<?= h($req['observacao']??'') ?>"></div>
    </div>
    <div class="table-responsive mb-3"><table class="table"><thead><tr><th>Item</th><th>Qtd</th><th>Obs</th></tr></thead><tbody>
    <?php foreach($itens as $ri): ?>
    <tr>
      <td><?= h($ri['item_id']) ?> — ID<?= $ri['item_id'] ?></td>
      <td><input type="number" name="qtd_<?= $ri['id'] ?>" class="form-control form-control-sm" value="<?= $ri['quantidade'] ?>" step="0.01" style="width:90px"></td>
      <td><input type="text" name="obs_<?= $ri['id'] ?>" class="form-control form-control-sm" value="<?= h($ri['observacao']??'') ?>"></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <div class="d-flex gap-2"><button type="submit" class="btn btn-primary">Salvar</button><a href="/requisicoes/mestre/<?= $req['id'] ?>" class="btn btn-outline-secondary">Cancelar</a></div>
  </form>
</div></div>
