<?php /* views/kits/index.php */ ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div class="d-flex align-items-center gap-2">
    <a href="/almoxarifado/<?= $almId ?>" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Voltar ao Almoxarifado
    </a>
    <h5 class="fw-bold mb-0">📦 Kits — <?= h($alm["nome"]) ?></h5>
  </div>
  <?php if(in_array($u["perfil"],["admin","almoxarife"])): ?>
  <a href="/almoxarifado/<?= $almId ?>/kits/novo" class="btn btn-primary btn-sm">
    <i class="bi bi-plus-lg me-1"></i>Novo Kit
  </a>
  <?php endif; ?>
</div>

<!-- Busca -->
<div class="mb-3">
  <div class="input-group">
    <span class="input-group-text"><i class="bi bi-search text-muted"></i></span>
    <input type="text" id="buscaKit" class="form-control" placeholder="Pesquisar kit..." oninput="filtrarKits(this.value)">
    <button class="btn btn-outline-secondary" onclick="document.getElementById('buscaKit').value='';filtrarKits('')"><i class="bi bi-x-lg"></i></button>
  </div>
</div>

<?php if(empty($kits)): ?>
<div class="card"><div class="text-center py-5 text-muted">
  <i class="bi bi-box2-heart fs-1 d-block mb-2" style="color:var(--accent)"></i>
  <p class="mb-1 fw-semibold">Nenhum kit cadastrado ainda.</p>
  <?php if(in_array($u["perfil"],["admin","almoxarife"])): ?>
  <a href="/almoxarifado/<?= $almId ?>/kits/novo" class="btn btn-primary btn-sm mt-2"><i class="bi bi-plus-lg me-1"></i>Criar primeiro kit</a>
  <?php endif; ?>
</div></div>
<?php else: ?>
<div class="row g-3" id="gridKits">
  <?php foreach($kits as $kit): ?>
  <div class="col-12 col-md-6 col-xl-4 kit-card" data-busca="<?= strtolower(h($kit["nome"])) ?>">
    <div class="card h-100 shadow-sm">
      <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <h6 class="fw-bold mb-0" style="color:var(--accent)"><i class="bi bi-box2-heart me-1"></i><?= h($kit["nome"]) ?></h6>
            <?php if($kit["descricao"]): ?><small class="text-muted"><?= h($kit["descricao"]) ?></small><?php endif; ?>
          </div>
          <span class="badge rounded-pill bg-secondary ms-2"><?= count($kit["_itens"]) ?> item(ns)</span>
        </div>
        <?php if(!empty($kit["_itens"])): ?>
        <ul class="list-unstyled mb-3 flex-fill" style="font-size:0.85rem">
          <?php foreach($kit["_itens"] as $ki): ?>
          <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
            <span class="text-truncate me-2"><i class="bi bi-dot" style="color:var(--accent)"></i><?= h($ki["item_nome"]) ?></span>
            <span class="badge bg-light text-dark border"><?= fmt_qtd((float)$ki["quantidade"]) ?> <?= h($ki["unidade"]) ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p class="text-muted small flex-fill">Nenhum item no kit.</p>
        <?php endif; ?>
        <?php if(in_array($u["perfil"],["admin","almoxarife"])): ?>
        <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
          <small class="text-muted"><i class="bi bi-person me-1"></i><?= h($kit["criado_por"]??"—") ?></small>
          <div class="d-flex gap-2">
            <a href="/almoxarifado/<?= $almId ?>/kits/<?= $kit["id"] ?>/editar" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
            <form method="POST" action="/almoxarifado/<?= $almId ?>/kits/<?= $kit["id"] ?>/excluir" onsubmit="return confirm('Remover kit?')"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<div id="semKits" class="text-center py-5 text-muted" style="display:none"><i class="bi bi-search fs-2 d-block mb-2"></i>Nenhum kit encontrado.</div>
<?php endif; ?>

<script>
function filtrarKits(q){
  q=q.toLowerCase();
  let found=0;
  document.querySelectorAll(".kit-card").forEach(c=>{
    const v=c.dataset.busca||"";
    c.style.display=(!q||v.includes(q))?"":"none";
    if(!q||v.includes(q)) found++;
  });
  document.getElementById("semKits").style.display=found===0&&q?"":"none";
}
</script>