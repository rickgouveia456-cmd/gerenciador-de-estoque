<?php /* views/usuarios/index.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0"><i class="bi bi-people me-2"></i>Usuários</h5>
  <a href="/usuarios/novo" class="btn btn-primary btn-sm"><i class="bi bi-plus me-1"></i>Novo</a>
</div>
<?php foreach($grupos as $perfil=>$grupo): if(empty($grupo['usuarios'])) continue; ?>
<div class="card mb-3">
  <div class="card-header d-flex align-items-center gap-2">
    <span class="fw-semibold"><?= $grupo['label'] ?></span>
    <span class="badge bg-secondary"><?= count($grupo['usuarios']) ?></span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Nome</th><th>Login</th><th>Almoxarifado</th><th class="text-center">Status</th><th class="text-center">Ações</th></tr></thead>
      <tbody>
      <?php foreach($grupo['usuarios'] as $u2): ?>
      <tr class="<?= !$u2['ativo']?'opacity-50':'' ?>">
        <td class="fw-semibold"><?= h($u2['nome']) ?></td>
        <td class="font-monospace small"><?= h($u2['login']) ?></td>
        <td class="text-muted small"><?= h($u2['alm_nome']??'—') ?></td>
        <td class="text-center"><span class="badge bg-<?= $u2['ativo']?'success':'secondary' ?>"><?= $u2['ativo']?'Ativo':'Inativo' ?></span></td>
        <td class="text-center">
          <a href="/usuarios/<?= $u2['id'] ?>/editar" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
          <form method="POST" action="/usuarios/<?= $u2['id'] ?>/deletar" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Remover usuário?')"><i class="bi bi-trash"></i></button></form>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>
