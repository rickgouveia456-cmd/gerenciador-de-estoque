<div class="page-header d-flex align-items-center gap-3 mb-4">
  <div class="page-header-icon" style="background:linear-gradient(135deg,#f97316,#fb923c)">
    <i class="bi bi-gear-fill"></i>
  </div>
  <div>
    <h1 class="page-title">Configuração do Sistema</h1>
    <p class="page-sub">Parametrizações iniciais — 3.7.1</p>
  </div>
  <?php if ($config['onboarding_feito'] === '1'): ?>
  <span class="badge bg-success ms-auto px-3 py-2"><i class="bi bi-check-circle me-1"></i>Configurado</span>
  <?php else: ?>
  <span class="badge bg-warning text-dark ms-auto px-3 py-2"><i class="bi bi-exclamation-triangle me-1"></i>Configuração pendente</span>
  <?php endif; ?>
</div>

<form method="POST" action="/admin/configuracao" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="row g-4">

    <!-- ── Dados da Empresa ───────────────────────────────────────────────── -->
    <div class="col-12">
      <div class="card border-0 shadow-sm">
        <div class="card-header fw-bold">
          <i class="bi bi-building me-2 text-muted"></i>Dados da Empresa
        </div>
        <div class="card-body">
          <div class="row g-3">

            <div class="col-md-6">
              <label class="form-label fw-semibold">Nome da Empresa <span class="text-danger">*</span></label>
              <input type="text" name="empresa_nome" class="form-control"
                     value="<?= h($config['empresa_nome']) ?>" required
                     placeholder="Ex: Stanza Construtora">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">CNPJ</label>
              <input type="text" name="empresa_cnpj" class="form-control"
                     value="<?= h($config['empresa_cnpj']) ?>"
                     placeholder="00.000.000/0001-00">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold">Cidade</label>
              <input type="text" name="empresa_cidade" class="form-control"
                     value="<?= h($config['empresa_cidade']) ?>"
                     placeholder="Ex: Salvador">
            </div>

            <div class="col-md-2">
              <label class="form-label fw-semibold">Estado</label>
              <input type="text" name="empresa_estado" class="form-control"
                     value="<?= h($config['empresa_estado']) ?>"
                     placeholder="BA" maxlength="2">
            </div>

            <div class="col-md-3">
              <label class="form-label fw-semibold">Telefone</label>
              <input type="text" name="empresa_telefone" class="form-control"
                     value="<?= h($config['empresa_telefone']) ?>"
                     placeholder="(71) 99999-9999">
            </div>

            <div class="col-md-3">
              <label class="form-label fw-semibold">E-mail</label>
              <input type="email" name="empresa_email" class="form-control"
                     value="<?= h($config['empresa_email']) ?>"
                     placeholder="contato@empresa.com.br">
            </div>

            <!-- Logo -->
            <div class="col-12">
              <label class="form-label fw-semibold">Logo da Empresa</label>
              <div class="d-flex align-items-center gap-4 flex-wrap">
                <?php if ($config['empresa_logo_b64']): ?>
                <img src="<?= h($config['empresa_logo_b64']) ?>" alt="Logo atual"
                     style="height:60px;object-fit:contain;border:1px solid var(--bs-border-color);border-radius:8px;padding:6px;background:#fff">
                <?php endif; ?>
                <div>
                  <input type="file" name="empresa_logo" class="form-control" accept=".png,.jpg,.jpeg,.svg,.webp"
                         style="max-width:320px">
                  <div class="form-text">PNG, JPG, SVG ou WebP — máximo 2MB</div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- ── Sistema ────────────────────────────────────────────────────────── -->
    <div class="col-12">
      <div class="card border-0 shadow-sm">
        <div class="card-header fw-bold">
          <i class="bi bi-sliders me-2 text-muted"></i>Sistema
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label fw-semibold">Versão do Sistema</label>
              <input type="text" name="sistema_versao" class="form-control"
                     value="<?= h($config['sistema_versao']) ?>"
                     placeholder="1.0.0">
            </div>
            <div class="col-md-9 d-flex align-items-end">
              <div class="alert alert-info mb-0 w-100" style="font-size:.85rem">
                <i class="bi bi-info-circle me-2"></i>
                Estas informações aparecem nos relatórios, fichas de EPI e documentos exportados pelo sistema.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Botão salvar ───────────────────────────────────────────────────── -->
    <div class="col-12">
      <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-save me-2"></i>Salvar Configurações
      </button>
      <a href="/admin" class="btn btn-outline-secondary ms-2">Cancelar</a>
    </div>

  </div>
</form>
