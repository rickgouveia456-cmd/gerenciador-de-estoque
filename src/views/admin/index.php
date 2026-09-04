<div class="mb-3"><a href="/" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Dashboard</a></div><h5 class="fw-bold mb-3"><i class="bi bi-sliders me-2"></i>Painel Admin</h5>
<div class="row g-3 mb-4">
  <?php foreach([['Usuários Ativos',$totalUsuarios,'people','info'],['Almoxarifados',$totalAlm,'buildings','accent'],['Itens Ativos',$totalItens,'box-seam','success'],['Movimentações',$totalMov,'arrow-left-right','warning']] as [$lbl,$n,$ico,$c]): ?>
  <div class="col-6 col-md-3"><div class="card p-3 text-center"><div class="fs-2 fw-bold" style="color:var(--<?= $c ?>)"><?= $n ?></div><div class="text-muted small"><?= $lbl ?></div></div></div>
  <?php endforeach; ?>
</div>
<div class="row g-3">
  <div class="col-md-4"><a href="/usuarios" class="card p-3 text-decoration-none text-dark card-stat d-flex flex-row align-items-center gap-3"><div class="rounded-3 p-2" style="background:var(--info-light)"><i class="bi bi-person-gear fs-4 text-info"></i></div><div><div class="fw-semibold">Usuários</div><div class="text-muted small">Gerenciar acessos</div></div></a></div>
  <div class="col-md-4"><a href="/admin/reativar_itens" class="card p-3 text-decoration-none text-dark card-stat d-flex flex-row align-items-center gap-3"><div class="rounded-3 p-2" style="background:var(--warning-light)"><i class="bi bi-arrow-counterclockwise fs-4 text-warning"></i></div><div><div class="fw-semibold">Itens Desativados</div><div class="text-muted small">Reativar ou excluir</div></div></a></div>
  <div class="col-md-4">
    <form method="POST" action="/admin/limpar-catalogo" onsubmit="return confirm('Remover todos os registros com nome corrompido/ilegível do catálogo?')">
      <?= csrf_field() ?>
      <button type="submit" class="card p-3 text-decoration-none text-dark card-stat d-flex flex-row align-items-center gap-3 w-100 border-0 text-start">
        <div class="rounded-3 p-2" style="background:var(--danger-light)"><i class="bi bi-trash3 fs-4 text-danger"></i></div>
        <div><div class="fw-semibold">Limpar Catálogo</div><div class="text-muted small">Remover registros corrompidos</div></div>
      </button>
    </form>
  </div>
</div>
