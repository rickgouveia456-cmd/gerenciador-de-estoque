<?php /* views/colaboradores/index.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0"><i class="bi bi-people me-2"></i>Colaboradores</h5>
</div>
<?php if(in_array($u['perfil'],['admin','almoxarife','analista'])): ?>
<div class="card mb-3">
  <div class="card-header fw-semibold">Novo Colaborador</div>
  <div class="card-body">
    <form method="POST" action="/colaboradores/novo" class="row g-2 align-items-end">
      <?= csrf_field() ?>
      <div class="col-md-3"><input type="text" name="nome" class="form-control form-control-sm" placeholder="Nome completo *" required></div>
      <div class="col-md-2"><input type="text" name="funcao" class="form-control form-control-sm" placeholder="Função"></div>
      <div class="col-md-2"><select name="escopo" class="form-select form-select-sm"><option value="">Escopo</option><option value="estrutura">Estrutura</option><option value="acabamento">Acabamento</option><option value="infraestrutura">Infraestrutura</option></select></div>
      <div class="col-md-2"><input type="text" name="obra" class="form-control form-control-sm" placeholder="Obra"></div>
      <div class="col-md-2"><input type="text" name="cidade" class="form-control form-control-sm" placeholder="Cidade"></div>
      <div class="col-md-1"><button class="btn btn-primary btn-sm w-100"><i class="bi bi-plus"></i></button></div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php foreach($grupos as $escopo=>$grupo): if(empty($grupo['colaboradores'])) continue; ?>
<div class="card mb-3">
  <div class="card-header d-flex align-items-center gap-2">
    <span class="fw-semibold"><?= $grupo['label'] ?></span>
    <span class="badge bg-secondary"><?= count($grupo['colaboradores']) ?></span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Nome</th><th>Função</th><th>Obra</th><th class="text-center">Status</th><?php if(in_array($u['perfil'],['admin','almoxarife'])): ?><th class="text-center">Ações</th><?php endif; ?></tr></thead>
      <tbody>
      <?php foreach($grupo['colaboradores'] as $c): ?>
      <tr class="<?= !$c['ativo']?'opacity-50':'' ?>">
        <td class="fw-semibold"><?= h($c['nome']) ?></td>
        <td class="text-muted small"><?= h($c['funcao']??'—') ?></td>
        <td class="text-muted small"><?= h($c['obra']??'—') ?></td>
        <td class="text-center"><span class="badge bg-<?= $c['ativo']?'success':'secondary' ?>"><?= $c['ativo']?'Ativo':'Inativo' ?></span></td>
        <?php if(in_array($u['perfil'],['admin','almoxarife'])): ?>
        <td class="text-center">
          <a href="/colaboradores/<?= $c['id'] ?>/editar" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
          <form method="POST" action="/colaboradores/<?= $c['id'] ?>/deletar" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm btn-outline-warning ms-1" onclick="return confirm('Desativar?')"><i class="bi bi-pause"></i></button></form>
        </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>
