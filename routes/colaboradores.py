
from flask import Blueprint, render_template, request, redirect, url_for, jsonify, flash, send_file, session
from markupsafe import Markup, escape
from datetime import datetime, date, timedelta
import io, os, json, re, logging
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter
from extensions import db
from models import (agora, Almoxarifado, Item, Movimentacao, Requisicao,
    RequisicaoMestre, RequisicaoMestreItem, Usuario, Colaborador,
    Ferramenta, HistoricoFerramenta, ItemEPI, HistoricoEPI,
    AcessoExtra, PermissaoExtra)
from core import (login_required, admin_required, almoxarife_required,
    usuario_atual, flash_html, usuario_tem_acesso_almoxarifado,
    usuario_tem_acesso_item, PERMISSOES_DISPONIVEIS,
    _check_rate_limit, _register_attempt, _clear_attempts, _check_api_rate)

logger = logging.getLogger(__name__)
colaboradores_bp = Blueprint('colaboradores_bp', __name__)

@colaboradores_bp.route('/colaboradores')
@login_required
def colaboradores():
    u = usuario_atual()
    if u.perfil not in ('admin', 'almoxarife', 'analista'):
        flash('Acesso negado.', 'danger')
        return redirect(url_for('main_bp.index'))
    cols = Colaborador.query.order_by(Colaborador.ativo.desc(), Colaborador.nome).all()

    # Determinar escopo, obra e cidade do almoxarife pelo seu almoxarifado vinculado
    escopo_almoxarife = None
    obra_almoxarife = None
    cidade_almoxarife = None
    if u.perfil == 'almoxarife' and u.almoxarifado_id:
        alm = db.session.get(Almoxarifado, u.almoxarifado_id)
        if alm:
            obra_almoxarife = (alm.obra or '').lower().strip() or None
            cidade_almoxarife = (alm.cidade or '').lower().strip() or None
            nome_lower = alm.nome.lower()
            for esc in ['estrutura', 'acabamento', 'infraestrutura', 'forma', 'acampamento']:
                if esc in nome_lower:
                    escopo_almoxarife = esc
                    break

    # Almoxarife vê apenas colaboradores da sua obra E frente
    if u.perfil == 'almoxarife':
        def colab_pertence(c):
            # Filtro por obra — se almoxarife tem obra definida, só vê colaboradores
            # com a mesma obra. Colaboradores sem obra passam apenas se cidade bater.
            if obra_almoxarife:
                obra_colab = (c.obra or '').lower().strip()
                if obra_colab and obra_colab != obra_almoxarife:
                    return False
            # Filtro por cidade — se almoxarife tem cidade, colaboradores sem cidade
            # ou de outra cidade ficam fora
            if cidade_almoxarife:
                cidade_colab = (c.cidade or '').lower().strip()
                if cidade_colab != cidade_almoxarife:
                    return False
            # Filtro por escopo (frente de obra)
            if escopo_almoxarife and c.escopo:
                if c.escopo.lower().strip() != escopo_almoxarife:
                    return False
            return True
        cols = [c for c in cols if colab_pertence(c)]

    # Analista vê apenas colaboradores da sua cidade E escopo
    if u.perfil == 'analista':
        # Determina cidade do analista pelo almoxarifado vinculado
        cidade_analista = None
        if u.almoxarifado_id:
            alm_analista = db.session.get(Almoxarifado, u.almoxarifado_id)
            if alm_analista:
                cidade_analista = (alm_analista.cidade or '').lower().strip() or None

        def analista_pertence(c):
            # Filtro por cidade — se analista tem cidade definida, só vê colaboradores
            # da mesma cidade. Colaboradores sem cidade definida ficam visíveis apenas
            # para admin.
            if cidade_analista:
                cidade_colab = (c.cidade or '').lower().strip()
                if cidade_colab != cidade_analista:
                    return False
            # Filtro por escopo
            if u.escopo and c.escopo:
                if c.escopo.lower().strip() != u.escopo.lower().strip():
                    return False
            return True

        cols = [c for c in cols if analista_pertence(c)]
    from collections import OrderedDict
    grupos = OrderedDict([
        ('estrutura',      {'label': '🏗️ Estrutura',      'cor': '#f0a500', 'colaboradores': []}),
        ('infraestrutura', {'label': '🔧 Infraestrutura',  'cor': '#0ea5e9', 'colaboradores': []}),
        ('acabamento',     {'label': '🏕️ Acabamento',      'cor': '#22c55e', 'colaboradores': []}),
        ('sem_escopo',     {'label': '📋 Sem Escopo',       'cor': '#94a3b8', 'colaboradores': []}),
    ])
    for c in cols:
        escopo = (c.escopo or '').lower().strip()
        if escopo in grupos:
            grupos[escopo]['colaboradores'].append(c)
        else:
            grupos['sem_escopo']['colaboradores'].append(c)
    return render_template('colaboradores.html', colaboradores=cols, grupos=grupos)

@colaboradores_bp.route('/colaboradores/novo', methods=['POST'])
@login_required
def novo_colaborador():
    u = usuario_atual()
    if u.perfil not in ('admin', 'almoxarife', 'analista'):
        flash('Acesso negado.', 'danger')
        return redirect(url_for('main_bp.index'))
    nome = request.form.get('nome', '').strip()
    funcao = request.form.get('funcao', '').strip()
    escopo = request.form.get('escopo', '').strip()
    obra = request.form.get('obra', '').strip()
    cidade = request.form.get('cidade', '').strip()
    tipo = request.form.get('tipo', 'peao').strip()

    # Se almoxarife não preencheu obra/cidade, usa os do almoxarifado dele
    if not obra and u.perfil == 'almoxarife' and u.almoxarifado_id:
        alm = db.session.get(Almoxarifado, u.almoxarifado_id)
        if alm:
            obra = alm.obra or ''
            cidade = alm.cidade or ''
    if not nome:
        flash('Informe o nome do colaborador.', 'warning')
        return redirect(url_for('colaboradores_bp.colaboradores'))
    # Evita duplicata
    if Colaborador.query.filter(Colaborador.nome.ilike(nome)).first():
        flash(f'Colaborador "{nome}" já está cadastrado.', 'warning')
        return redirect(url_for('colaboradores_bp.colaboradores'))
    db.session.add(Colaborador(nome=nome, funcao=funcao or None, escopo=escopo or None,
                               obra=obra or None, cidade=cidade or None, tipo=tipo or 'peao'))
    db.session.commit()
    flash(f'✅ Colaborador "{nome}" cadastrado!', 'success')
    return redirect(url_for('colaboradores_bp.colaboradores'))

@colaboradores_bp.route('/colaboradores/<int:id>/desativar', methods=['POST'])
@login_required
def desativar_colaborador(id):
    u = usuario_atual()
    if u.perfil not in ('admin', 'almoxarife', 'analista'):
        flash('Acesso negado.', 'danger')
        return redirect(url_for('main_bp.index'))
    c = Colaborador.query.get_or_404(id)
    c.ativo = False
    db.session.commit()
    flash(f'Colaborador "{c.nome}" desativado.', 'warning')
    return redirect(url_for('colaboradores_bp.colaboradores'))

@colaboradores_bp.route('/colaboradores/<int:id>/reativar', methods=['POST'])
@login_required
def reativar_colaborador(id):
    u = usuario_atual()
    if u.perfil not in ('admin', 'almoxarife', 'analista'):
        flash('Acesso negado.', 'danger')
        return redirect(url_for('main_bp.index'))
    c = Colaborador.query.get_or_404(id)
    c.ativo = True
    db.session.commit()
    flash(f'Colaborador "{c.nome}" reativado.', 'success')
    return redirect(url_for('colaboradores_bp.colaboradores'))

# ── GERENCIAR USUÁRIOS (só admin) ────────────────────────────────────────────
