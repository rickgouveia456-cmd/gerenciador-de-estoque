<?php /* views/epis/modulo.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0"><i class="bi bi-person-badge me-2"></i>Módulo EPI</h5>
</div>
<form method="GET" class="card p-3 mb-3">
  <div class="row g-2"><div class="col-md-6"><input type="text" name="q" class="form-control" placeholder="Buscar EPI..." value="<?= h($busca) ?>"></div><div class="col-md-2"><button class="btn btn-primary w-100">Buscar</button></div></div>
</form>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>ID</th><th>EPI</th><th>Almoxarifado</th><th class="text-center">Status</th><th class="text-center">Responsável</th></tr></thead>
      <tbody>
      <?php foreach($epis as $e): $cls=['disponivel'=>'success','em_uso'=>'warning','manutencao'=>'danger'][$e['status']]??'secondary'; ?>
      <tr>
        <td class="font-monospace"><?= h($e['identificacao']) ?></td>
        <td><?= h($e['nome']) ?> <?php if($e['tamanho']): ?><span class="badge bg-secondary"><?= h($e['tamanho']) ?></span><?php endif; ?></td>
        <td class="text-muted small"><?= h($e['alm_nome']) ?></td>
        <td class="text-center"><span class="badge bg-<?= $cls ?>"><?= ['disponivel'=>'Disponível','em_uso'=>'Em Uso','manutencao'=>'Manutenção'][$e['status']]??$e['status'] ?></span></td>
        <td class="text-center text-muted small"><?= h($e['responsavel_atual']??'—') ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
