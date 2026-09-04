<?php /* views/requisicoes/index.php */ ?>

<div class="mb-3"><a href="/requisicoes/mestre" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Requisições</a></div>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0">Requisições</h5>
  <a href="/requisicoes/nova" class="btn btn-primary btn-sm"><i class="bi bi-plus me-1"></i>Nova</a>
</div>
<form method="GET" class="card p-3 mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-3"><input type="text" name="colaborador" class="form-control form-control-sm" placeholder="Colaborador..." value="<?= h($colab??'') ?>"></div>
    <div class="col-md-2"><select name="status" class="form-select form-select-sm"><option value="">Todos</option><option value="aberta" <?= ($status??'')==='aberta'?'selected':'' ?>>Aberta</option><option value="devolvida" <?= ($status??'')==='devolvida'?'selected':'' ?>>Devolvida</option></select></div>
    <div class="col-md-2"><input type="date" name="data_ini" class="form-control form-control-sm" value="<?= h($dini??'') ?>"></div>
    <div class="col-md-2"><input type="date" name="data_fim" class="form-control form-control-sm" value="<?= h($dfim??'') ?>"></div>
    <div class="col-md-2"><button class="btn btn-primary btn-sm w-100">Filtrar</button></div>
    <div class="col-md-1"><a href="/requisicoes" class="btn btn-outline-secondary btn-sm w-100">✕</a></div>
  </div>
</form>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Item</th><th>Almoxarifado</th><th>Colaborador</th><th>Qtd</th><th>Data</th><th class="text-center">Status</th><th class="text-center">Ação</th></tr></thead>
      <tbody>
      <?php if(empty($requisicoes)): ?><tr><td colspan="7" class="text-center text-muted py-3">Nenhuma requisição.</td></tr><?php endif; ?>
      <?php foreach($requisicoes as $r): ?>
      <tr>
        <td><?= h($r['item_nome']) ?></td>
        <td class="text-muted small"><?= h($r['alm_nome']) ?></td>
        <td><?= h($r['colaborador']) ?></td>
        <td><?= fmt_qtd((float)$r['quantidade']) ?> <?= h($r['unidade']) ?></td>
        <td class="text-muted small"><?= fmt_data($r['data_retirada'],'d/m/Y') ?></td>
        <td class="text-center"><span class="badge bg-<?= $r['status']==='aberta'?'warning':'success' ?>"><?= $r['status']==='aberta'?'Em uso':'Devolvido' ?></span></td>
        <td class="text-center">
          <?php if($r['status']==='aberta'): ?>
          <form method="POST" action="/requisicoes/<?= $r['id'] ?>/devolver" class="d-inline"><?= csrf_field() ?>
            <button class="btn btn-sm btn-success" onclick="return confirm('Confirmar devolução?')"><i class="bi bi-arrow-return-left"></i></button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
