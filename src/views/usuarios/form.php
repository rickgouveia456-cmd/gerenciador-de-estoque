<div class="mb-3"><a href="/usuarios" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a></div>
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
      <div class="col-md-12"><label class="form-label fw-semibold">Perfil *</label>
        <input type="hidden" name="perfil" id="perfilInput" value="<?= h($u2['perfil'] ?? 'colaborador') ?>" required>
        <div class="d-flex flex-wrap gap-2" id="perfilCards">
          <?php
          $perfisOpcoes = [
            'admin'             => ['cor'=>'#7c3aed','icone'=>'bi-shield-lock-fill',  'label'=>'Admin'],
            'almoxarife'        => ['cor'=>'#ff6b35','icone'=>'bi-boxes',             'label'=>'Almoxarife'],
            'mestre'            => ['cor'=>'#f0a500','icone'=>'bi-person-badge-fill', 'label'=>'Mestre'],
            'tecnico_seguranca' => ['cor'=>'#059669','icone'=>'bi-shield-check-fill', 'label'=>'Tec. Seg.'],
            'analista'          => ['cor'=>'#2563eb','icone'=>'bi-graph-up',          'label'=>'Analista'],
            'assistente'        => ['cor'=>'#0891b2','icone'=>'bi-clipboard2-check',  'label'=>'Assistente'],
            'engenheiro'        => ['cor'=>'#7c3aed','icone'=>'bi-building-gear',     'label'=>'Engenheiro'],
            'colaborador'       => ['cor'=>'#64748b','icone'=>'bi-person-fill',       'label'=>'Colaborador'],
          ];
          $perfilAtual = $u2['perfil'] ?? 'colaborador';
          foreach ($perfisOpcoes as $pKey => $pCfg):
            $isAtivo = $perfilAtual === $pKey;
          ?>
          <button type="button"
                  class="btn perfil-card <?= $isAtivo ? 'perfil-card-ativo' : '' ?>"
                  data-perfil="<?= $pKey ?>"
                  onclick="selecionarPerfil('<?= $pKey ?>')"
                  style="
                    border: 2px solid <?= $pCfg['cor'] ?>;
                    color: <?= $isAtivo ? '#fff' : $pCfg['cor'] ?>;
                    background: <?= $isAtivo ? $pCfg['cor'] : 'transparent' ?>;
                    border-radius: 8px;
                    padding: 8px 14px;
                    font-size: 0.82rem;
                    font-weight: 600;
                    transition: all 0.15s;
                    min-width: 110px;
                  ">
            <i class="bi <?= $pCfg['icone'] ?> me-1"></i><?= $pCfg['label'] ?>
          </button>
          <?php endforeach; ?>
        </div>
        <div class="form-text mt-1">Clique para selecionar o perfil do usuário</div>
      </div>
      <div class="col-md-8"><label class="form-label fw-semibold">Almoxarifado</label><select name="almoxarifado_id" class="form-select"><option value="">—</option><?php foreach($almoxarifados as $a): ?><option value="<?= $a['id'] ?>" <?= ($u2['almoxarifado_id']??0)==$a['id']?'selected':'' ?>><?= h($a['nome']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Região / Obra</label>
        <input type="text" name="regiao" class="form-control"
               placeholder="Ex: Obra Patamares, Norte..."
               value="<?= h($u2['regiao'] ?? '') ?>">
        <div class="form-text">Identifica qual obra ou região este usuário pertence</div>
      </div>
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

<script>
function selecionarPerfil(perfil) {
  document.getElementById('perfilInput').value = perfil;
  const perfisConfig = {
    'admin':             '#7c3aed',
    'almoxarife':        '#ff6b35',
    'mestre':            '#f0a500',
    'tecnico_seguranca': '#059669',
    'analista':          '#2563eb',
    'assistente':        '#0891b2',
    'engenheiro':        '#7c3aed',
    'colaborador':       '#64748b',
  };
  document.querySelectorAll('.perfil-card').forEach(btn => {
    const p = btn.dataset.perfil;
    const cor = perfisConfig[p] || '#64748b';
    btn.style.background = p === perfil ? cor : 'transparent';
    btn.style.color = p === perfil ? '#fff' : cor;
    btn.classList.toggle('perfil-card-ativo', p === perfil);
  });
}
</script>
