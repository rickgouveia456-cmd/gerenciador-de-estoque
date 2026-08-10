ferramentas_bp = Blueprint('ferramentas_bp', __name__)

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

@ferramentas_bp.route('/almoxarifado/<int:alm_id>/ferramentas')
@login_required
def ferramentas(alm_id):
    u = usuario_atual()
    alm = Almoxarifado.query.get_or_404(alm_id)
    if u.perfil not in ('admin', 'almoxarife') and alm_id not in u.almoxarifados_permitidos():
        flash('Acesso negado.', 'danger')
        return redirect(url_for('index'))
    lista = Ferramenta.query.filter_by(almoxarifado_id=alm_id, ativo=True).order_by(Ferramenta.nome).all()
    return render_template('ferramentas.html', almoxarifado=alm, ferramentas=lista)

@ferramentas_bp.route('/almoxarifado/<int:alm_id>/ferramentas/nova', methods=['GET', 'POST'])
@login_required
def nova_ferramenta(alm_id):
    u = usuario_atual()
    alm = Almoxarifado.query.get_or_404(alm_id)
    if u.perfil not in ('admin', 'almoxarife'):
        flash('Acesso negado.', 'danger')
        return redirect(url_for('ferramentas', alm_id=alm_id))
    if request.method == 'POST':
        identificacao = request.form['identificacao'].strip()
        # Verificar duplicidade de ID em qualquer almoxarifado ativo
        existente = Ferramenta.query.filter_by(identificacao=identificacao, ativo=True).first()
        if existente:
            alm_existente = existente.almoxarifado
            flash(
                f'⚠️ ID "{identificacao}" já está cadastrado: '
                f'<strong>{existente.nome}</strong> — '
                f'Almoxarifado: <strong>{alm_existente.nome}</strong> — '
                f'Status: <strong>{existente.status.replace("_", " ").title()}</strong>',
                'danger'
            )
            return render_template('ferramenta_form.html', almoxarifado=alm, ferramenta=None,
                                   form_data=request.form)
        f = Ferramenta(
            identificacao=identificacao,
            nome=request.form['nome'].strip(),
            empresa=request.form.get('empresa', '').strip() or None,
            almoxarifado_id=alm_id,
            local=request.form.get('local', '').strip() or None,
            observacao=request.form.get('observacao', '').strip() or None
        )
        db.session.add(f)
        db.session.commit()
        flash(f'Ferramenta "{f.nome}" cadastrada!', 'success')
        return redirect(url_for('ferramentas', alm_id=alm_id))
    return render_template('ferramenta_form.html', almoxarifado=alm, ferramenta=None, form_data={})

@ferramentas_bp.route('/ferramenta/<int:id>/status', methods=['POST'])
@login_required
def atualizar_status_ferramenta(id):
    f = Ferramenta.query.get_or_404(id)
    u = usuario_atual()
    if u.perfil not in ('admin', 'almoxarife'):
        return jsonify({'error': 'Acesso negado'}), 403
    novo_status = request.form.get('status', 'disponivel')
    responsavel = request.form.get('responsavel', '').strip()
    motivo = request.form.get('motivo', '').strip()

    if novo_status == 'em_uso':
        f.status = 'em_uso'
        f.responsavel_atual = responsavel
        f.data_saida = agora()
        db.session.add(HistoricoFerramenta(
            ferramenta_id=f.id,
            colaborador=responsavel,
            data_saida=f.data_saida,
            registrado_por=u.nome,
            tipo_evento='uso'
        ))
    elif novo_status == 'manutencao':
        # Fechar registro aberto se houver
        hist_aberto = HistoricoFerramenta.query.filter_by(
            ferramenta_id=f.id, data_devolucao=None
        ).order_by(HistoricoFerramenta.data_saida.desc()).first()
        if hist_aberto:
            hist_aberto.data_devolucao = agora()
        f.status = 'manutencao'
        f.responsavel_atual = motivo or 'Em manutenção'
        f.data_saida = agora()
        db.session.add(HistoricoFerramenta(
            ferramenta_id=f.id,
            colaborador=u.nome,
            data_saida=f.data_saida,
            registrado_por=u.nome,
            tipo_evento='manutencao',
            motivo_manutencao=motivo or None
        ))
    else:
        # Devolver / disponivel
        hist = HistoricoFerramenta.query.filter_by(
            ferramenta_id=f.id, data_devolucao=None
        ).order_by(HistoricoFerramenta.data_saida.desc()).first()
        if hist:
            hist.data_devolucao = agora()
        f.status = 'disponivel'
        f.responsavel_atual = None
        f.data_saida = None

    db.session.commit()
    data_saida_iso = f.data_saida.isoformat() if f.data_saida else None
    # Retorna hist_id para o frontend abrir câmera de foto
    hist_novo = HistoricoFerramenta.query.filter_by(
        ferramenta_id=f.id, data_devolucao=None
    ).order_by(HistoricoFerramenta.data_saida.desc()).first()
    hist_id = hist_novo.id if hist_novo else None
    return jsonify({
        'status': f.status,
        'responsavel': f.responsavel_atual or '',
        'data_saida': data_saida_iso,
        'hist_id': hist_id
    })

@ferramentas_bp.route('/ferramenta/<int:id>/historico')
@login_required
def historico_ferramenta(id):
    """Retorna o histórico de uso da ferramenta em JSON."""
    f = Ferramenta.query.get_or_404(id)
    hist = HistoricoFerramenta.query.filter_by(ferramenta_id=id).order_by(
        HistoricoFerramenta.data_saida.desc()
    ).limit(20).all()
    return jsonify({
        'ferramenta': f.nome,
        'id': f.identificacao,
        'empresa': f.empresa or '',
        'historico': [{
            'colaborador': h.colaborador,
            'data_saida': h.data_saida.strftime('%d/%m/%Y %H:%M'),
            'data_devolucao': h.data_devolucao.strftime('%d/%m/%Y %H:%M') if h.data_devolucao else None,
            'registrado_por': h.registrado_por or '—',
            'tipo_evento': h.tipo_evento or 'uso',
            'motivo_manutencao': h.motivo_manutencao or '',
            'foto_url': h.foto_url or ''
        } for h in hist]
    })

@ferramentas_bp.route('/ferramenta/<int:id>/deletar', methods=['POST'])
@admin_required
def deletar_ferramenta(id):
    f = Ferramenta.query.get_or_404(id)
    alm_id = f.almoxarifado_id
    f.ativo = False
    db.session.commit()
    flash(f'Ferramenta "{f.nome}" removida.', 'warning')
    return redirect(url_for('ferramentas', alm_id=alm_id))

@ferramentas_bp.route('/api/ferramenta/verificar-id')
@login_required
def verificar_id_ferramenta():
    if _check_api_rate(request.remote_addr or '0.0.0.0'):
        return jsonify({'disponivel': True}), 429
    """Verifica se um ID/patrimônio já está cadastrado em qualquer almoxarifado."""
    identificacao = request.args.get('id', '').strip()
    excluir_id = request.args.get('excluir', type=int)  # para edição futura
    if not identificacao or identificacao == '__noop__':
        return jsonify({'disponivel': True})
    q = Ferramenta.query.filter_by(identificacao=identificacao, ativo=True)
    if excluir_id:
        q = q.filter(Ferramenta.id != excluir_id)
    existente = q.first()
    if existente:
        return jsonify({
            'disponivel': False,
            'nome': existente.nome,
            'almoxarifado': existente.almoxarifado.nome,
            'status': existente.status,
            'empresa': existente.empresa or 'Própria'
        })
    return jsonify({'disponivel': True})

@ferramentas_bp.route('/api/ferramentas/empresas')
@login_required
def api_empresas_ferramentas():
    """Retorna lista de empresas já cadastradas nas ferramentas para autocomplete."""
    q = request.args.get('q', '').strip()
    empresas = db.session.query(Ferramenta.empresa)\
        .filter(Ferramenta.ativo == True, Ferramenta.empresa != None)\
        .distinct().all()
    nomes = [e[0] for e in empresas if e[0] and q.lower() in e[0].lower()]
    return jsonify(sorted(nomes)[:10])

# ── FOTO DE RETIRADA — SALVA DIRETO NO BANCO ─────────────────────────────────
@ferramentas_bp.route('/ferramenta/historico/<int:hist_id>/foto', methods=['POST'])
@login_required
def upload_foto_retirada(hist_id):
    """Recebe foto em base64 do frontend e salva direto no PostgreSQL."""
    hist = HistoricoFerramenta.query.get_or_404(hist_id)
    u = usuario_atual()
    ferr = db.session.get(Ferramenta, hist.ferramenta_id)
    if u.perfil not in ('admin', 'almoxarife') and (
            ferr and ferr.almoxarifado_id not in u.almoxarifados_permitidos()):
        return jsonify({'error': 'Acesso negado.'}), 403
    data = request.get_json()
    if not data or 'foto' not in data:
        return jsonify({'error': 'Nenhuma foto recebida.'}), 400

    foto = data['foto']
    # Aceita apenas imagem base64
    if not foto.startswith('data:image'):
        return jsonify({'error': 'Formato inválido.'}), 400
    # Limite de 5MB por foto
    if len(foto) > 5 * 1024 * 1024:
        return jsonify({'error': 'Foto muito grande. Máximo 5MB.'}), 400

    try:
        hist.foto_url = foto
        db.session.commit()
        logger.info(f'FOTO: salva no banco — hist_id={hist_id} tamanho={len(foto)} bytes')
        return jsonify({'ok': True})
    except Exception as e:
        logger.error(f'FOTO: erro ao salvar — {e}')
        return jsonify({'error': str(e)}), 500

# ── FOTO DE EPI — SALVA DIRETO NO BANCO ──────────────────────────────────────
@ferramentas_bp.route('/movimentacao/<int:mov_id>/foto', methods=['POST'])
@login_required
def upload_foto_epi(mov_id):
    """Recebe foto em base64 do frontend e salva na movimentação de EPI."""
    mov = Movimentacao.query.get_or_404(mov_id)
    u = usuario_atual()
    it = db.session.get(Item, mov.item_id)
    if u.perfil not in ('admin', 'almoxarife') and (
            it and it.almoxarifado_id not in u.almoxarifados_permitidos()):
        return jsonify({'error': 'Acesso negado.'}), 403
    data = request.get_json()
    if not data or 'foto' not in data:
        return jsonify({'error': 'Nenhuma foto recebida.'}), 400

    foto = data['foto']
    if not foto.startswith('data:image'):
        return jsonify({'error': 'Formato inválido.'}), 400
    if len(foto) > 5 * 1024 * 1024:
        return jsonify({'error': 'Foto muito grande. Máximo 5MB.'}), 400

    try:
        mov.foto_url = foto
        db.session.commit()
        logger.info(f'FOTO EPI: salva — mov_id={mov_id} tamanho={len(foto)} bytes')
        return jsonify({'ok': True})
    except Exception as e:
        logger.error(f'FOTO EPI: erro — {e}')
        return jsonify({'error': str(e)}), 500

# ── FROTA DE EPIs / UNIFORMES ─────────────────────────────────────────────────
