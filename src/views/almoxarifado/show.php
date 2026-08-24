<?php /* views/almoxarifado/show.php */ ?>
<div class="row g-3">
<!-- ── Coluna principal ──────────────────────────────────────── -->
<div class="col-lg-8 col-xl-9">

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <div>
    <h5 class="fw-bold mb-1"><?= h($alm['nome']) ?></h5>
    <div class="text-muted small">
      <?php if($alm['obra']): ?><i class="bi bi-geo-alt me-1"></i><?= h($alm['obra']) ?><?php endif; ?>
      <?php if($alm['cidade']): ?> · <?= h($alm['cidade']) ?><?php endif; ?>
    </div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <?php if(in_array($u['perfil'],['admin','almoxarife'])): ?>
    <a href="/item/novo?alm=<?= $id ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus me-1"></i>Novo Item</a>
    <a href="/almoxarifado/<?= $id ?>/importar" class="btn btn-outline-secondary btn-sm"><i class="bi bi-upload me-1"></i>Importar</a>
    <?php endif; ?>
    <a href="/almoxarifado/<?= $id ?>/exportar" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>Exportar</a>
    <?php if($u['perfil']==='admin'): ?>
    <a href="/almoxarifado/<?= $id ?>/editar" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Editar</a>
    <?php endif; ?>
  </div>
</div>

<!-- Filtros -->
<form method="GET" class="card p-3 mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-4"><label class="form-label small fw-semibold mb-1">Buscar item</label>
      <input type="text" name="filtro" class="form-control form-control-sm" placeholder="Nome ou código..." value="<?= h($filtro) ?>"></div>
    <div class="col-md-3"><label class="form-label small fw-semibold mb-1">Categoria</label>
      <select name="categoria" class="form-select form-select-sm">
        <option value="">Todas</option>
        <?php foreach(['geral','epi','maquinario','eletrica','hidraulica','gas'] as $cat): ?>
        <option value="<?= $cat ?>" <?= $categoria===$cat?'selected':'' ?>><?= categoria_label($cat) ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="col-md-3"><label class="form-label small fw-semibold mb-1">Status</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">Todos</option>
        <option value="ok" <?= $status==='ok'?'selected':'' ?>>OK</option>
        <option value="alerta" <?= $status==='alerta'?'selected':'' ?>>Alerta</option>
        <option value="critico" <?= $status==='critico'?'selected':'' ?>>Crítico</option>
      </select></div>
    <div class="col-md-2"><button class="btn btn-primary btn-sm w-100">Filtrar</button></div>
  </div>
</form>

<!-- Badges -->
<div class="d-flex flex-wrap gap-2 mb-3">
  <span class="badge bg-info p-2"><i class="bi bi-box-seam me-1"></i><?= count($itens) ?> itens</span>
  <?php if($valorTotal>0): ?><span class="badge bg-success p-2"><i class="bi bi-currency-dollar me-1"></i><?= fmt_dinheiro($valorTotal) ?></span><?php endif; ?>
</div>

<!-- Tabela de itens -->
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th style="width:12%">Código</th>
          <th>Item</th>
          <th style="width:10%">Categoria</th>
          <th style="width:10%" class="text-center">Qtd</th>
          <th style="width:10%" class="text-center">Mínimo</th>
          <th style="width:10%" class="text-center">Status</th>
          <?php if(in_array($u['perfil'],['admin','almoxarife'])): ?><th style="width:10%" class="text-center">Compra</th><?php endif; ?>
          <th style="width:8%" class="text-center">Ações</th>
        </tr>
      </thead>
      <tbody>
      <?php if(empty($itens)): ?><tr><td colspan="8" class="text-center text-muted py-4">Nenhum item encontrado.</td></tr><?php endif; ?>
      <?php foreach($itens as $it):
        $st=status_item((float)$it['quantidade'],(float)$it['estoque_minimo']);
        $rowCls=!$it['ativo']?'table-secondary opacity-50':'';
      ?>
        <tr class="<?= $rowCls ?>">
          <td class="text-muted small font-monospace"><?= h($it['codigo']) ?></td>
          <td>
            <a href="/item/<?= $it['id'] ?>" class="fw-semibold text-decoration-none text-dark"><?= h($it['nome']) ?></a>
            <?php if($it['fixado']): ?><i class="bi bi-pin-fill text-warning ms-1" title="Fixado"></i><?php endif; ?>
            <?php if(!$it['ativo']): ?><span class="badge bg-secondary ms-1">Desativado</span><?php endif; ?>
          </td>
          <td><span class="badge bg-secondary"><?= categoria_label($it['categoria']??'geral') ?></span></td>
          <td class="text-center fw-bold <?= $st==='critico'?'text-danger':($st==='alerta'?'text-warning':'text-success') ?>">
            <?= fmt_qtd((float)$it['quantidade']) ?> <?= h($it['unidade']) ?>
          </td>
          <td class="text-center text-muted"><?= fmt_qtd((float)$it['estoque_minimo']) ?></td>
          <td class="text-center"><?= status_badge($st) ?></td>
          <?php if(in_array($u['perfil'],['admin','almoxarife'])): ?>
          <td class="text-center">
            <?php $sc=$it['status_compra']??'pendente'; $scMap=['pendente'=>'warning','comprado'=>'success','nao_necessario'=>'secondary']; $scLabel=['pendente'=>'Pendente','comprado'=>'Comprado','nao_necessario'=>'N/A']; ?>
            <form method="POST" action="/item/<?= $it['id'] ?>/status_compra"><<?= csrf_field() ?>
              <select name="status_compra" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php foreach($scMap as $val=>$cls): ?><option value="<?= $val ?>" <?= $sc===$val?'selected':'' ?>><?= $scLabel[$val] ?></option><?php endforeach; ?>
              </select>
            </form>
          </td>
          <?php endif; ?>
          <td class="text-center">
            <a href="/item/<?= $it['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Botoes admin -->
<?php if($u['perfil']==='admin'): ?>
<div class="mt-3 d-flex gap-2">
  <a href="/admin/reativar_itens?alm=<?= $id ?>" class="btn btn-outline-warning btn-sm"><i class="bi bi-arrow-counterclockwise me-1"></i>Itens Desativados</a>
  <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTransferir"><i class="bi bi-arrow-left-right me-1"></i>Transferir Itens</button>
</div>
<?php endif; ?>
</div><!-- /col principal -->

<!-- ── Right Panel ───────────────────────────────────────────── -->
<div class="col-lg-4 col-xl-3">

  <!-- Kits -->
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
      <span class="fw-semibold small"><i class="bi bi-box2-heart me-1" style="color:var(--accent)"></i>Kits</span>
      <?php if(in_array($u['perfil'],['admin','almoxarife'])): ?>
      <a href="/kits" class="btn btn-xs btn-outline-secondary" style="font-size:0.7rem;padding:2px 8px">Ver todos</a>
      <?php endif; ?>
    </div>
    <div class="card-body p-0">
      <?php if(empty($kitsAlm)): ?>
      <div class="text-center py-3 text-muted small">Nenhum kit cadastrado.</div>
      <?php else: ?>
      <ul class="list-group list-group-flush">
        <?php foreach($kitsAlm as $kit): ?>
        <li class="list-group-item py-2 d-flex justify-content-between align-items-center">
          <span class="small fw-semibold"><?= h($kit['nome']) ?></span>
          <span class="badge bg-secondary"><?= $kit['total_itens'] ?> itens</span>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>

  <!-- Ferramentas -->
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
      <span class="fw-semibold small"><i class="bi bi-tools me-1" style="color:var(--accent)"></i>Ferramentas</span>
      <a href="/ferramentas?alm=<?= $id ?>" class="btn btn-xs btn-outline-secondary" style="font-size:0.7rem;padding:2px 8px">
        <i class="bi bi-plus me-1"></i>Gerenciar
      </a>
    </div>
    <div class="card-body p-0">
      <?php if(empty($ferramentasAlm)): ?>
      <div class="text-center py-3 text-muted small">Nenhuma ferramenta.</div>
      <?php else: ?>
      <ul class="list-group list-group-flush">
        <?php foreach(array_slice($ferramentasAlm,0,8) as $f):
          $fCls=['disponivel'=>'success','em_uso'=>'warning','manutencao'=>'danger'][$f['status']]??'secondary';
          $fIco=['disponivel'=>'check-circle','em_uso'=>'person-fill','manutencao'=>'wrench'][$f['status']]??'circle';
        ?>
        <li class="list-group-item py-2">
          <div class="d-flex justify-content-between align-items-start">
            <div class="overflow-hidden me-2">
              <div class="small fw-semibold text-truncate"><?= h($f['nome']) ?></div>
              <div class="text-muted" style="font-size:0.7rem"><?= h($f['identificacao']) ?></div>
            </div>
            <span class="badge bg-<?= $fCls ?> flex-shrink-0"><i class="bi bi-<?= $fIco ?>"></i></span>
          </div>
          <?php if($f['responsavel_atual']): ?>
          <div class="text-muted mt-1" style="font-size:0.7rem"><i class="bi bi-person me-1"></i><?= h($f['responsavel_atual']) ?></div>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
        <?php if(count($ferramentasAlm)>8): ?>
        <li class="list-group-item text-center py-2"><a href="/ferramentas?alm=<?= $id ?>" class="small text-muted">Ver todas (<?= count($ferramentasAlm) ?>)</a></li>
        <?php endif; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>

  <!-- EPIs -->
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
      <span class="fw-semibold small"><i class="bi bi-shield-check me-1" style="color:var(--accent)"></i>EPIs</span>
      <a href="/epis?alm=<?= $id ?>" class="btn btn-xs btn-outline-secondary" style="font-size:0.7rem;padding:2px 8px">
        <i class="bi bi-plus me-1"></i>Gerenciar
      </a>
    </div>
    <div class="card-body p-0">
      <?php if(empty($episAlm)): ?>
      <div class="text-center py-3 text-muted small">Nenhum EPI cadastrado.</div>
      <?php else: ?>
      <ul class="list-group list-group-flush">
        <?php foreach(array_slice($episAlm,0,8) as $e):
          $eCls=['disponivel'=>'success','em_uso'=>'warning','manutencao'=>'danger'][$e['status']]??'secondary';
        ?>
        <li class="list-group-item py-2">
          <div class="d-flex justify-content-between align-items-start">
            <div class="overflow-hidden me-2">
              <div class="small fw-semibold text-truncate"><?= h($e['nome']) ?></div>
              <?php if($e['tamanho']): ?><span class="badge bg-light text-dark border" style="font-size:0.65rem"><?= h($e['tamanho']) ?></span><?php endif; ?>
            </div>
            <span class="badge bg-<?= $eCls ?> flex-shrink-0"><?= $e['quantidade'] ?></span>
          </div>
          <?php if($e['responsavel_atual']): ?>
          <div class="text-muted mt-1" style="font-size:0.7rem"><i class="bi bi-person me-1"></i><?= h($e['responsavel_atual']) ?></div>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
        <?php if(count($episAlm)>8): ?>
        <li class="list-group-item text-center py-2"><a href="/epis?alm=<?= $id ?>" class="small text-muted">Ver todos (<?= count($episAlm) ?>)</a></li>
        <?php endif; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>

</div><!-- /right panel -->
</div><!-- /row -->

<!-- Modal Transferir (Admin) -->
<?php if($u['perfil']==='admin'): ?>
<div class="modal fade" id="modalTransferir" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="bi bi-arrow-left-right me-2"></i>Transferir Itens</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="POST" action="/admin/transferir_itens">
        <?= csrf_field() ?>
        <input type="hidden" name="origem_id" value="<?= $id ?>">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Destino</label>
            <select name="destino_id" class="form-select" required>
              <option value="">Selecione o destino...</option>
              <?php $outros=db()->query('SELECT id,nome FROM almoxarifado ORDER BY nome')->fetchAll(); foreach($outros as $o){if($o['id']==$id)continue; echo '<option value="'.$o['id'].'">'.h($o['nome']).'</option>';} ?>
            </select>
          </div>
          <div style="max-height:300px;overflow-y:auto;border:1px solid var(--border);border-radius:8px">
            <?php foreach($itens as $it): if(!$it['ativo']) continue; ?>
            <div class="d-flex align-items-center gap-2 p-2 border-bottom">
              <input type="checkbox" name="item_ids[]" value="<?= $it['id'] ?>" class="form-check-input mt-0">
              <div class="flex-grow-1"><span class="fw-semibold small"><?= h($it['nome']) ?></span><span class="text-muted small ms-2"><?= fmt_qtd((float)$it['quantidade']) ?> <?= h($it['unidade']) ?></span></div>
              <input type="number" name="qtd_<?= $it['id'] ?>" step="0.01" min="0.01" max="<?= $it['quantidade'] ?>" placeholder="Qtd" class="form-control form-control-sm" style="width:80px">
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-left-right me-1"></i>Transferir</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>