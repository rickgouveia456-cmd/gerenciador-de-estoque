
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
epis_bp = Blueprint('epis_bp', __name__)

@epis_bp.route('/almoxarifado/<int:alm_id>/epis')
@login_required
def epis(alm_id):
    u = usuario_atual()
    alm = Almoxarifado.query.get_or_404(alm_id)
    if u.perfil not in ('admin', 'almoxarife') and alm_id not in u.almoxarifados_permitidos():
        flash('Acesso negado.', 'danger')
        return redirect(url_for('main_bp.index'))
    lista = ItemEPI.query.filter_by(almoxarifado_id=alm_id, ativo=True).order_by(ItemEPI.nome).all()
    return render_template('epis.html', almoxarifado=alm, epis=lista)

@epis_bp.route('/almoxarifado/<int:alm_id>/epis/novo', methods=['GET', 'POST'])
@login_required
def novo_epi(alm_id):
    u = usuario_atual()
    alm = Almoxarifado.query.get_or_404(alm_id)
    if u.perfil not in ('admin', 'almoxarife'):
        flash('Acesso negado.', 'danger')
        return redirect(url_for('epis_bp.epis', alm_id=alm_id))
    if request.method == 'POST':
        e = ItemEPI(
            identificacao=request.form['identificacao'].strip(),
            nome=request.form['nome'].strip(),
            tamanho=request.form.get('tamanho', '').strip() or None,
            almoxarifado_id=alm_id,
            quantidade=int(request.form.get('quantidade', 1) or 1),
            local=request.form.get('local', '').strip() or None,
            observacao=request.form.get('observacao', '').strip() or None
        )
        db.session.add(e)
        db.session.commit()
        flash(f'EPI "{e.nome}" cadastrado!', 'success')
        return redirect(url_for('epis_bp.epis', alm_id=alm_id))
    return render_template('epi_form.html', almoxarifado=alm, epi=None, form_data={})

@epis_bp.route('/epi/<int:id>/status', methods=['POST'])
@login_required
def atualizar_status_epi(id):
    e = ItemEPI.query.get_or_404(id)
    u = usuario_atual()
    if u.perfil not in ('admin', 'almoxarife'):
        return jsonify({'error': 'Acesso negado'}), 403
    novo_status = request.form.get('status', 'disponivel')
    responsavel = request.form.get('responsavel', '').strip()
    motivo = request.form.get('motivo', '').strip()

    if novo_status == 'em_uso':
        e.status = 'em_uso'
        e.responsavel_atual = responsavel
        db.session.add(HistoricoEPI(
            item_epi_id=e.id,
            colaborador=responsavel,
            data_saida=agora(),
            registrado_por=u.nome,
            tipo_evento='uso'
        ))
        # Registrar automaticamente na FichaEPI
        if responsavel:
            try:
                from routes.epi_modulo import registrar_epi_na_ficha
                registrar_epi_na_ficha(
                    colaborador=responsavel,
                    almoxarifado_id=e.almoxarifado_id,
                    descricao=e.nome,
                    ca=None,
                    quantidade=e.quantidade or 1,
                    tamanho=e.tamanho,
                    registrado_por=u.nome
                )
            except Exception as _ex:
                import logging as _l
                _l.getLogger(__name__).error(f'registrar_epi_na_ficha (ItemEPI): {_ex}')
    elif novo_status == 'manutencao':
        hist_aberto = HistoricoEPI.query.filter_by(
            item_epi_id=e.id, data_devolucao=None
        ).order_by(HistoricoEPI.data_saida.desc()).first()
        if hist_aberto:
            hist_aberto.data_devolucao = agora()
        e.status = 'manutencao'
        e.responsavel_atual = motivo or 'Em manutenção'
        db.session.add(HistoricoEPI(
            item_epi_id=e.id,
            colaborador=u.nome,
            data_saida=agora(),
            registrado_por=u.nome,
            tipo_evento='manutencao',
            motivo_manutencao=motivo or None
        ))
    else:
        hist = HistoricoEPI.query.filter_by(
            item_epi_id=e.id, data_devolucao=None
        ).order_by(HistoricoEPI.data_saida.desc()).first()
        if hist:
            hist.data_devolucao = agora()
        e.status = 'disponivel'
        e.responsavel_atual = None

    db.session.commit()
    hist_novo = HistoricoEPI.query.filter_by(
        item_epi_id=e.id, data_devolucao=None
    ).order_by(HistoricoEPI.data_saida.desc()).first()
    hist_id = hist_novo.id if hist_novo else None
    return jsonify({
        'status': e.status,
        'responsavel': e.responsavel_atual or '',
        'hist_id': hist_id
    })

@epis_bp.route('/epi/<int:id>/historico')
@login_required
def historico_epi(id):
    e = ItemEPI.query.get_or_404(id)
    hist = HistoricoEPI.query.filter_by(item_epi_id=id).order_by(
        HistoricoEPI.data_saida.desc()
    ).limit(20).all()
    return jsonify({
        'nome': e.nome,
        'id': e.identificacao,
        'historico': [{
            'colaborador': h.colaborador,
            'data_saida': h.data_saida.strftime('%d/%m/%Y'),
            'data_devolucao': h.data_devolucao.strftime('%d/%m/%Y') if h.data_devolucao else None,
            'registrado_por': h.registrado_por or '—',
            'tipo_evento': h.tipo_evento or 'uso',
            'motivo_manutencao': h.motivo_manutencao or '',
            'foto_url': h.foto_url or ''
        } for h in hist]
    })

@epis_bp.route('/epi/<int:id>/deletar', methods=['POST'])
@admin_required
def deletar_epi(id):
    e = ItemEPI.query.get_or_404(id)
    alm_id = e.almoxarifado_id
    e.ativo = False
    db.session.commit()
    flash(f'EPI "{e.nome}" removido.', 'warning')
    return redirect(url_for('epis_bp.epis', alm_id=alm_id))

@epis_bp.route('/epi/historico/<int:hist_id>/foto', methods=['POST'])
@login_required
def upload_foto_historico_epi(hist_id):
    hist = HistoricoEPI.query.get_or_404(hist_id)
    data = request.get_json()
    if not data or 'foto' not in data:
        return jsonify({'error': 'Nenhuma foto recebida.'}), 400
    foto = data['foto']
    if not foto.startswith('data:image'):
        return jsonify({'error': 'Formato inválido.'}), 400
    if len(foto) > 5 * 1024 * 1024:
        return jsonify({'error': 'Foto muito grande. Máximo 5MB.'}), 400
    try:
        hist.foto_url = foto
        db.session.commit()
        return jsonify({'ok': True})
    except Exception as exc:
        return jsonify({'error': str(exc)}), 500

# ── GERENCIAR COLABORADORES ──────────────────────────────────────────────────
