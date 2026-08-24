<?php /* views/admin/reativar_itens.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0"><i class="bi bi-arrow-counterclockwise me-2"></i>Itens Desativados</h5>
  <div class="d-flex gap-2">
    <form method="GET">
      <select name="alm" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">Todos os almoxarifados</option>
        <?php foreach ($almoxarifados as $a): ?>
        <option value="<?= $a['id'] ?>" <?= $almId==$a['id'] ? 'selected' : '' ?>><?= h($a['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Item</th>
          <th>Almoxarifado</th>
          <th>Codigo</th>
          <th>Categoria</th>
          <th class="text-center">Acoes</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($itens)): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">Nenhum item desativado.</td></tr>
      <?php else: ?>
      <?php foreach ($itens as $it): ?>
        <tr>
          <td><?= h($it['nome']) ?></td>
          <td class="text-muted small"><?= h($it['alm_nome']) ?></td>
          <td class="font-monospace small"><?= h($it['codigo']) ?></td>
          <td><span class="badge bg-secondary"><?= categoria_label($it['categoria'] ?? 'geral') ?></span></td>
          <td class="text-center">
            <form method="POST" action="/admin/reativar_item/<?= $it['id'] ?>" class="d-inline">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-success" title="Reativar">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reativar
              </button>
            </form>
            <form method="POST" action="/admin/deletar_item/<?= $it['id'] ?>" class="d-inline"
                  onsubmit="return confirm('Excluir permanentemente? Esta acao nao pode ser desfeita.')">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-danger" title="Excluir permanentemente">
                <i class="bi bi-trash me-1"></i>Excluir
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
