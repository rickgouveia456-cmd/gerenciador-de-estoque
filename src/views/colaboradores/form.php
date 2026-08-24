<div class="row justify-content-center"><div class="col-md-7">
  <div class="card"><div class="card-header"><h6 class="mb-0">Editar Colaborador</h6></div>
  <div class="card-body"><form method="POST" action="/colaboradores/<?= $c['id'] ?>/editar">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-8"><label class="form-label fw-semibold">Nome *</label><input type="text" name="nome" class="form-control" required value="<?= h($c['nome']) ?>"></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Função</label><input type="text" name="funcao" class="form-control" value="<?= h($c['funcao']??'') ?>"></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Escopo</label><select name="escopo" class="form-select"><option value="">—</option><option value="estrutura" <?= ($c['escopo']??'')==='estrutura'?'selected':'' ?>>Estrutura</option><option value="acabamento" <?= ($c['escopo']??'')==='acabamento'?'selected':'' ?>>Acabamento</option><option value="infraestrutura" <?= ($c['escopo']??'')==='infraestrutura'?'selected':'' ?>>Infraestrutura</option></select></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Obra</label><input type="text" name="obra" class="form-control" value="<?= h($c['obra']??'') ?>"></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Cidade</label><input type="text" name="cidade" class="form-control" value="<?= h($c['cidade']??'') ?>"></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Tipo</label><select name="tipo" class="form-select"><option value="peao" <?= ($c['tipo']??'peao')==='peao'?'selected':'' ?>>Peão</option><option value="tecnico" <?= ($c['tipo']??'')==='tecnico'?'selected':'' ?>>Técnico</option></select></div>
      <div class="col-md-4"><div class="form-check mt-4"><input type="checkbox" name="ativo" class="form-check-input" <?= $c['ativo']?'checked':'' ?>><label class="form-check-label">Ativo</label></div></div>
    </div>
    <div class="d-flex gap-2 mt-3"><button type="submit" class="btn btn-primary">Salvar</button><a href="/colaboradores" class="btn btn-outline-secondary">Cancelar</a></div>
  </form></div></div>
</div></div>
