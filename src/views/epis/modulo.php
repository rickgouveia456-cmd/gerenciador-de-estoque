<?php /* views/epis/modulo.php */
$abaAtual = $aba ?? "painel";
$abas = [
    ["painel",       "bi-speedometer2",        "Painel"],
    ["fichas",       "bi-file-earmark-person",  "Fichas"],
    ["devolucoes",   "bi-arrow-return-left",    "Devoluções"],
    ["matriz",       "bi-table",                "Matriz EPI"],
    ["habilitacoes", "bi-award",                "Habilitações"],
    ["certificados", "bi-patch-check",          "Certificados CA"],
];
?>
<!-- Abas -->
<div class="mb-4">
  <ul class="nav nav-tabs border-bottom-0 flex-wrap" style="gap:3px">
    <?php foreach($abas as [$id,$ico,$lbl]): $isAtiva = ($abaAtual===$id)||($id==="fichas"&&in_array($abaAtual,["ficha_nova","ficha_detalhe"])); ?>
    <li class="nav-item">
      <a href="/epi_modulo?aba=<?= $id ?>"
         class="nav-link fw-semibold <?= $isAtiva?"active text-white":"text-muted" ?>"
         style="<?= $isAtiva?"background:var(--accent);border-color:var(--accent)":"background:var(--primary-light)" ?>;border-radius:8px 8px 0 0;font-size:0.83rem">
        <i class="bi <?= $ico ?> me-1"></i><?= $lbl ?>
      </a>
    </li>
    <?php endforeach; ?>
  </ul>
  <div style="height:3px;background:var(--accent);border-radius:0 4px 4px 4px"></div>
</div>

<?php // ═══ PAINEL ═══════════════════════════════════════════
if($abaAtual==="painel"): ?>
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3"><div class="card text-center p-3" style="border-top:3px solid var(--accent)">
    <div class="fs-3 fw-bold" style="color:var(--accent)"><?= $total_fichas ?></div>
    <div class="small text-muted">Total de Fichas</div>
  </div></div>
  <div class="col-6 col-md-3"><div class="card text-center p-3" style="border-top:3px solid #059669">
    <div class="fs-3 fw-bold text-success"><?= $fichas_ativas ?></div>
    <div class="small text-muted">Fichas Ativas</div>
  </div></div>
  <div class="col-6 col-md-3"><div class="card text-center p-3" style="border-top:3px solid #d97706">
    <div class="fs-3 fw-bold text-warning"><?= $devolucoes_abertas ?></div>
    <div class="small text-muted">Devoluções em Aberto</div>
  </div></div>
  <div class="col-6 col-md-3"><div class="card text-center p-3" style="border-top:3px solid var(--info)">
    <div class="fs-3 fw-bold text-info"><?= count($ultimas_fichas??[]) ?></div>
    <div class="small text-muted">Fichas Recentes</div>
  </div></div>
</div>
<div class="card mb-3">
  <div class="card-header fw-semibold" style="background:var(--primary-light)">
    <i class="bi bi-file-earmark-person me-2" style="color:var(--accent)"></i>Fichas Ativas Recentes
  </div>
  <div class="card-body p-0">
    <?php if(empty($ultimas_fichas)): ?>
    <div class="text-center py-4 text-muted small"><i class="bi bi-inbox fs-3 d-block mb-1"></i>Nenhuma ficha ativa.</div>
    <?php else: ?>
    <table class="table table-hover mb-0">
      <thead><tr><th>Colaborador</th><th>Função</th><th>Obra</th><th>Abertura</th><th></th></tr></thead>
      <tbody>
      <?php foreach($ultimas_fichas as $f): ?>
      <tr>
        <td class="fw-semibold"><?= h($f["colaborador"]) ?></td>
        <td class="small text-muted"><?= h($f["funcao"]??"—") ?></td>
        <td class="small text-muted"><?= h($f["obra"]??"—") ?></td>
        <td class="small"><?= fmt_data($f["data_abertura"],"d/m/Y") ?></td>
        <td><a href="/epi_modulo?aba=ficha_detalhe&id=<?= $f["id"] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
<div class="d-flex gap-2">
  <a href="/epi_modulo?aba=ficha_nova" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nova Ficha</a>
  <a href="/epi_modulo?aba=devolucoes" class="btn btn-outline-warning btn-sm"><i class="bi bi-arrow-return-left me-1"></i>Ver Devoluções</a>
</div>

<?php // ═══ FICHAS ═══════════════════════════════════════════
elseif($abaAtual==="fichas"): ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <form method="GET" action="/epi_modulo" class="d-flex gap-2">
    <input type="hidden" name="aba" value="fichas">
    <input type="text" name="q" class="form-control form-control-sm" placeholder="Buscar colaborador..." value="<?= h($busca??"") ?>" style="max-width:220px">
    <select name="status" class="form-select form-select-sm" style="max-width:130px">
      <option value="">Todos</option>
      <option value="ativa" <?= ($status_filtro??"")=="ativa"?"selected":"" ?>>Ativas</option>
      <option value="encerrada" <?= ($status_filtro??"")=="encerrada"?"selected":"" ?>>Encerradas</option>
    </select>
    <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-funnel"></i></button>
  </form>
  <a href="/epi_modulo?aba=ficha_nova" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nova Ficha</a>
</div>
<?php if(empty($fichas)): ?>
<div class="card"><div class="text-center py-5 text-muted"><i class="bi bi-file-earmark-person fs-1 d-block mb-2" style="color:var(--accent)"></i>Nenhuma ficha encontrada.</div></div>
<?php else: ?>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Colaborador</th><th>Função</th><th>Obra</th><th class="text-center">Itens</th><th>Abertura</th><th>Criado por</th><th class="text-center">Status</th><th class="text-center">Ações</th></tr></thead>
      <tbody>
      <?php foreach($fichas as $f): ?>
      <tr>
        <td class="fw-semibold"><?= h($f["colaborador"]) ?></td>
        <td class="small text-muted"><?= h($f["funcao"]??"—") ?></td>
        <td class="small text-muted"><?= h($f["obra"]??"—") ?></td>
        <td class="text-center"><span class="badge bg-secondary"><?= $f["total_itens"] ?></span></td>
        <td class="small"><?= fmt_data($f["data_abertura"],"d/m/Y") ?></td>
        <td class="small text-muted"><?= h($f["criado_por"]??"—") ?></td>
        <td class="text-center"><span class="badge bg-<?= $f["status"]==="ativa"?"success":"secondary" ?>"><?= $f["status"]==="ativa"?"✅ Ativa":"● Encerrada" ?></span></td>
        <td class="text-center"><a href="/epi_modulo?aba=ficha_detalhe&id=<?= $f["id"] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php // ═══ NOVA FICHA ════════════════════════════════════════
elseif($abaAtual==="ficha_nova"): ?>
<form method="POST" action="/epi_modulo?aba=ficha_nova" id="formFicha">
  <?= csrf_field() ?>
  <div class="card mb-3">
    <div class="card-header fw-bold text-white" style="background:var(--accent)"><i class="bi bi-person-check me-2"></i>Dados do Colaborador</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4"><label class="form-label fw-semibold">Colaborador *</label>
          <input type="text" name="colaborador" class="form-control" required id="inputColab" autocomplete="off" placeholder="Nome...">
          <div id="sugColab" class="list-group position-absolute shadow-sm" style="z-index:1000;display:none;min-width:250px"></div>
        </div>
        <div class="col-md-3"><label class="form-label fw-semibold">Função</label><input type="text" name="funcao" class="form-control" placeholder="Ex: Pedreiro..."></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Obra</label><input type="text" name="obra" class="form-control" placeholder="Ex: Obra Patamares"></div>
        <div class="col-md-2"><label class="form-label fw-semibold">Almoxarifado</label>
          <select name="almoxarifado_id" class="form-select">
            <option value="">—</option>
            <?php foreach($almoxarifados as $a): ?><option value="<?= $a["id"] ?>"><?= h($a["nome"]) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
  </div>
  <div class="card mb-3">
    <div class="card-header fw-bold text-white d-flex justify-content-between align-items-center" style="background:var(--accent)">
      <span><i class="bi bi-list-ul me-2"></i>EPIs Entregues</span>
      <button type="button" class="btn btn-sm btn-warning fw-bold" onclick="addEpiLine()"><i class="bi bi-plus-lg me-1"></i>Adicionar EPI</button>
    </div>
    <div class="card-body p-2">
      <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Descrição *</th><th style="width:100px">CA</th><th style="width:80px">Qtd</th><th style="width:90px">Tamanho</th><th style="width:110px">Data Entrega</th><th style="width:40px"></th></tr></thead><tbody id="epiLinhas"></tbody></table></div>
    </div>
  </div>
  <div class="d-flex gap-2 justify-content-end">
    <a href="/epi_modulo?aba=fichas" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Criar Ficha</button>
  </div>
</form>
<script>
let epiIdx=0;
function addEpiLine(){
  const tbody=document.getElementById("epiLinhas");
  const tr=document.createElement("tr");
  const hoje=new Date().toISOString().split("T")[0];
  tr.innerHTML=`<td><input type="text" name="epi_desc_${epiIdx}" class="form-control form-control-sm" required></td><td><input type="text" name="epi_ca_${epiIdx}" class="form-control form-control-sm" placeholder="CA-XXXXX"></td><td><input type="number" name="epi_qtd_${epiIdx}" class="form-control form-control-sm" value="1" min="0.01" step="0.01"></td><td><input type="text" name="epi_tam_${epiIdx}" class="form-control form-control-sm" placeholder="M, G..."></td><td><input type="date" name="epi_dt_${epiIdx}" class="form-control form-control-sm" value="${hoje}"></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest(\"tr\").remove()"><i class="bi bi-trash"></i></button></td>`;
  tbody.appendChild(tr); epiIdx++;
}
// Autocomplete colaboradores
const inputColab=document.getElementById("inputColab");
const sugColab=document.getElementById("sugColab");
inputColab?.addEventListener("input",function(){
  const q=this.value.trim(); if(q.length<2){sugColab.style.display="none";return;}
  fetch("/api/colaboradores?q="+encodeURIComponent(q)).then(r=>r.json()).then(data=>{
    sugColab.innerHTML=""; if(!data.length){sugColab.style.display="none";return;}
    data.slice(0,8).forEach(c=>{const a=document.createElement("button");a.type="button";a.className="list-group-item list-group-item-action py-1 small";a.textContent=c.nome+(c.funcao?" — "+c.funcao:"");a.onclick=()=>{inputColab.value=c.nome;sugColab.style.display="none";};sugColab.appendChild(a);});
    sugColab.style.display="block";
  });
});
document.addEventListener("click",e=>{if(!sugColab.contains(e.target)&&e.target!==inputColab)sugColab.style.display="none";});
</script>

<?php // ═══ DETALHE FICHA ═════════════════════════════════════
elseif($abaAtual==="ficha_detalhe"): ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0"><?= h($ficha["colaborador"]) ?></h5>
    <span class="text-muted small"><?= h($ficha["funcao"]??"") ?><?= $ficha["obra"]?" · ".h($ficha["obra"]):"" ?></span>
  </div>
  <div class="d-flex gap-2">
    <?php if($ficha["status"]==="ativa"&&$u["perfil"]!=="mestre"): ?>
    <form method="POST" action="/epi_modulo?aba=encerrar_ficha" class="d-inline" onsubmit="return confirm('Encerrar esta ficha?')">
      <?= csrf_field() ?><input type="hidden" name="ficha_id" value="<?= $ficha["id"] ?>">
      <button class="btn btn-outline-warning btn-sm"><i class="bi bi-x-circle me-1"></i>Encerrar</button>
    </form>
    <?php endif; ?>
    <a href="/epi_modulo?aba=fichas" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
  </div>
</div>
<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="card text-center p-3"><div class="small text-muted">Status</div><div class="fw-bold mt-1"><span class="badge bg-<?= $ficha["status"]==="ativa"?"success":"secondary" ?> px-3 py-2"><?= $ficha["status"]==="ativa"?"✅ Ativa":"● Encerrada" ?></span></div></div></div>
  <div class="col-md-3"><div class="card text-center p-3"><div class="small text-muted">Abertura</div><div class="fw-bold mt-1"><?= fmt_data($ficha["data_abertura"],"d/m/Y") ?></div></div></div>
  <div class="col-md-3"><div class="card text-center p-3"><div class="small text-muted">Total de EPIs</div><div class="fw-bold mt-1" style="color:var(--accent)"><?= count($ficha_itens) ?></div></div></div>
  <div class="col-md-3"><div class="card text-center p-3"><div class="small text-muted">Criado por</div><div class="fw-bold mt-1 small"><?= h($ficha["criado_por"]??"—") ?></div></div></div>
</div>
<div class="card">
  <div class="card-header fw-semibold" style="background:var(--primary-light)"><i class="bi bi-list-ul me-2" style="color:var(--accent)"></i>EPIs desta Ficha</div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Descrição</th><th class="text-center">CA</th><th class="text-center">Qtd</th><th class="text-center">Tamanho</th><th class="text-center">Entrega</th><th class="text-center">Devolução</th><th class="text-center">Status</th><?php if($ficha["status"]==="ativa"&&$u["perfil"]!=="mestre"): ?><th class="text-center">Ação</th><?php endif; ?></tr></thead>
      <tbody>
      <?php if(empty($ficha_itens)): ?><tr><td colspan="8" class="text-center text-muted py-3">Nenhum EPI nesta ficha.</td></tr><?php endif; ?>
      <?php foreach($ficha_itens as $it): ?>
      <tr>
        <td class="fw-semibold"><?= h($it["descricao"]) ?></td>
        <td class="text-center"><code><?= h($it["ca"]??"—") ?></code></td>
        <td class="text-center"><?= fmt_qtd((float)$it["quantidade"]) ?></td>
        <td class="text-center text-muted small"><?= h($it["tamanho"]??"—") ?></td>
        <td class="text-center small"><?= $it["data_entrega"]?fmt_data($it["data_entrega"],"d/m/Y"):"—" ?></td>
        <td class="text-center small"><?= $it["data_devolucao"]?fmt_data($it["data_devolucao"],"d/m/Y"):"—" ?></td>
        <td class="text-center"><span class="badge bg-<?= $it["data_devolucao"]?"success":"warning" ?>"><?= $it["data_devolucao"]?"Devolvido":"Em uso" ?></span></td>
        <?php if($ficha["status"]==="ativa"&&$u["perfil"]!=="mestre"): ?>
        <td class="text-center">
          <?php if(!$it["data_devolucao"]): ?>
          <form method="POST" action="/epi_modulo?aba=devolver_item" class="d-inline"><?= csrf_field() ?>
            <input type="hidden" name="item_id" value="<?= $it["id"] ?>">
            <input type="hidden" name="ficha_id" value="<?= $ficha["id"] ?>">
            <button class="btn btn-sm btn-outline-success" onclick="return confirm(\"Registrar devolução?\")"><i class="bi bi-arrow-return-left"></i></button>
          </form>
          <?php endif; ?>
        </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php // ═══ DEVOLUCOES ════════════════════════════════════════
elseif($abaAtual==="devolucoes"): ?>
<h6 class="fw-semibold mb-3">EPIs em uso aguardando devolução</h6>
<?php if(empty($devolucoes)): ?>
<div class="card"><div class="text-center py-5 text-success"><i class="bi bi-check-circle fs-1 d-block mb-2"></i>Nenhuma devolução pendente!</div></div>
<?php else: ?>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
  <thead><tr><th>Colaborador</th><th>EPI</th><th class="text-center">CA</th><th>Almoxarifado</th><th class="text-center">Entregue em</th><th class="text-center">Ação</th></tr></thead>
  <tbody>
  <?php foreach($devolucoes as $d): ?>
  <tr>
    <td class="fw-semibold"><?= h($d["colaborador"]) ?><div class="small text-muted"><?= h($d["funcao"]??"") ?></div></td>
    <td><?= h($d["descricao"]) ?></td>
    <td class="text-center"><code><?= h($d["ca"]??"—") ?></code></td>
    <td class="small text-muted"><?= h($d["alm_nome"]??"—") ?></td>
    <td class="text-center small"><?= $d["data_entrega"]?fmt_data($d["data_entrega"],"d/m/Y"):"—" ?></td>
    <td class="text-center">
      <form method="POST" action="/epi_modulo?aba=devolver_item"><?= csrf_field() ?>
        <input type="hidden" name="item_id" value="<?= $d["id"] ?>">
        <input type="hidden" name="ficha_id" value="<?= $d["ficha_id"] ?>">
        <button class="btn btn-sm btn-success" onclick="return confirm(\"Confirmar devolução?\")"><i class="bi bi-arrow-return-left me-1"></i>Devolver</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
<?php endif; ?>

<?php // ═══ MATRIZ ════════════════════════════════════════════
elseif($abaAtual==="matriz"): ?>
<div class="d-flex justify-content-end mb-3">
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalMatriz"><i class="bi bi-plus-lg me-1"></i>Nova Matriz</button>
</div>
<?php if(empty($matrizes)): ?>
<div class="card"><div class="text-center py-5 text-muted"><i class="bi bi-table fs-1 d-block mb-2" style="color:var(--accent)"></i>Nenhuma matriz cadastrada.</div></div>
<?php else: ?>
<div class="row g-3">
  <?php foreach($matrizes as $m): ?>
  <div class="col-md-6 col-xl-4">
    <div class="card h-100" style="border-top:3px solid var(--accent)">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div><h6 class="fw-bold mb-0" style="color:var(--accent)"><?= h($m["funcao"]) ?></h6><small class="text-muted"><?= h($m["obra"]??"Todas as obras") ?></small></div>
          <div class="d-flex gap-1 align-items-center">
            <?php if($m["norma"]): ?><span class="badge bg-info text-dark" style="font-size:0.65rem"><?= h($m["norma"]) ?></span><?php endif; ?>
            <form method="POST" action="/epi_modulo?aba=deletar_matriz" class="d-inline" onsubmit="return confirm('Remover?')"><?= csrf_field() ?><input type="hidden" name="matriz_id" value="<?= $m["id"] ?>"><button class="btn btn-sm btn-outline-danger p-0 px-1"><i class="bi bi-trash" style="font-size:0.75rem"></i></button></form>
          </div>
        </div>
        <div class="small text-muted mb-1 fw-semibold">EPIs Obrigatórios:</div>
        <?php if(!empty($m["_epis"])): ?>
        <div class="d-flex flex-wrap gap-1">
          <?php foreach($m["_epis"] as $epi): ?><span class="badge rounded-pill bg-light text-dark border">🪖 <?= h($epi) ?></span><?php endforeach; ?>
        </div>
        <?php else: ?><span class="text-muted small">Nenhum EPI definido</span><?php endif; ?>
        <div class="mt-2 pt-2 border-top"><small class="text-muted"><i class="bi bi-person me-1"></i><?= h($m["criado_por"]??"—") ?></small></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<!-- Modal Nova Matriz -->
<div class="modal fade" id="modalMatriz" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="/epi_modulo?aba=matriz">
      <?= csrf_field() ?>
      <div class="modal-header text-white" style="background:var(--accent)"><h6 class="modal-title fw-bold"><i class="bi bi-table me-2"></i>Nova Matriz de EPI</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label fw-semibold">Função/Cargo *</label><input type="text" name="funcao" class="form-control" required placeholder="Ex: Armador, Eletricista..."></div>
        <div class="row g-2 mb-3">
          <div class="col-6"><label class="form-label small">Obra</label><input type="text" name="obra" class="form-control form-control-sm" placeholder="Opcional"></div>
          <div class="col-6"><label class="form-label small">Norma</label><input type="text" name="norma" class="form-control form-control-sm" placeholder="Ex: NR-6"></div>
        </div>
        <div class="mb-2"><label class="form-label fw-semibold">EPIs Obrigatórios (um por linha)</label><textarea name="epis_texto" class="form-control" rows="5" placeholder="Capacete&#10;Bota&#10;Luva&#10;Óculos de proteção"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Criar Matriz</button></div>
    </form>
  </div></div>
</div>

<?php // ═══ HABILITACOES ══════════════════════════════════════
elseif($abaAtual==="habilitacoes"): ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <form method="GET" action="/epi_modulo" class="d-flex gap-2"><input type="hidden" name="aba" value="habilitacoes"><input type="text" name="q" class="form-control form-control-sm" placeholder="Buscar colaborador..." value="<?= h($_GET["q"]??"") ?>" style="max-width:220px"><button class="btn btn-outline-secondary btn-sm"><i class="bi bi-search"></i></button></form>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalHab"><i class="bi bi-plus-lg me-1"></i>Nova Habilitação</button>
</div>
<?php if(empty($habilitacoes)): ?>
<div class="card"><div class="text-center py-5 text-muted"><i class="bi bi-award fs-1 d-block mb-2" style="color:var(--accent)"></i>Nenhuma habilitação cadastrada.</div></div>
<?php else: ?>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
  <thead><tr><th>Colaborador</th><th>Tipo</th><th>Descrição</th><th class="text-center">Validade</th><th class="text-center">Status</th></tr></thead>
  <tbody>
  <?php foreach($habilitacoes as $h2): $hoje=new DateTime(); $valida=$h2["validade"]?new DateTime($h2["validade"]):null; $venceu=$valida&&$valida<$hoje; ?>
  <tr>
    <td class="fw-semibold"><?= h($h2["colaborador"]) ?></td>
    <td><?= h($h2["tipo"]) ?></td>
    <td class="text-muted small"><?= h($h2["descricao"]??"—") ?></td>
    <td class="text-center small"><?= $valida?fmt_data($h2["validade"],"d/m/Y"):"—" ?></td>
    <td class="text-center"><span class="badge bg-<?= !$valida?"secondary":($venceu?"danger":"success") ?>"><?= !$valida?"Sem validade":($venceu?"Vencida":"Válida") ?></span></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
<?php endif; ?>
<!-- Modal Nova Habilitacao -->
<div class="modal fade" id="modalHab" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="/epi_modulo?aba=habilitacoes">
      <?= csrf_field() ?>
      <div class="modal-header text-white" style="background:var(--accent)"><h6 class="modal-title fw-bold">Nova Habilitação</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label fw-semibold">Colaborador *</label><input type="text" name="colaborador" class="form-control" required placeholder="Nome do colaborador..."></div>
        <div class="mb-3"><label class="form-label fw-semibold">Tipo *</label><input type="text" name="tipo" class="form-control" required placeholder="Ex: NR-10, Espaço Confinado, Andaime..."></div>
        <div class="mb-3"><label class="form-label fw-semibold">Descrição</label><input type="text" name="descricao" class="form-control" placeholder="Observações..."></div>
        <div class="row g-2">
          <div class="col-6"><label class="form-label small">Validade</label><input type="date" name="validade" class="form-control form-control-sm"></div>
          <div class="col-6"><label class="form-label small">Almoxarifado</label><select name="almoxarifado_id" class="form-select form-select-sm"><option value="">—</option><?php foreach($almoxarifados as $a): ?><option value="<?= $a["id"] ?>"><?= h($a["nome"]) ?></option><?php endforeach; ?></select></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
    </form>
  </div></div>
</div>

<?php // ═══ CERTIFICADOS (Em Breve) ═══════════════════════════
elseif($abaAtual==="certificados"): ?>
<div class="text-center py-5 mt-4">
  <div style="font-size:4rem">🔜</div>
  <h3 class="fw-bold mt-3" style="color:var(--accent)">Em Breve</h3>
  <p class="text-muted mt-2">O módulo de Certificados CA está sendo desenvolvido.<br>Em breve você poderá cadastrar e controlar os Certificados de Aprovação dos EPIs.</p>
  <a href="/epi_modulo?aba=painel" class="btn btn-outline-secondary btn-sm mt-3"><i class="bi bi-arrow-left me-1"></i>Voltar ao Painel</a>
</div>

<?php endif; ?>