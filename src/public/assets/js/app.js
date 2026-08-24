/**
 * Logi-Prime — JS principal (PHP version)
 */

// ── Sidebar Mobile ────────────────────────────────────────────
function openSidebar() {
  document.getElementById('sidebar')?.classList.add('open');
  document.getElementById('sidebarOverlay')?.classList.add('show');
}
function closeSidebar() {
  document.getElementById('sidebar')?.classList.remove('open');
  document.getElementById('sidebarOverlay')?.classList.remove('show');
}
// Fechar no ESC
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeSidebar();
});

// ── Auto-dismiss flashes ──────────────────────────────────────
document.querySelectorAll('.alert.alert-success, .alert.alert-info').forEach(el => {
  setTimeout(() => {
    const bs = bootstrap.Alert.getOrCreateInstance(el);
    bs?.close();
  }, 5000);
});

// ── Confirmar acoes destrutivas ───────────────────────────────
document.querySelectorAll('[data-confirm]').forEach(btn => {
  btn.addEventListener('click', function(e) {
    if (!confirm(this.dataset.confirm || 'Confirmar ação?')) {
      e.preventDefault();
    }
  });
});

// ── Tabela clicavel (linha inteira) ───────────────────────────
document.querySelectorAll('tr[data-href]').forEach(row => {
  row.style.cursor = 'pointer';
  row.addEventListener('click', e => {
    if (!e.target.closest('a, button, input, select, form')) {
      window.location = row.dataset.href;
    }
  });
});

// ── Busca em tabela local ─────────────────────────────────────
const tblSearch = document.getElementById('tblSearch');
if (tblSearch) {
  tblSearch.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tblMain tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// ── Toast helper ─────────────────────────────────────────────
function showToast(msg, tipo = 'success') {
  const container = document.getElementById('toastContainer') || (() => {
    const c = document.createElement('div');
    c.id = 'toastContainer';
    c.style.cssText = 'position:fixed;bottom:1rem;right:1rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem';
    document.body.appendChild(c);
    return c;
  })();
  const t = document.createElement('div');
  t.className = `toast align-items-center text-bg-${tipo} border-0 show`;
  t.setAttribute('role', 'alert');
  t.innerHTML = `<div class="d-flex"><div class="toast-body">${msg}</div>
    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
  container.appendChild(t);
  setTimeout(() => t.remove(), 4000);
}

// ── Linhas de movimentacao em lote ────────────────────────────
window.addLinhaMovimentacao = function(almId, itensJson) {
  const tbody = document.getElementById('linhasMovimentacao');
  if (!tbody) return;
  const idx = tbody.children.length;
  const itens = itensJson[almId] || [];
  const options = itens.map(it =>
    `<option value="${it.id}" data-qtd="${it.quantidade}" data-und="${it.unidade}">${it.nome} (${it.quantidade} ${it.unidade})</option>`
  ).join('');

  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td>
      <select name="item_id_${idx}" class="form-select form-select-sm item-select" required>
        <option value="">Selecione...</option>${options}
      </select>
    </td>
    <td><input type="number" name="quantidade_${idx}" class="form-control form-control-sm" step="0.01" min="0.01" required></td>
    <td><input type="text"   name="colaborador_${idx}" class="form-control form-control-sm" placeholder="Colaborador..."></td>
    <td>
      <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">
        <i class="bi bi-trash"></i>
      </button>
    </td>`;
  tbody.appendChild(tr);
};

// ── Persistir scroll da sidebar entre navegacoes ──────────────
(function() {
  const SCROLL_KEY = 'sidebar_scroll';
  const sidebar = document.getElementById('sidebar');
  if (!sidebar) return;

  // Restaurar posicao ao carregar
  const saved = sessionStorage.getItem(SCROLL_KEY);
  if (saved) sidebar.scrollTop = parseInt(saved, 10);

  // Salvar posicao antes de sair
  window.addEventListener('beforeunload', function() {
    sessionStorage.setItem(SCROLL_KEY, sidebar.scrollTop);
  });

  // Salvar em cada link da sidebar (mais confiavel em SPA-like)
  sidebar.querySelectorAll('a').forEach(function(a) {
    a.addEventListener('click', function() {
      sessionStorage.setItem(SCROLL_KEY, sidebar.scrollTop);
    });
  });
})();