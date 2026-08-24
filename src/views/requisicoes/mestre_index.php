<?php /* views/requisicoes/mestre_index.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0"><i class="bi bi-clipboard-check me-2"></i>Requisições</h5>
  <?php if (in_array($u['perfil'], ['mestre','tecnico_seguranca','admin','almoxarife']) || $u['pode_requisitar']): ?>
  <a href="/requisicoes/mestre/nova" class="btn btn-primary btn-sm">
    <i class="bi bi-plus me-1"></i>Nova Requisição
  </a>
  <?php endif; ?>
</div>

<!-- Filtros -->
<form method="GET" class="card p-3 mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-5">
      <input type="text" name="busca" class="form-control form-control-sm"
             placeholder="Buscar por colaborador ou protocolo..." value="<?= h($busca) ?>">
    </div>
    <div class="col-md-3">
      <select name="status" class="form-select form-select-sm">
        <option value="">Todos os status</option>
        <?php foreach (['pendente'=>'Pendente','aprovada'=>'Aprovada','recusada'=>'Recusada','entregue'=>'Entregue'] as $v=>$l): ?>
        <option value="<?= $v ?>" <?= $status===$v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary btn-sm w-100">Filtrar</button>
    </div>
    <div class="col-md-2">
      <a href="/requisicoes/mestre" class="btn btn-outline-secondary btn-sm w-100">Limpar</a>
    </div>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Protocolo</th>
          <th>Colaborador</th>
          <th>Almoxarifado</th>
          <th>Data</th>
          <th class="text-center">Status</th>
          <th class="text-center">Ações</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($requisicoes)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma requisição encontrada.</td></tr>
      <?php endif; ?>
      <?php foreach ($requisicoes as $r):
        $statusMap = [
            'pendente' => ['warning', 'clock', 'Pendente'],
            'aprovada' => ['info',    'check-circle', 'Aprovada'],
            'recusada' => ['danger',  'x-circle', 'Recusada'],
            'entregue' => ['success', 'bag-check', 'Entregue'],
        ];
        [$cls, $ico, $lbl] = $statusMap[$r['status']] ?? ['secondary','question','?'];
      ?>
        <tr>
          <td class="font-monospace small"><?= h($r['protocolo'] ?? '—') ?></td>
          <td class="fw-semibold"><?= h($r['colaborador']) ?></td>
          <td class="text-muted small"><?= h($r['alm_nome']) ?></td>
          <td class="text-muted small"><?= fmt_data($r['data_criacao'], 'd/m/Y H:i') ?></td>
          <td class="text-center">
            <span class="badge bg-<?= $cls ?>">
              <i class="bi bi-<?= $ico ?> me-1"></i><?= $lbl ?>
            </span>
          </td>
          <td class="text-center">
            <a href="/requisicoes/mestre/<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pag['total_pages'] > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center">
    <small class="text-muted"><?= $total ?> resultado(s)</small>
    <nav>
      <ul class="pagination pagination-sm mb-0">
        <?php for ($p = 1; $p <= $pag['total_pages']; $p++): ?>
        <li class="page-item <?= $p === $pag['page'] ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $p ?>&status=<?= h($status) ?>&busca=<?= h($busca) ?>">
            <?= $p ?>
          </a>
        </li>
        <?php endfor; ?>
      </ul>
    </nav>
  </div>
  <?php endif; ?>
</div>
