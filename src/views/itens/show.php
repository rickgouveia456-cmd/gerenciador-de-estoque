<?php /* views/itens/show.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-1"><?= h($it['nome']) ?></h5>
    <span class="badge bg-secondary"><?= categoria_label($it['categoria']??'geral') ?></span>
    <span class="badge bg-info ms-1"><?= h($it['codigo']) ?></span>
    <?php if(!$it['ativo']): ?><span class="badge bg-secondary ms-1">Desativado</span><?php endif; ?>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <?php if(in_array($u['perfil'],['admin','almoxarife'])): ?>
    <a href="/item/<?= $it['id'] ?>/editar" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Editar</a>
    <?php if($it['ativo']): ?>
    <form method="POST" action="/item/<?= $it['id'] ?>/desativar"><<?= csrf_field() ?>
      <button class="btn btn-sm btn-outline-warning" onclick="return confirm('Desativar item?')"><i class="bi bi-pause-circle me-1"></i>Desativar</button>
    </form>
    <?php else: ?>
    <form method="POST" action="/item/<?= $it['id'] ?>/reativar"><?= csrf_field() ?>
      <button class="btn btn-sm btn-outline-success"><i class="bi bi-play-circle me-1"></i>Reativar</button>
    </form>
    <?php endif; ?>
    <?php endif; ?>
    <a href="/almoxarifado/<?= $it['almoxarifado_id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center">
      <?php $st2=status_item((float)$it['quantidade'],(float)$it['estoque_minimo']); $cls=$st2==='critico'?'danger':($st2==='alerta'?'warning':'success'); ?>
      <div class="fs-2 fw-bold text-<?= $cls ?>"><?= fmt_qtd((float)$it['quantidade']) ?></div>
      <div class="text-muted small"><?= h($it['unidade']) ?> em estoque</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center">
      <div class="fs-2 fw-bold text-muted"><?= fmt_qtd((float)$it['estoque_minimo']) ?></div>
      <div class="text-muted small">Estoque mínimo</div>
    </div>
  </div>
  <?php if($it['valor_unitario']): ?>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center">
      <div class="fs-5 fw-bold text-success"><?= fmt_dinheiro((float)$it['valor_unitario']) ?></div>
      <div class="text-muted small">Valor unitário</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center">
      <div class="fs-5 fw-bold text-success"><?= fmt_dinheiro((float)$it['quantidade']*(float)$it['valor_unitario']) ?></div>
      <div class="text-muted small">Valor em estoque</div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php if(in_array($u['perfil'],['admin','almoxarife'])&&$it['ativo']): ?>
<div class="card mb-4">
  <div class="card-header"><h6 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Registrar Movimentação</h6></div>
  <div class="card-body">
    <form method="POST" action="/item/<?= $it['id'] ?>/movimentar" class="row g-2 align-items-end">
      <?= csrf_field() ?>
      <div class="col-md-2">
        <label class="form-label small fw-semibold">Tipo</label>
        <select name="tipo" class="form-select form-select-sm">
          <option value="saida">📤 Saída</option>
          <option value="entrada">📥 Entrada</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-semibold">Quantidade</label>
        <input type="number" name="quantidade" class="form-control form-control-sm" step="0.01" min="0.01" required>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold">Responsável</label>
        <input type="text" name="responsavel" class="form-control form-control-sm">
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold">Observação</label>
        <input type="text" name="observacao" class="form-control form-control-sm">
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary btn-sm w-100">Confirmar</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Histórico de Movimentações</h6></div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Data</th><th>Tipo</th><th>Qtd</th><th>Responsável</th><th>Observação</th><?php if(in_array($u['perfil'],['admin','almoxarife'])): ?><th class="text-center">Dev.</th><?php endif; ?></tr></thead>
      <tbody>
      <?php foreach($movimentacoes as $m): ?>
      <tr>
        <td class="text-muted small"><?= fmt_data($m['data']) ?></td>
        <td><?= $m['tipo']==='entrada'?'<span class="badge bg-success">📥 Entrada</span>':'<span class="badge bg-warning">📤 Saída</span>' ?></td>
        <td><?= fmt_qtd((float)$m['quantidade']) ?> <?= h($it['unidade']) ?></td>
        <td class="small"><?= h($m['responsavel']??'—') ?></td>
        <td class="small text-muted"><?= h($m['observacao']??'') ?></td>
        <?php if(in_array($u['perfil'],['admin','almoxarife'])&&$m['tipo']==='saida'): ?>
        <td class="text-center">
          <form method="POST" action="/movimentacao/<?= $m['id'] ?>/devolvido"><?= csrf_field() ?>
            <button class="btn btn-xs btn-<?= $m['devolvido']?'success':'outline-secondary' ?>" style="font-size:0.7rem;padding:2px 6px">
              <?= $m['devolvido']?'✓':'-' ?>
            </button>
          </form>
        </td>
        <?php elseif(in_array($u['perfil'],['admin','almoxarife'])): ?><td></td><?php endif; ?>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
