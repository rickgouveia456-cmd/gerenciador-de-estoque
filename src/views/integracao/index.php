<div class="page-header d-flex align-items-center gap-3 mb-4">
  <div class="page-header-icon" style="background:linear-gradient(135deg,#7c3aed,#a78bfa)">
    <i class="bi bi-plug-fill"></i>
  </div>
  <div>
    <h1 class="page-title">Integrações</h1>
    <p class="page-sub">Conecte o Logi-Prime com sistemas externos</p>
  </div>
</div>

<!-- ── STATUS SIENGE ─────────────────────────────────────────────────────── -->
<div class="row g-4 mb-4">
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
          <span style="font-size:1.8rem">🔗</span>
          <div>
            <h5 class="mb-0 fw-bold">Sienge</h5>
            <small class="text-muted">ERP de Construção Civil</small>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <?php if ($ativo): ?>
            <span class="badge bg-success px-3 py-2"><i class="bi bi-check-circle me-1"></i>Ativo</span>
          <?php else: ?>
            <span class="badge bg-secondary px-3 py-2"><i class="bi bi-x-circle me-1"></i>Inativo</span>
          <?php endif; ?>
          <button class="btn btn-outline-primary btn-sm" onclick="testarConexao()" id="btnTestar">
            <i class="bi bi-wifi me-1"></i> Testar Conexão
          </button>
        </div>
      </div>
      <div class="card-body">

        <!-- Ações de sincronização -->
        <?php if ($ativo): ?>
        <div class="d-flex gap-2 flex-wrap mb-4">
          <button class="btn btn-sm btn-outline-success" onclick="sincronizar('materiais')">
            <i class="bi bi-arrow-repeat me-1"></i> Sincronizar Materiais
          </button>
          <button class="btn btn-sm btn-outline-info" onclick="sincronizar('estoque')" disabled title="Em breve">
            <i class="bi bi-box me-1"></i> Enviar Estoque
          </button>
          <span class="badge bg-warning text-dark align-self-center">
            <i class="bi bi-tools me-1"></i> Endpoints pendentes — aguardando acesso à API
          </span>
        </div>
        <?php else: ?>
        <div class="alert alert-warning mb-4">
          <i class="bi bi-exclamation-triangle me-2"></i>
          <strong>Integração desativada.</strong> Configure as credenciais abaixo e ative para habilitar a sincronização.
        </div>
        <?php endif; ?>

        <!-- Formulário de configuração -->
        <form method="POST" action="/integracao/configurar">
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">URL Base da API <span class="text-danger">*</span></label>
              <input type="url" name="base_url" class="form-control"
                     value="<?= h($cfg['base_url'] ?? '') ?>"
                     placeholder="https://api.sienge.com.br">
              <div class="form-text">Ex: https://api.sienge.com.br/v1</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Token de Acesso <span class="text-danger">*</span></label>
              <input type="password" name="token" class="form-control"
                     value="<?= h($cfg['token'] ?? '') ?>"
                     placeholder="Bearer token da API do Sienge">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">ID da Empresa</label>
              <input type="text" name="empresa_id" class="form-control"
                     value="<?= h($cfg['empresa_id'] ?? '') ?>"
                     placeholder="Ex: 1">
            </div>
            <div class="col-md-8">
              <label class="form-label fw-semibold d-block">Sincronizações automáticas</label>
              <div class="d-flex gap-4 mt-1">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="sync_materiais" id="ckMat"
                         <?= !empty($cfg['sync_materiais']) ? 'checked' : '' ?>>
                  <label class="form-check-label" for="ckMat">Materiais / Catálogo</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="sync_requisicoes" id="ckReq"
                         <?= !empty($cfg['sync_requisicoes']) ? 'checked' : '' ?>>
                  <label class="form-check-label" for="ckReq">Requisições</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="sync_estoques" id="ckEst"
                         <?= !empty($cfg['sync_estoques']) ? 'checked' : '' ?>>
                  <label class="form-check-label" for="ckEst">Estoques</label>
                </div>
              </div>
            </div>
            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="ativo" id="ckAtivo"
                       <?= !empty($cfg['ativo']) ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="ckAtivo">
                  Ativar integração com Sienge
                </label>
              </div>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Salvar Configuração
              </button>
            </div>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>

<!-- ── LOG DE INTEGRAÇÕES ───────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
  <div class="card-header d-flex align-items-center justify-content-between">
    <h5 class="mb-0 fw-bold"><i class="bi bi-journal-text me-2 text-muted"></i>Log de Sincronizações</h5>
    <div class="d-flex gap-2">
      <span class="badge bg-success">✅ <?= $contagens['sucesso'] ?? 0 ?> sucesso</span>
      <span class="badge bg-danger">❌ <?= $contagens['erro'] ?? 0 ?> erro</span>
      <span class="badge bg-warning text-dark">⏳ <?= $contagens['pendente'] ?? 0 ?> pendente</span>
    </div>
  </div>
  <div class="card-body p-0">
    <?php if (empty($logs)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
        Nenhuma sincronização registrada ainda.
      </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Data</th>
            <th>Sistema</th>
            <th>Operação</th>
            <th>Status</th>
            <th>Erro</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $log): ?>
          <tr>
            <td class="text-nowrap small"><?= h(date('d/m/Y H:i', strtotime($log['criado_em']))) ?></td>
            <td><span class="badge bg-secondary"><?= h($log['sistema']) ?></span></td>
            <td class="small"><?= h($log['operacao']) ?></td>
            <td>
              <?php if ($log['status'] === 'sucesso'): ?>
                <span class="badge bg-success">✅ Sucesso</span>
              <?php elseif ($log['status'] === 'erro'): ?>
                <span class="badge bg-danger">❌ Erro</span>
              <?php else: ?>
                <span class="badge bg-warning text-dark">⏳ Pendente</span>
              <?php endif; ?>
            </td>
            <td class="small text-danger"><?= h($log['erro_msg'] ?? '—') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
function testarConexao() {
  const btn = document.getElementById('btnTestar');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Testando...';

  fetch('/integracao/testar')
    .then(r => r.json())
    .then(data => {
      alert(data.ok ? '✅ ' + data.msg : '❌ ' + data.msg);
    })
    .catch(() => alert('❌ Erro ao conectar com o servidor.'))
    .finally(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-wifi me-1"></i> Testar Conexão';
    });
}

function sincronizar(acao) {
  if (!confirm(`Iniciar sincronização de ${acao}?`)) return;

  const body = new FormData();
  body.append('acao', acao);
  body.append('csrf_token', document.querySelector('meta[name=csrf-token]')?.content ?? '');

  fetch('/integracao/sincronizar', { method: 'POST', body })
    .then(r => r.json())
    .then(data => {
      alert(data.ok ? '✅ ' + (data.msg ?? 'Concluído!') : '❌ ' + (data.msg ?? 'Erro.'));
      if (data.ok) location.reload();
    })
    .catch(() => alert('❌ Erro na sincronização.'));
}
</script>
