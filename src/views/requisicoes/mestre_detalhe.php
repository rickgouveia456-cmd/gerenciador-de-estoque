<?php /* views/requisicoes/mestre_detalhe.php */
$statusMap=['pendente'=>['warning','clock','Pendente'],'aprovada'=>['info','check-circle','Aprovada'],'recusada'=>['danger','x-circle','Recusada'],'entregue'=>['success','bag-check','Entregue'],'parcial'=>['warning','dash-circle','Parcial'],'cancelada'=>['secondary','slash-circle','Cancelada']];
[$cls,$ico,$lbl]=$statusMap[$req['status']]??['secondary','question','?'];
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-1">Requisição <?= h($req['protocolo']??'#'.$req['id']) ?></h5>
    <span class="badge bg-<?= $cls ?>"><i class="bi bi-<?= $ico ?> me-1"></i><?= $lbl ?></span>
  </div>
  <div class="d-flex gap-2">
    <?php if(in_array($u['perfil'],['admin','almoxarife'])&&$req['status']!=='entregue'): ?>
    <a href="/requisicoes/mestre/<?= $req['id'] ?>/editar" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Editar</a>
    <?php endif; ?>
    <a href="/requisicoes/mestre" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
  </div>
</div>
<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Colaborador</div><div class="fw-semibold"><?= h($req['colaborador']) ?></div></div></div>
  <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Almoxarifado</div><div class="fw-semibold"><?= h($req['alm_nome']) ?></div></div></div>
  <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Solicitante</div><div class="fw-semibold"><?= h($req['mestre_nome']) ?></div></div></div>
  <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Data</div><div class="fw-semibold"><?= fmt_data($req['data_criacao'],'d/m/Y H:i') ?></div></div></div>
</div>
<?php if($req['observacao']): ?><div class="alert alert-info py-2 mb-3"><i class="bi bi-chat me-2"></i><?= h($req['observacao']) ?></div><?php endif; ?>
<div class="card mb-3">
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>Item</th><th class="text-center">Qtd Solicitada</th><th class="text-center">Estoque</th><th class="text-center">Status</th><th>Observação</th></tr></thead>
      <tbody>
      <?php foreach($itens as $ri): $sc=['aprovado'=>['success','✓'],'recusado'=>['danger','✗'],'pendente'=>['secondary','?']][$ri['status_item']]??['secondary','?']; ?>
      <tr>
        <td><?= h($ri['item_nome']) ?></td>
        <td class="text-center"><?= fmt_qtd((float)$ri['quantidade']) ?> <?= h($ri['unidade']) ?></td>
        <td class="text-center text-<?= (float)$ri['estoque_atual']>=(float)$ri['quantidade']?'success':'danger' ?>"><?= fmt_qtd((float)$ri['estoque_atual']) ?></td>
        <td class="text-center"><span class="badge bg-<?= $sc[0] ?>"><?= $sc[1] ?></span></td>
        <td class="small text-muted"><?= h($ri['observacao']??'') ?><?= $ri['motivo_recusa']?' — '.$ri['motivo_recusa']:'' ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php if(in_array($u['perfil'],['admin','almoxarife'])&&$req['status']==='pendente'): ?>
<div class="card mb-3">
  <div class="card-header fw-semibold">Aprovar / Recusar</div>
  <div class="card-body">
    <form method="POST" action="/requisicoes/mestre/<?= $req['id'] ?>/aprovar">
      <?= csrf_field() ?>
      <div class="d-flex gap-2 mb-3">
        <button type="submit" name="decisao" value="aprovada" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Aprovar Tudo</button>
        <button type="submit" name="decisao" value="recusada" class="btn btn-danger" onclick="return confirm('Recusar toda a requisição?')"><i class="bi bi-x-lg me-1"></i>Recusar Tudo</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php if(in_array($u['perfil'],['admin','almoxarife'])&&in_array($req['status'],['aprovada','pendente','parcial'])): ?>
<form method="POST" action="/requisicoes/mestre/<?= $req['id'] ?>/entregar" onsubmit="return confirm('Confirmar entrega e baixar estoque?')">
  <?= csrf_field() ?>
  <button class="btn btn-primary"><i class="bi bi-bag-check me-2"></i>Confirmar Entrega</button>
</form>
<?php endif; ?>
