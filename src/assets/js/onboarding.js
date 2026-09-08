/**
 * Logi-Prime — Onboarding Guiado (3.7.2)
 * Aparece na primeira vez que o usuário acessa cada tela principal.
 * Usa localStorage para não repetir.
 */
(function () {
    'use strict';

    // Passos por página (data-onboarding no body)
    const PASSOS = {
        dashboard: [
            {
                seletor: '.stats-grid, #stats-row, .row.g-3',
                titulo:  '📊 Visão Geral',
                texto:   'Aqui você vê o resumo do sistema: total de almoxarifados, itens, alertas e críticos. Atualize a página para ver dados em tempo real.',
            },
            {
                seletor: '[href="/relatorios/alertas"], .nav-link[href*="alertas"]',
                titulo:  '🔔 Alertas de Estoque',
                texto:   'Itens abaixo do estoque mínimo aparecem aqui. Configure o estoque mínimo de cada item para receber alertas automáticos.',
            },
        ],
        almoxarifado: [
            {
                seletor: '#buscaItem, input[placeholder*="Buscar"]',
                titulo:  '🔍 Busca Rápida',
                texto:   'Digite o nome ou código do insumo para filtrar a lista instantaneamente.',
            },
            {
                seletor: '[href*="/item/novo"], .btn-novo-item',
                titulo:  '➕ Novo Insumo',
                texto:   'Clique aqui para cadastrar um novo item neste almoxarifado. Informe código, unidade e estoque mínimo.',
            },
            {
                seletor: '[href*="/movimentacao/lote"]',
                titulo:  '📦 Registrar Movimentação',
                texto:   'Use este botão para registrar entradas e saídas de múltiplos itens de uma vez.',
            },
        ],
        requisicao: [
            {
                seletor: '[href="/requisicoes/mestre/nova"], .btn-nova-req',
                titulo:  '📋 Nova Requisição',
                texto:   'Clique aqui para solicitar materiais ao almoxarifado. Informe o colaborador e os itens necessários.',
            },
            {
                seletor: '.pill-status, [class*="badge"]',
                titulo:  '🏷️ Status da Requisição',
                texto:   'Pendente → Aprovada → Entregue. Acompanhe o status de cada requisição nesta tela.',
            },
        ],
        epi: [
            {
                seletor: '[href*="aba=fichas"], [href*="epi_modulo"]',
                titulo:  '🦺 Fichas de EPI',
                texto:   'Cada colaborador tem uma ficha digital. Registre entregas e devoluções de EPIs diretamente na ficha.',
            },
            {
                seletor: '[href*="aba=matriz"]',
                titulo:  '📋 Matriz de EPI',
                texto:   'Defina quais EPIs são obrigatórios por função (NR-6, NR-35, etc). A matriz orienta o almoxarife na entrega.',
            },
        ],
        movimentacao: [
            {
                seletor: 'select[name="almoxarifado_id"]',
                titulo:  '🏭 Selecione o Almoxarifado',
                texto:   'Escolha o almoxarifado antes de registrar a movimentação. Cada almoxarifado tem seu próprio estoque.',
            },
            {
                seletor: 'select[name="tipo"]',
                titulo:  '↕️ Tipo de Movimentação',
                texto:   'Entrada: aumenta o estoque. Saída: diminui o estoque. Devolução EPI: registra retorno de equipamento.',
            },
        ],
    };

    // ── Detecta qual página estamos ─────────────────────────────────────────
    function detectarPagina() {
        const path = window.location.pathname;
        if (path === '/' || path === '/dashboard') return 'dashboard';
        if (path.match(/^\/almoxarifado\/\d+$/))   return 'almoxarifado';
        if (path.match(/^\/requisicoes\/mestre/))  return 'requisicao';
        if (path.match(/^\/epi_modulo/))           return 'epi';
        if (path === '/movimentacao/lote')         return 'movimentacao';
        return null;
    }

    // ── Verifica se já mostrou para esta página ──────────────────────────────
    function jaViu(pagina) {
        try {
            const visto = JSON.parse(localStorage.getItem('lp_onboarding') || '{}');
            return !!visto[pagina];
        } catch { return true; }
    }

    function marcarVisto(pagina) {
        try {
            const visto = JSON.parse(localStorage.getItem('lp_onboarding') || '{}');
            visto[pagina] = true;
            localStorage.setItem('lp_onboarding', JSON.stringify(visto));
        } catch {}
        // Notifica o servidor
        fetch('/admin/onboarding', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'passo=' + pagina + '&csrf_token=' + (document.querySelector('input[name=csrf_token]')?.value || ''),
        }).catch(() => {});
    }

    // ── Cria o tooltip ────────────────────────────────────────────────────────
    function criarTooltip(titulo, texto, alvo, passoAtual, totalPassos, onNext, onFim) {
        // Remove tooltip existente
        document.getElementById('lp-onboarding-tip')?.remove();

        const tip = document.createElement('div');
        tip.id = 'lp-onboarding-tip';
        tip.innerHTML = `
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                <span style="font-weight:700;font-size:.9rem">${titulo}</span>
                <button id="lp-ob-skip" style="background:none;border:none;color:rgba(255,255,255,.6);cursor:pointer;font-size:.85rem;padding:0">Pular ✕</button>
            </div>
            <p style="margin:0 0 12px;font-size:.82rem;color:rgba(255,255,255,.85);line-height:1.5">${texto}</p>
            <div style="display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:.72rem;color:rgba(255,255,255,.5)">${passoAtual}/${totalPassos}</span>
                <button id="lp-ob-next" style="background:#f0a500;border:none;color:#000;border-radius:6px;padding:5px 16px;font-size:.8rem;font-weight:700;cursor:pointer">
                    ${passoAtual === totalPassos ? '✅ Concluir' : 'Próximo →'}
                </button>
            </div>
        `;

        Object.assign(tip.style, {
            position: 'fixed',
            zIndex: '99999',
            background: '#1a2035',
            border: '2px solid #f0a500',
            borderRadius: '12px',
            padding: '16px 18px',
            maxWidth: '300px',
            boxShadow: '0 8px 32px rgba(0,0,0,.5)',
            color: '#fff',
            fontFamily: 'inherit',
        });

        document.body.appendChild(tip);

        // Posiciona próximo ao elemento alvo
        if (alvo) {
            alvo.scrollIntoView({ behavior: 'smooth', block: 'center' });
            alvo.style.outline = '3px solid #f0a500';
            alvo.style.outlineOffset = '4px';
            alvo.style.borderRadius = '4px';

            setTimeout(() => {
                const rect = alvo.getBoundingClientRect();
                const tipH = tip.offsetHeight;
                const tipW = tip.offsetWidth;
                let top  = rect.bottom + 12 + window.scrollY;
                let left = rect.left + window.scrollX;
                if (left + tipW > window.innerWidth - 20) left = window.innerWidth - tipW - 20;
                if (top + tipH > window.innerHeight + window.scrollY - 20) top = rect.top - tipH - 12 + window.scrollY;
                tip.style.top  = top + 'px';
                tip.style.left = left + 'px';
            }, 300);
        } else {
            // Centraliza se não achou o elemento
            tip.style.top  = '50%';
            tip.style.left = '50%';
            tip.style.transform = 'translate(-50%,-50%)';
        }

        document.getElementById('lp-ob-next').addEventListener('click', () => {
            if (alvo) { alvo.style.outline = ''; alvo.style.outlineOffset = ''; }
            tip.remove();
            if (passoAtual === totalPassos) onFim();
            else onNext();
        });

        document.getElementById('lp-ob-skip').addEventListener('click', () => {
            if (alvo) { alvo.style.outline = ''; alvo.style.outlineOffset = ''; }
            tip.remove();
            onFim();
        });
    }

    // ── Executa o tour ────────────────────────────────────────────────────────
    function iniciarTour(pagina) {
        const passos = PASSOS[pagina];
        if (!passos?.length) return;

        let idx = 0;

        function mostrarPasso(i) {
            const p   = passos[i];
            const alvo = document.querySelector(p.seletor);
            criarTooltip(
                p.titulo, p.texto, alvo,
                i + 1, passos.length,
                () => mostrarPasso(i + 1),
                () => { marcarVisto(pagina); }
            );
        }

        mostrarPasso(0);
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    window.addEventListener('DOMContentLoaded', () => {
        // Botão "Ver tour" sempre disponível
        const btnTour = document.getElementById('btn-iniciar-tour');
        if (btnTour) {
            const pagina = btnTour.dataset.pagina || detectarPagina();
            btnTour.addEventListener('click', () => {
                if (pagina) iniciarTour(pagina);
            });
        }

        // Tour automático na primeira visita
        const pagina = detectarPagina();
        if (pagina && !jaViu(pagina)) {
            setTimeout(() => iniciarTour(pagina), 800);
        }
    });

    // Expõe globalmente para uso manual
    window.lpTour = iniciarTour;

})();
