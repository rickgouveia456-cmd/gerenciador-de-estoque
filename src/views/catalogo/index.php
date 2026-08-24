<?php /* views/catalogo/index.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h5 class="fw-bold mb-0"><i class="bi bi-journal-text me-2"></i>Catálogo de Insumos</h5>
  <div class="d-flex gap-2">
    <a href="/catalogo/valor_estoque" class="btn btn-sm btn-outline-success"><i class="bi bi-currency-dollar me-1"></i>Valor em Estoque</a>
    <?php if(in_array($u['perfil'],['admin','almoxarife'])): ?>
    <a href="/catalogo/novo" class="btn btn-sm btn-primary"><i class="bi bi-plus me-1"></i>Novo</a>
    <?php endif; ?>
  </div>
</div>
<form method="GET" class="card p-3 mb-3">
  <div class="row g-2"><div class="col-md-5"><input type="text" name="q" class="form-control form-control-sm" placeholder="Buscar..." value="<?= h($q) ?>"></div><div class="col-md-3"><select name="categoria" class="form-select form-select-sm"><option value="">Todas</option><?php foreach($categorias as $c): ?><option value="<?= $c ?>" <?= ($cat??'')===$c?'selected':'' ?>><?= categoria_label($c) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><button class="btn btn-primary btn-sm w-100">Filtrar</button></div><div class="col-md-2"><a href="/catalogo" class="btn btn-outline-secondary btn-sm w-100">Limpar</a></div></div>
</form>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Nome</th><th>Ref</th><th>Unidade</th><th>Categoria</th><th class="text-center">Valor Unit.</th><?php if(in_array($u['perfil'],['admin','almoxarife'])): ?><th class="text-center">Ações</th><?php endif; ?></tr></thead>
      <tbody>
      <?php if(empty($insumos)): ?><tr><td colspan="6" class="text-center text-muted py-3">Nenhum insumo.</td></tr><?php endif; ?>
      <?php foreach($insumos as $ins): ?>
      <tr>
        <td class="fw-semibold"><?= h($ins['nome']) ?></td>
        <td class="font-monospace small"><?= h($ins['codigo_ref']??'—') ?></td>
        <td><?= h($ins['unidade']) ?></td>
        <td><span class="badge bg-secondary"><?= categoria_label($ins['categoria']??'geral') ?></span></td>
        <td class="text-center"><?= $ins['valor_unitario']?fmt_dinheiro((float)$ins['valor_unitario']):'—' ?></td>
        <?php if(in_array($u['perfil'],['admin','almoxarife'])): ?>
        <td class="text-center">
          <a href="/catalogo/<?= $ins['id'] ?>/editar" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
          <?php if($u['perfil']==='admin'): ?>
          <form method="POST" action="/catalogo/<?= $ins['id'] ?>/deletar" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Remover?')"><i class="bi bi-trash"></i></button></form>
          <?php endif; ?>
        </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
