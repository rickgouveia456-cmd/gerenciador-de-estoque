<?php /* views/usuarios/index.php */ ?>
<?php
// Cores e ícones por perfil
$perfilConfig = [
    'admin'             => ['cor' => '#7c3aed', 'icone' => 'bi-shield-lock-fill',    'label' => 'Admin',          'label_curto' => 'Admin'],
    'almoxarife'        => ['cor' => '#ff6b35', 'icone' => 'bi-boxes',               'label' => 'Almoxarife',     'label_curto' => 'Almoxarife'],
    'mestre'            => ['cor' => '#f0a500', 'icone' => 'bi-person-badge-fill',   'label' => 'Mestre de Obra', 'label_curto' => 'Mestre'],
    'tecnico_seguranca' => ['cor' => '#059669', 'icone' => 'bi-shield-check-fill',   'label' => 'Téc. Segurança', 'label_curto' => 'Tec. Seg.'],
    'analista'          => ['cor' => '#2563eb', 'icone' => 'bi-graph-up',            'label' => 'Analista',       'label_curto' => 'Analista'],
    'colaborador'       => ['cor' => '#64748b', 'icone' => 'bi-person-fill',         'label' => 'Colaborador',    'label_curto' => 'Colaborador'],
    'engenheiro'        => ['cor' => '#0891b2', 'icone' => 'bi-wrench-adjustable',   'label' => 'Engenheiro',     'label_curto' => 'Engenheiro'],
];

// Contar ativos por perfil e total geral
$totalGeral = 0;
foreach ($grupos as $perfil => $grupo) {
    $totalGeral += count($grupo['usuarios']);
}

function avatarIniciais(string $nome): string {
    $partes = explode(' ', trim($nome));
    if (count($partes) === 1) return strtoupper(mb_substr($partes[0], 0, 2));
    return strtoupper(mb_substr($partes[0], 0, 1) . mb_substr(end($partes), 0, 1));
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="fw-bold mb-1"><i class="bi bi-people me-2"></i>Usuários</h5>
    <div class="text-muted small"><?= $totalGeral ?> usuário(s) cadastrado(s)</div>
  </div>
  <a href="/usuarios/novo" class="btn btn-primary btn-sm">
    <i class="bi bi-plus me-1"></i>Novo Usuário
  </a>
</div>

<!-- Cards de stats por perfil -->
<div class="row g-3 mb-4">
  <?php foreach ($grupos as $perfil => $grupo): if (empty($grupo['usuarios'])) continue;
    $cfg = $perfilConfig[$perfil] ?? ['cor' => '#64748b', 'icone' => 'bi-person', 'label' => ucfirst($perfil), 'label_curto' => ucfirst($perfil)];
    $nAtivos = count(array_filter($grupo['usuarios'], fn($u2) => $u2['ativo']));
  ?>
  <div class="col-6 col-md-4 col-lg-3 col-xl-2">
    <div class="card p-3 card-stat-perfil"
         style="cursor:pointer;border-top:3px solid <?= $cfg['cor'] ?> !important"
         onclick="filtrarPerfil('<?= $perfil ?>')"
         data-perfil="<?= $perfil ?>">
      <div class="d-flex align-items-center gap-2 mb-2">
        <div class="rounded-2 d-flex align-items-center justify-content-center"
             style="width:32px;height:32px;background:<?= $cfg['cor'] ?>20;flex-shrink:0">
          <i class="<?= $cfg['icone'] ?>" style="color:<?= $cfg['cor'] ?>;font-size:1rem"></i>
        </div>
        <div class="fw-bold fs-4 mb-0" style="color:<?= $cfg['cor'] ?>"><?= count($grupo['usuarios']) ?></div>
      </div>
      <div class="fw-semibold small"><?= h($cfg['label_curto']) ?></div>
      <div class="text-muted" style="font-size:0.72rem"><?= $nAtivos ?> ativo(s)</div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Barra de filtros + busca -->
<div class="card p-3 mb-4">
  <div class="d-flex flex-wrap gap-2 align-items-center">
    <!-- Pills de perfil -->
    <div class="d-flex flex-wrap gap-1 flex-grow-1" id="pillsPerfil">
      <button class="btn btn-sm rounded-pill active-pill" id="pill-todos"
              onclick="filtrarPerfil('todos')"
              style="background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;font-size:0.8rem">
        Todos (<?= $totalGeral ?>)
      </button>
      <?php foreach ($grupos as $perfil => $grupo): if (empty($grupo['usuarios'])) continue;
        $cfg = $perfilConfig[$perfil] ?? ['cor' => '#64748b', 'label_curto' => ucfirst($perfil)];
      ?>
      <button class="btn btn-sm rounded-pill pill-perfil"
              id="pill-<?= $perfil ?>"
              onclick="filtrarPerfil('<?= $perfil ?>')"
              data-perfil="<?= $perfil ?>"
              style="background:<?= $cfg['cor'] ?>18;color:<?= $cfg['cor'] ?>;border:1px solid <?= $cfg['cor'] ?>40;font-size:0.8rem">
        <?= h($cfg['label_curto']) ?> (<?= count($grupo['usuarios']) ?>)
      </button>
      <?php endforeach; ?>
    </div>
    <!-- Busca -->
    <div class="input-group input-group-sm" style="max-width:280px">
      <span class="input-group-text"><i class="bi bi-search"></i></span>
      <input type="text" id="buscaUsuario" class="form-control"
             placeholder="Buscar nome ou login..."
             oninput="buscarUsuario(this.value)">
    </div>
  </div>
</div>

<!-- Grid de cards -->
<div class="row g-3" id="gridUsuarios">
  <?php
  $todosList = [];
  foreach ($grupos as $perfil => $grupo) {
      foreach ($grupo['usuarios'] as $u2) {
          $u2['_perfil_key'] = $perfil;
          $todosList[] = $u2;
      }
  }
  foreach ($todosList as $u2):
    $perfil = $u2['_perfil_key'];
    $cfg = $perfilConfig[$perfil] ?? ['cor' => '#64748b', 'icone' => 'bi-person', 'label' => ucfirst($perfil), 'label_curto' => ucfirst($perfil)];
    $iniciais = avatarIniciais($u2['nome']);
    $buscaAttr = strtolower($u2['nome'] . ' ' . $u2['login']);
  ?>
  <div class="col-12 col-sm-6 col-lg-4 col-xl-3 card-usuario"
       data-perfil="<?= $perfil ?>"
       data-busca="<?= h($buscaAttr) ?>">
    <div class="card card-usuario-inner h-100"
         style="border-top:3px solid <?= $cfg['cor'] ?> !important;transition:all 0.15s">
      <div class="card-body p-3">
        <!-- Avatar + nome -->
        <div class="d-flex align-items-start gap-3 mb-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
               style="width:46px;height:46px;background:<?= $cfg['cor'] ?>;font-size:1rem;letter-spacing:1px">
            <?= h($iniciais) ?>
          </div>
          <div class="flex-grow-1 overflow-hidden">
            <div class="fw-bold text-truncate" style="font-size:0.95rem"><?= h($u2['nome']) ?></div>
            <div class="font-monospace text-muted" style="font-size:0.75rem"><?= h($u2['login']) ?></div>
          </div>
        </div>

        <!-- Badges -->
        <div class="d-flex flex-wrap gap-1 mb-2">
          <span class="badge rounded-pill"
                style="background:<?= $cfg['cor'] ?>18;color:<?= $cfg['cor'] ?>;border:1px solid <?= $cfg['cor'] ?>40;font-size:0.72rem">
            <i class="<?= $cfg['icone'] ?> me-1" style="font-size:0.65rem"></i><?= h($cfg['label_curto']) ?>
          </span>
          <?php if ($u2['ativo']): ?>
          <span class="badge rounded-pill bg-success" style="font-size:0.72rem">Ativo</span>
          <?php else: ?>
          <span class="badge rounded-pill bg-secondary" style="font-size:0.72rem">Inativo</span>
          <?php endif; ?>
          <?php if (!empty($u2['pode_requisitar'])): ?>
          <span class="badge rounded-pill" style="background:#ff6b3518;color:#ff6b35;border:1px solid #ff6b3540;font-size:0.72rem">
            <i class="bi bi-clipboard-check me-1" style="font-size:0.65rem"></i>Requisições
          </span>
          <?php endif; ?>
        </div>

        <!-- Almoxarifado -->
        <?php if (!empty($u2['alm_nome'])): ?>
        <div class="text-muted d-flex align-items-center gap-1" style="font-size:0.78rem">
          <i class="bi bi-building" style="color:var(--accent)"></i>
          <span class="text-truncate"><?= h($u2['alm_nome']) ?></span>
        </div>
        <?php else: ?>
        <div class="text-muted" style="font-size:0.78rem">
          <i class="bi bi-dash-circle me-1"></i>Sem almoxarifado
        </div>
        <?php endif; ?>
        <?php if (!empty($u2['regiao'])): ?>
        <div class="text-muted d-flex align-items-center gap-1 mt-1" style="font-size:0.75rem">
          <i class="bi bi-geo-alt" style="color:var(--accent)"></i>
          <span><?= h($u2['regiao']) ?></span>
        </div>
        <?php endif; ?>
      </div>

      <!-- Ações -->
      <div class="card-footer bg-transparent border-top py-2 px-3 d-flex gap-2 justify-content-end">
        <a href="/usuarios/<?= $u2['id'] ?>/editar"
           class="btn btn-sm btn-outline-primary"
           title="Editar">
          <i class="bi bi-pencil me-1"></i>Editar
        </a>
        <form method="POST" action="/usuarios/<?= $u2['id'] ?>/deletar" class="m-0">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-danger"
                  title="Remover"
                  onclick="return confirm('Remover usuário <?= h(addslashes($u2['nome'])) ?>?')">
            <i class="bi bi-trash"></i>
          </button>
        </form>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Mensagem vazio -->
<div id="semResultados" class="text-center py-5 d-none">
  <i class="bi bi-person-x fs-1 text-muted mb-3"></i>
  <div class="fw-semibold text-muted">Nenhum usuário encontrado.</div>
</div>

<style>
.card-usuario-inner:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important;
}
.card-stat-perfil:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,0,0,0.1) !important;
}
</style>

<script>
let _filtroPerfilAtivo = 'todos';
let _filtroBusca = '';

function filtrarPerfil(perfil) {
  _filtroPerfilAtivo = perfil;
  // Atualizar pills
  document.querySelectorAll('.pill-perfil, #pill-todos').forEach(el => {
    el.style.fontWeight = '';
    el.style.boxShadow = '';
  });
  const pilAtivo = document.getElementById('pill-' + perfil) || document.getElementById('pill-todos');
  if (pilAtivo) {
    pilAtivo.style.fontWeight = '700';
    pilAtivo.style.boxShadow = '0 0 0 2px currentColor';
  }
  // Atualizar cards dos stats
  document.querySelectorAll('.card-stat-perfil').forEach(c => {
    c.style.opacity = (perfil === 'todos' || c.dataset.perfil === perfil) ? '1' : '0.4';
  });
  aplicarFiltros();
}

function buscarUsuario(q) {
  _filtroBusca = q.toLowerCase().trim();
  aplicarFiltros();
}

function aplicarFiltros() {
  const cards = document.querySelectorAll('.card-usuario');
  let visiveis = 0;
  cards.forEach(card => {
    const perfilMatch = _filtroPerfilAtivo === 'todos' || card.dataset.perfil === _filtroPerfilAtivo;
    const buscaMatch  = !_filtroBusca || card.dataset.busca.includes(_filtroBusca);
    if (perfilMatch && buscaMatch) {
      card.style.display = '';
      visiveis++;
    } else {
      card.style.display = 'none';
    }
  });
  const msg = document.getElementById('semResultados');
  msg.classList.toggle('d-none', visiveis > 0);
}
</script>
