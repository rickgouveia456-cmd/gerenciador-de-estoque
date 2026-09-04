<?php /* views/relatorios/ficha_epi.php */ ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0">
    <i class="bi bi-file-earmark-person me-2"></i>Ficha de EPI — FORM.SEG.014
  </h5>
  <div class="d-flex gap-2">
    <?php if ($funcionarioSel !== '' && !empty($fichaMovs)): ?>
    <?php
    $qsExp = http_build_query([
        'funcionario' => $funcionarioSel,
        'data_ini'    => $dataIni,
        'data_fim'    => $dataFim,
        'exportar'    => 1,
    ]);
    ?>
    <a href="/relatorios/ficha-epi/exportar?<?= $qsExp ?>" class="btn btn-sm btn-outline-success">
      <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar CSV
    </a>
    <?php endif; ?>
    <a href="/relatorios" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Relatórios
    </a>
  </div>
</div>

<!-- Formulário de seleção -->
<div class="card mb-4" style="border-radius:12px;overflow:hidden;box-shadow:0 1px 8px rgba(0,0,0,0.08)">
  <div class="card-header py-3" style="background:var(--accent);border-bottom:none">
    <h6 class="fw-bold mb-0 text-white">
      <i class="bi bi-funnel me-2"></i>Selecionar Funcionário e Período
    </h6>
  </div>
  <div class="card-body p-4">
    <form method="GET" action="/relatorios/ficha-epi" id="formFichaEpi">
      <div class="row g-3 align-items-end">

        <!-- Funcionário com datalist -->
        <div class="col-md-5">
          <label class="form-label fw-semibold">
            <i class="bi bi-person me-1"></i>Funcionário
          </label>
          <input type="text"
                 name="funcionario"
                 id="inputFuncionario"
                 class="form-control"
                 list="listFuncionarios"
                 placeholder="Digite o nome ou selecione…"
                 value="<?= h($funcionarioSel) ?>"
                 autocomplete="off">
          <datalist id="listFuncionarios">
            <?php foreach ($funcionarios as $nome): ?>
            <option value="<?= h($nome) ?>">
            <?php endforeach; ?>
          </datalist>
          <div class="form-text">Nomes extraídos das movimentações de EPI</div>
        </div>

        <!-- Data Início -->
        <div class="col-md-2">
          <label class="form-label fw-semibold">
            <i class="bi bi-calendar me-1"></i>Data Início
          </label>
          <input type="date" name="data_ini" class="form-control" value="<?= h($dataIni) ?>">
        </div>

        <!-- Data Fim -->
        <div class="col-md-2">
          <label class="form-label fw-semibold">
            <i class="bi bi-calendar-check me-1"></i>Data Fim
          </label>
          <input type="date" name="data_fim" class="form-control" value="<?= h($dataFim) ?>">
        </div>

        <!-- Botão -->
        <div class="col-md-3">
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-search me-1"></i>Gerar Ficha
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php if ($funcionarioSel !== ''): ?>

<!-- Resultado -->
<?php if (empty($fichaMovs)): ?>
<div class="card p-5 text-center" style="border-radius:12px">
  <i class="bi bi-inbox fs-1 text-muted mb-3"></i>
  <h5 class="fw-semibold text-muted">Nenhuma saída de EPI encontrada para "<?= h($funcionarioSel) ?>" no período.</h5>
</div>
<?php else: ?>

<!-- Stats rápidas -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center" style="border-left:4px solid var(--accent) !important;border-radius:12px">
      <div class="fw-bold fs-3" style="color:var(--accent)"><?= count($fichaMovs) ?></div>
      <div class="text-muted small">Entregas</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center" style="border-left:4px solid var(--info) !important;border-radius:12px">
      <div class="fw-bold fs-3 text-info">
        <?= fmt_qtd(array_sum(array_column($fichaMovs, 'quantidade'))) ?>
      </div>
      <div class="text-muted small">Qtd Total</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center" style="border-left:4px solid var(--success) !important;border-radius:12px">
      <div class="fw-bold fs-3 text-success"><?= fmt_data($dataIni, 'd/m/Y') ?></div>
      <div class="text-muted small">Início</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center" style="border-left:4px solid var(--warning) !important;border-radius:12px">
      <div class="fw-bold fs-3 text-warning"><?= fmt_data($dataFim, 'd/m/Y') ?></div>
      <div class="text-muted small">Fim</div>
    </div>
  </div>
</div>

<!-- Tabela da ficha -->
<div class="card" style="border-radius:12px;overflow:hidden;box-shadow:0 1px 8px rgba(0,0,0,0.08)">
  <div class="card-header d-flex justify-content-between align-items-center py-3"
       style="background:var(--accent)">
    <span class="fw-bold text-white">
      <i class="bi bi-person-check me-2"></i><?= h($funcionarioSel) ?>
    </span>
    <span class="badge bg-white text-dark rounded-pill px-3"><?= count($fichaMovs) ?> registro(s)</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
      <thead>
        <tr>
          <th class="text-center">Qtd</th>
          <th>Descrição do EPI</th>
          <th class="text-center">C.A.</th>
          <th class="text-center">Data Entrega</th>
          <th>Almoxarifado</th>
          <th class="text-center">Data Devolução</th>
          <th>Observação</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($fichaMovs as $mov): ?>
      <tr>
        <td class="text-center fw-semibold">
          <?= fmt_qtd((float)$mov['quantidade']) ?>
          <span class="text-muted fw-normal" style="font-size:0.75rem"><?= h($mov['unidade']) ?></span>
        </td>
        <td>
          <span class="fw-semibold"><?= h($mov['item_nome']) ?></span>
          <?php if (!empty($mov['codigo'])): ?>
          <div class="font-monospace text-muted" style="font-size:0.73rem"><?= h($mov['codigo']) ?></div>
          <?php endif; ?>
        </td>
        <td class="text-center">
          <?php if (!empty($mov['ca'])): ?>
          <span class="badge rounded-pill" style="background:var(--info-light);color:var(--info);border-radius:20px">
            <?= h($mov['ca']) ?>
          </span>
          <?php else: ?>
          <span class="text-muted small">—</span>
          <?php endif; ?>
        </td>
        <td class="text-center text-muted small"><?= fmt_data($mov['data'], 'd/m/Y') ?></td>
        <td class="text-muted small"><?= h($mov['alm_nome']) ?></td>
        <td class="text-center text-muted small">—</td>
        <td class="small text-muted"><?= h($mov['observacao'] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer py-2 text-end">
    <?php
    $qsExp = http_build_query([
        'funcionario' => $funcionarioSel,
        'data_ini'    => $dataIni,
        'data_fim'    => $dataFim,
        'exportar'    => 1,
    ]);
    ?>
    <a href="/relatorios/ficha-epi/exportar?<?= $qsExp ?>" class="btn btn-success btn-sm">
      <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar Ficha de EPI (CSV)
    </a>
  </div>
</div>

<?php endif; ?>
<?php endif; ?>

<!-- Card informativo -->
<div class="card mt-4 p-4" style="border-radius:12px;background:var(--primary-light);border:1px solid var(--border)">
  <div class="d-flex gap-3 align-items-start">
    <i class="bi bi-info-circle-fill fs-4" style="color:var(--info);flex-shrink:0;margin-top:2px"></i>
    <div>
      <div class="fw-semibold mb-1">Sobre o FORM.SEG.014 — Ficha de EPI</div>
      <p class="text-muted small mb-0">
        Este relatório gera a <strong>Ficha de Controle de EPI</strong> conforme o formulário
        FORM.SEG.014, que registra todos os Equipamentos de Proteção Individual entregues a
        um colaborador em um determinado período. O documento pode ser exportado em CSV e
        utilizado para auditorias de segurança do trabalho, fiscalizações do MTE e controle
        interno do PCMSO/PPRA. Certifique-se de que as observações das movimentações incluam
        o nome do colaborador no padrão <code>liberado P/ NOME</code> ou <code>Colaborador: NOME</code>.
      </p>
    </div>
  </div>
</div>
