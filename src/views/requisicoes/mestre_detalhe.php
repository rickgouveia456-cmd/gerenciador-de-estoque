<?php /* views/requisicoes/mestre_detalhe.php */
$statusMap=['pendente'=>['warning','clock','Pendente'],'aprovada'=>['info','check-circle','Aprovada'],'recusada'=>['danger','x-circle','Recusada'],'entregue'=>['success','bag-check','Entregue'],'parcial'=>['warning','dash-circle','Parcial'],'cancelada'=>['secondary','slash-circle','Cancelada']];
[$cls,$ico,$lbl]=$statusMap[$req['status']]??['secondary','question','?'];
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-1">Requisição <?= h($req['protocolo']??'#'.$req['id']) ?></h5>
    <span class="badge bg-<?= $cls ?>"><i class="bi bi-<?= $ico ?> me-1"></i><?= $lbl ?></span>
  </div>
  <div class="d-flex gap-2">
    <?php if(in_array($u['perfil'],['admin','almoxarife'])&&$req['status']!=='entregue'): ?>
    <a href="/requisicoes/mestre/<?= $req['id'] ?>/editar" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Editar</a>
    <?php endif; ?>
    <a href="/requisicoes/mestre" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
  </div>
</div>
<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Colaborador</div><div class="fw-semibold"><?= h($req['colaborador']) ?></div></div></div>
  <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Almoxarifado</div><div class="fw-semibold"><?= h($req['alm_nome']) ?></div></div></div>
  <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Solicitante</div><div class="fw-semibold"><?= h($req['mestre_nome']) ?></div></div></div>
  <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Data</div><div class="fw-semibold"><?= fmt_data($req['data_criacao'],'d/m/Y H:i') ?></div></div></div>
</div>
<?php if($req['observacao']): ?><div class="alert alert-info py-2 mb-3"><i class="bi bi-chat me-2"></i><?= h($req['observacao']) ?></div><?php endif; ?>
<div class="card mb-3">
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>Item</th><th class="text-center">Qtd Solicitada</th><th class="text-center">Estoque</th><th class="text-center">Status</th><th>Observação</th></tr></thead>
      <tbody>
      <?php foreach($itens as $ri): $sc=['aprovado'=>['success','✓'],'recusado'=>['danger','✗'],'pendente'=>['secondary','?']][$ri['status_item']]??['secondary','?']; ?>
      <tr>
        <td><?= h($ri['item_nome']) ?></td>
        <td class="text-center"><?= fmt_qtd((float)$ri['quantidade']) ?> <?= h($ri['unidade']) ?></td>
        <td class="text-center text-<?= (float)$ri['estoque_atual']>=(float)$ri['quantidade']?'success':'danger' ?>"><?= fmt_qtd((float)$ri['estoque_atual']) ?></td>
        <td class="text-center"><span class="badge bg-<?= $sc[0] ?>"><?= $sc[1] ?></span></td>
        <td class="small text-muted"><?= h($ri['observacao']??'') ?><?= $ri['motivo_recusa']?' — '.$ri['motivo_recusa']:'' ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php if(in_array($u['perfil'],['admin','almoxarife'])&&$req['status']==='pendente'): ?>
<div class="card mb-3">
  <div class="card-header fw-semibold">Aprovar / Recusar</div>
  <div class="card-body">
    <form method="POST" action="/requisicoes/mestre/<?= $req['id'] ?>/aprovar">
      <?= csrf_field() ?>
      <div class="d-flex gap-2 mb-3">
        <button type="submit" name="decisao" value="aprovada" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Aprovar Tudo</button>
        <button type="submit" name="decisao" value="recusada" class="btn btn-danger" onclick="return confirm('Recusar toda a requisição?')"><i class="bi bi-x-lg me-1"></i>Recusar Tudo</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php if(in_array($u['perfil'],['admin','almoxarife'])&&in_array($req['status'],['aprovada','pendente','parcial'])): ?>
<form method="POST" action="/requisicoes/mestre/<?= $req['id'] ?>/entregar" onsubmit="return confirm('Confirmar entrega e baixar estoque?')">
  <?= csrf_field() ?>
  <button class="btn btn-primary"><i class="bi bi-bag-check me-2"></i>Confirmar Entrega</button>
</form>
<?php endif; ?>

<?php if(in_array($u['perfil'],['admin','almoxarife'])): ?>
<!-- ── Seção: Foto do Comprovante ─────────────────────────── -->
<div class="card mt-3" style="border-radius:12px;overflow:hidden;box-shadow:0 1px 8px rgba(0,0,0,0.08)">
  <div class="card-header d-flex justify-content-between align-items-center py-3"
       style="background:var(--accent)">
    <span class="fw-bold text-white">
      <i class="bi bi-camera me-2"></i>Foto do Comprovante
    </span>
    <?php if(!empty($req['foto_url'])): ?>
    <button type="button" class="btn btn-sm btn-light" onclick="abrirModalFoto()">
      <i class="bi bi-arrow-repeat me-1"></i>Substituir
    </button>
    <?php endif; ?>
  </div>
  <div class="card-body p-3">
    <?php if(!empty($req['foto_url'])): ?>
    <!-- Foto existente -->
    <div class="text-center">
      <img src="<?= h($req['foto_url']) ?>"
           alt="Comprovante"
           class="img-fluid rounded"
           style="max-height:380px;border:1px solid var(--border);border-radius:10px;cursor:pointer"
           onclick="document.getElementById('modalFotoViewer').style.display='flex'"
           title="Clique para ampliar">
      <div class="text-muted small mt-2">
        <i class="bi bi-zoom-in me-1"></i>Clique na imagem para ampliar
      </div>
    </div>
    <?php else: ?>
    <!-- Sem foto -->
    <div class="text-center py-3">
      <i class="bi bi-camera-fill fs-1 text-muted mb-2 d-block"></i>
      <p class="text-muted mb-3">Nenhuma foto de comprovante adicionada.</p>
      <button type="button" class="btn btn-outline-primary" onclick="abrirModalFoto()">
        <i class="bi bi-plus-circle me-1"></i>Adicionar Foto
      </button>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal: Upload de Foto -->
<div id="modalFoto"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);
            z-index:1060;align-items:center;justify-content:center">
  <div class="card" style="width:min(500px,95vw);border-radius:14px;overflow:hidden">
    <div class="card-header d-flex justify-content-between align-items-center py-3"
         style="background:var(--accent)">
      <span class="fw-bold text-white"><i class="bi bi-camera me-2"></i>Adicionar / Substituir Foto</span>
      <button type="button" class="btn btn-sm btn-light" onclick="fecharModalFoto()">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    <div class="card-body p-4">
      <!-- Preview -->
      <div id="fotoPreviewWrap" style="display:none;margin-bottom:16px;text-center;text-align:center">
        <img id="fotoPreview"
             src=""
             alt="Preview"
             style="max-width:100%;max-height:260px;border-radius:10px;border:1px solid var(--border)">
        <div class="text-muted small mt-1">Preview da imagem</div>
      </div>

      <!-- Input file -->
      <div class="mb-3">
        <label class="form-label fw-semibold">Selecionar imagem</label>
        <input type="file"
               id="fotoInput"
               class="form-control"
               accept="image/*"
               onchange="previewFoto(this)">
        <div class="form-text">JPG, PNG ou WebP — máximo 5MB</div>
      </div>

      <!-- Mensagem de status -->
      <div id="fotoStatus" style="display:none" class="alert py-2 mb-3"></div>

      <div class="d-flex gap-2 justify-content-end">
        <button type="button" class="btn btn-outline-secondary" onclick="fecharModalFoto()">
          Cancelar
        </button>
        <button type="button"
                id="btnConfirmarFoto"
                class="btn btn-primary"
                disabled
                onclick="enviarFoto()">
          <i class="bi bi-cloud-upload me-1"></i>Confirmar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Visualizador ampliado -->
<div id="modalFotoViewer"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.85);
            z-index:1070;align-items:center;justify-content:center;cursor:zoom-out"
     onclick="this.style.display='none'">
  <img src="<?= h($req['foto_url'] ?? '') ?>"
       alt="Comprovante ampliado"
       style="max-width:90vw;max-height:90vh;border-radius:10px;box-shadow:0 0 40px rgba(0,0,0,0.5)">
</div>

<script>
(function() {
  var fotoBase64 = null;

  window.abrirModalFoto = function() {
    fotoBase64 = null;
    document.getElementById('fotoInput').value = '';
    document.getElementById('fotoPreviewWrap').style.display = 'none';
    document.getElementById('fotoPreview').src = '';
    document.getElementById('btnConfirmarFoto').disabled = true;
    var st = document.getElementById('fotoStatus');
    st.style.display = 'none'; st.className = 'alert py-2 mb-3';
    document.getElementById('modalFoto').style.display = 'flex';
  };

  window.fecharModalFoto = function() {
    document.getElementById('modalFoto').style.display = 'none';
  };

  window.previewFoto = function(input) {
    var file = input.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
      mostrarStatus('Imagem muito grande. Máximo 5MB.', 'danger');
      document.getElementById('btnConfirmarFoto').disabled = true;
      return;
    }
    var reader = new FileReader();
    reader.onload = function(e) {
      fotoBase64 = e.target.result;
      document.getElementById('fotoPreview').src = fotoBase64;
      document.getElementById('fotoPreviewWrap').style.display = 'block';
      document.getElementById('btnConfirmarFoto').disabled = false;
      var st = document.getElementById('fotoStatus');
      st.style.display = 'none';
    };
    reader.readAsDataURL(file);
  };

  window.enviarFoto = function() {
    if (!fotoBase64) return;
    var btn = document.getElementById('btnConfirmarFoto');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enviando…';

    fetch('/requisicoes/mestre/<?= $req['id'] ?>/foto', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ foto: fotoBase64 })
    })
    .then(function(r) { return r.json(); })
    .then(function(json) {
      if (json.ok) {
        mostrarStatus('Foto salva com sucesso!', 'success');
        // Atualizar imagem na página sem reload
        setTimeout(function() {
          fecharModalFoto();
          location.reload();
        }, 900);
      } else {
        mostrarStatus('Erro: ' + (json.erro || 'Falha ao salvar.'), 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i>Confirmar';
      }
    })
    .catch(function() {
      mostrarStatus('Erro de conexão. Tente novamente.', 'danger');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i>Confirmar';
    });
  };

  function mostrarStatus(msg, tipo) {
    var el = document.getElementById('fotoStatus');
    el.className = 'alert alert-' + tipo + ' py-2 mb-3';
    el.textContent = msg;
    el.style.display = 'block';
  }
})();
</script>
<?php endif; ?>
