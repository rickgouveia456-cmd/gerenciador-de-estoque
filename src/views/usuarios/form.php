<?php $isNew=!isset($u2)||!$u2; $action=$isNew?'/usuarios/novo':"/usuarios/{$u2['id']}/editar"; ?>
<div class="row justify-content-center"><div class="col-md-8">
  <div class="card"><div class="card-header"><h6 class="mb-0"><?= $isNew?'Novo Usuário':'Editar Usuário' ?></h6></div>
  <div class="card-body"><form method="POST" action="<?= $action ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label fw-semibold">Nome *</label><input type="text" name="nome" class="form-control" required value="<?= h($u2['nome']??'') ?>"></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Login *</label><input type="text" name="login" class="form-control" required value="<?= h($u2['login']??'') ?>"></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Senha <?= $isNew?'*':'(deixe em branco para manter)' ?></label><input type="password" name="senha" class="form-control" <?= $isNew?'required':'' ?> autocomplete="new-password"></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Email</label><input type="email" name="email" class="form-control" value="<?= h($u2['email']??'') ?>"></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Perfil *</label><select name="perfil" class="form-select"><?php foreach(['admin','almoxarife','mestre','tecnico_seguranca','analista','colaborador'] as $p): ?><option value="<?= $p ?>" <?= ($u2['perfil']??'')===$p?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$p)) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-8"><label class="form-label fw-semibold">Almoxarifado</label><select name="almoxarifado_id" class="form-select"><option value="">—</option><?php foreach($almoxarifados as $a): ?><option value="<?= $a['id'] ?>" <?= ($u2['almoxarifado_id']??0)==$a['id']?'selected':'' ?>><?= h($a['nome']) ?></option><?php endforeach; ?></select></div>
      <?php if(!$isNew): ?>
      <div class="col-md-4"><div class="form-check mt-4"><input type="checkbox" name="ativo" class="form-check-input" <?= $u2['ativo']?'checked':'' ?>><label class="form-check-label">Ativo</label></div></div>
      <div class="col-md-4"><div class="form-check mt-4"><input type="checkbox" name="pode_requisitar" class="form-check-input" <?= $u2['pode_requisitar']?'checked':'' ?>><label class="form-check-label">Pode Requisitar</label></div></div>
      <div class="col-md-4"><div class="form-check mt-4"><input type="checkbox" name="pode_ver_alertas" class="form-check-input" <?= $u2['pode_ver_alertas']?'checked':'' ?>><label class="form-check-label">Ver Alertas</label></div></div>
      <?php endif; ?>
    </div>
    <div class="d-flex gap-2 mt-3"><button type="submit" class="btn btn-primary"><?= $isNew?'Criar':'Salvar' ?></button><a href="/usuarios" class="btn btn-outline-secondary">Cancelar</a></div>
  </form></div></div>
  <?php if(!$isNew): ?>
  <!-- Permissoes extras -->
  <div class="card mt-3">
    <div class="card-header fw-semibold">Permissões Extras</div>
    <div class="card-body">
      <?php foreach($permissoesExtra??[] as $p): ?>
      <div class="d-flex justify-content-between align-items-center mb-2">
        <span><?= h($permissoesDisp[$p['permissao']]??$p['permissao']) ?></span>
        <form method="POST" action="/usuarios/<?= $u2['id'] ?>/acesso"><?= csrf_field() ?><input type="hidden" name="acao" value="revogar_perm"><input type="hidden" name="perm_id" value="<?= $p['id'] ?>"><button class="btn btn-sm btn-outline-danger">Revogar</button></form>
      </div>
      <?php endforeach; ?>
      <form method="POST" action="/usuarios/<?= $u2['id'] ?>/acesso" class="d-flex gap-2 mt-2">
        <?= csrf_field() ?><input type="hidden" name="acao" value="permissao">
        <select name="permissao" class="form-select form-select-sm"><?php foreach($permissoesDisp as $k=>$v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?></select>
        <button class="btn btn-sm btn-success">Conceder</button>
      </form>
    </div>
  </div>
  <?php endif; ?>
</div></div>
