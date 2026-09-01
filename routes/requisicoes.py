
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
    _check_rate_limit, _register_attempt, _clear_attempts, _check_api_rate,
    is_admin_ou_ggo, admin_ou_ggo_required, almoxarifados_do_ggo)

logger = logging.getLogger(__name__)
requisicoes_bp = Blueprint('requisicoes_bp', __name__)

# Import lazy para evitar circular — chamado em runtime
def _reg_epi(colaborador, almoxarifado_id, descricao, ca, quantidade, tamanho, registrado_por):
    from routes.epi_modulo import registrar_epi_na_ficha
    registrar_epi_na_ficha(colaborador, almoxarifado_id, descricao, ca, quantidade, tamanho, registrado_por)

@requisicoes_bp.route('/mestre/requisicoes')
@login_required
def mestre_requisicoes():
    """Lista de requisições do mestre logado."""
    u = usuario_atual()

    pode_fazer = (
        u.perfil in ('mestre', 'tecnico_seguranca', 'admin', 'ggo', 'almoxarife') or
        u.pode_requisitar or
        any(p.permissao == 'fazer_requisicao' for p in u.permissoes_extras)
    )
    if not pode_fazer:
        flash('Acesso negado.', 'danger')
        return redirect(url_for('main_bp.index'))

    if is_admin_ou_ggo(u):
        reqs = RequisicaoMestre.query.order_by(RequisicaoMestre.data_criacao.desc()).all()
    elif u.perfil == 'almoxarife' and u.almoxarifado_id:
        reqs = RequisicaoMestre.query.filter_by(almoxarifado_id=u.almoxarifado_id).order_by(RequisicaoMestre.data_criacao.desc()).all()
    else:
        # mestre, tecnico, engenheiro com pode_requisitar — vê só as suas
        reqs = RequisicaoMestre.query.filter_by(mestre_id=u.id).order_by(RequisicaoMestre.data_criacao.desc()).all()

    return render_template('mestre_requisicoes.html', requisicoes=reqs)

@requisicoes_bp.route('/mestre/requisicoes/nova', methods=['GET', 'POST'])
@login_required
def mestre_requisicao_nova():
    """Mestre, técnico, engenheiro (pode_requisitar) e admin criam requisição."""
    u = usuario_atual()

    # ── Verificação de acesso ────────────────────────────────────────────────
    pode_fazer = (
        u.perfil in ('mestre', 'tecnico_seguranca', 'admin', 'ggo') or
        u.pode_requisitar or
        any(p.permissao == 'fazer_requisicao' for p in u.permissoes_extras)
    )
    if not pode_fazer:
        flash('Você não tem permissão para criar requisições.', 'danger')
        return redirect(url_for('main_bp.index'))

    # ── Almoxarifados que o usuário pode requisitar ──────────────────────────
    if is_admin_ou_ggo(u):
        almoxarifados = almoxarifados_do_ggo(u) if u.perfil == 'ggo' else Almoxarifado.query.all()
    elif u.perfil == 'tecnico_seguranca':
        ids = u.almoxarifados_permitidos()
        almoxarifados = Almoxarifado.query.filter(Almoxarifado.id.in_(ids)).all() if ids else (
            [u.almoxarifado] if u.almoxarifado_id else []
        )
    else:
        # mestre, colaborador com pode_requisitar, engenheiro — usa almoxarifado vinculado
        if not u.almoxarifado_id:
            flash('Você não está vinculado a nenhum almoxarifado. Contate o administrador.', 'warning')
            return redirect(url_for('requisicoes_bp.mestre_requisicoes'))
        almoxarifados = [u.almoxarifado]

    if not almoxarifados:
        flash('Nenhum almoxarifado disponível para requisição.', 'warning')
        return redirect(url_for('requisicoes_bp.mestre_requisicoes'))

    itens_json = {}
    for alm in almoxarifados:
        # Mestre NÃO pode requisitar EPIs — filtra categoria epi
        # Demais perfis podem requisitar tudo
        if u.perfil == 'mestre':
            itens_filtrados = [it for it in alm.itens if it.ativo and it.quantidade > 0 and it.categoria != 'epi']
        else:
            itens_filtrados = [it for it in alm.itens if it.ativo and it.quantidade > 0]

        itens_json[str(alm.id)] = [
            {'id': it.id, 'nome': it.nome, 'quantidade': it.quantidade,
             'unidade': it.unidade, 'categoria': it.categoria or 'geral'}
            for it in itens_filtrados
        ]

    if request.method == 'POST':
        colaborador = request.form.get('colaborador', '').strip()
        alm_id = int(request.form.get('almoxarifado_id', u.almoxarifado_id or 0))
        observacao = request.form.get('observacao', '')

        if not colaborador:
            flash('Informe o nome do colaborador que vai buscar os materiais.', 'warning')
            return redirect(url_for('requisicoes_bp.mestre_requisicao_nova'))

        # Coletar itens
        indices = set()
        for key in request.form.keys():
            if key.startswith('item_id_'):
                try:
                    indices.add(int(key.split('_')[-1]))
                except ValueError:
                    pass

        if not indices:
            flash('Adicione pelo menos um item à requisição.', 'warning')
            return redirect(url_for('requisicoes_bp.mestre_requisicao_nova'))

        req = RequisicaoMestre(
            mestre_id=u.id,
            colaborador=colaborador,
            almoxarifado_id=alm_id,
            observacao=observacao,
            status='pendente',
            data_criacao=agora()
        )
        db.session.add(req)
        db.session.flush()  # gera o id

        # Gera número de protocolo único: REQ-AAAAMMDD-XXXX
        req.protocolo = f"REQ-{req.data_criacao.strftime('%Y%m%d')}-{req.id:04d}"

        for i in sorted(indices):
            item_id = request.form.get(f'item_id_{i}')
            qtd_str = request.form.get(f'quantidade_{i}')
            obs_item = request.form.get(f'observacao_{i}', '')
            if not item_id or not qtd_str:
                continue
            try:
                qtd = float(qtd_str)
            except ValueError:
                continue
            if qtd <= 0:
                continue
            db.session.add(RequisicaoMestreItem(
                requisicao_id=req.id,
                item_id=int(item_id),
                quantidade=qtd,
                observacao=obs_item
            ))

        db.session.commit()
        flash_html(f'✅ Requisição <strong>{escape(req.protocolo or "#" + str(req.id))}</strong> enviada ao almoxarifado! Aguarde a separação.', 'success')
        return redirect(url_for('requisicoes_bp.mestre_requisicoes'))

    return render_template('mestre_requisicao_nova.html',
                           almoxarifados=almoxarifados,
                           itens_json=json.dumps(itens_json))

@requisicoes_bp.route('/mestre/requisicoes/<int:id>')
@login_required
def mestre_requisicao_detalhe(id):
    """Detalhe de uma requisição do mestre."""
    req = RequisicaoMestre.query.get_or_404(id)
    u = usuario_atual()
    # Mestre e técnico de segurança só veem as suas próprias
    if u.perfil in ('mestre', 'tecnico_seguranca') and req.mestre_id != u.id:
        flash('Acesso negado.', 'danger')
        return redirect(url_for('requisicoes_bp.mestre_requisicoes'))
    # Colaborador com pode_requisitar só vê as suas próprias
    if u.perfil == 'colaborador' and req.mestre_id != u.id:
        flash('Acesso negado.', 'danger')
        return redirect(url_for('requisicoes_bp.mestre_requisicoes'))
    # Almoxarife só vê do seu almoxarifado
    if u.perfil == 'almoxarife' and req.almoxarifado_id != u.almoxarifado_id:
        flash('Acesso negado.', 'danger')
        return redirect(url_for('requisicoes_bp.mestre_requisicoes'))
    return render_template('mestre_requisicao_detalhe.html', req=req)

@requisicoes_bp.route('/mestre/requisicoes/<int:id>/editar', methods=['GET', 'POST'])
@login_required
def mestre_requisicao_editar(id):
    """Almoxarife ou admin edita uma requisição pendente."""
    req = RequisicaoMestre.query.get_or_404(id)
    u = usuario_atual()
    if u.perfil not in ('admin', 'ggo', 'almoxarife'):
        flash('Apenas almoxarife, GGO ou admin pode editar requisições.', 'danger')
        return redirect(url_for('requisicoes_bp.mestre_requisicoes'))
    if req.status == 'entregue':
        flash('Não é possível editar uma requisição já entregue.', 'warning')
        return redirect(url_for('requisicoes_bp.mestre_requisicao_detalhe', id=id))

    if request.method == 'POST':
        req.colaborador = request.form.get('colaborador', req.colaborador).strip()
        req.observacao = request.form.get('observacao', req.observacao)
        # Atualizar quantidades dos itens
        for ri in req.itens:
            qtd_str = request.form.get(f'qtd_{ri.id}')
            obs_str = request.form.get(f'obs_{ri.id}', ri.observacao)
            if qtd_str:
                try:
                    ri.quantidade = float(qtd_str)
                except ValueError:
                    pass
            ri.observacao = obs_str
        db.session.commit()
        flash('Requisição atualizada!', 'success')
        return redirect(url_for('requisicoes_bp.mestre_requisicao_detalhe', id=id))

    return render_template('mestre_requisicao_editar.html', req=req)

@requisicoes_bp.route('/mestre/requisicoes/<int:id>/aprovar', methods=['POST'])
@login_required
def mestre_requisicao_aprovar(id):
    """Almoxarife aprova, recusa ou aprova parcialmente a requisição.

    Modos:
    - decisao=aprovada  → aprova todos os itens de uma vez
    - decisao=recusada  → recusa todos os itens
    - decisao=parcial   → avalia item a item via campos item_status_<ri_id>
                          e item_motivo_<ri_id> no formulário
    """
    req = RequisicaoMestre.query.get_or_404(id)
    u = usuario_atual()
    if u.perfil not in ('admin', 'ggo', 'almoxarife'):
        flash('Acesso negado.', 'danger')
        return redirect(url_for('requisicoes_bp.mestre_requisicoes'))
    if req.status != 'pendente':
        flash('Requisição não está pendente.', 'warning')
        return redirect(url_for('requisicoes_bp.mestre_requisicao_detalhe', id=id))

    decisao = request.form.get('decisao', 'aprovada')

    if decisao == 'parcial':
        # Aprovação item a item
        aprovados = 0
        recusados = 0
        for ri in req.itens:
            st = request.form.get(f'item_status_{ri.id}', 'aprovado')
            motivo = request.form.get(f'item_motivo_{ri.id}', '').strip()
            ri.status_item = st  # 'aprovado' ou 'recusado'
            ri.motivo_recusa = motivo if st == 'recusado' else None
            if st == 'aprovado':
                aprovados += 1
            else:
                recusados += 1

        # Define status geral da requisição
        if aprovados == 0:
            req.status = 'recusada'
            flash(f'❌ Requisição #{req.id} recusada — todos os itens foram recusados.', 'danger')
        elif recusados == 0:
            req.status = 'aprovada'
            flash(f'✅ Requisição #{req.id} aprovada! Separe os materiais e confirme a entrega.', 'success')
        else:
            req.status = 'parcial'
            flash(
                f'⚠️ Requisição #{req.id} aprovada parcialmente: '
                f'{aprovados} item(ns) aprovado(s), {recusados} recusado(s).',
                'warning'
            )
    elif decisao == 'recusada':
        req.status = 'recusada'
        for ri in req.itens:
            ri.status_item = 'recusado'
            ri.motivo_recusa = request.form.get('motivo_geral', '').strip() or None
        flash(f'❌ Requisição #{req.id} recusada.', 'danger')
    else:
        # aprovada inteira
        req.status = 'aprovada'
        for ri in req.itens:
            ri.status_item = 'aprovado'
            ri.motivo_recusa = None
        flash(f'✅ Requisição #{req.id} aprovada! Separe os materiais e confirme a entrega.', 'success')

    db.session.commit()
    return redirect(url_for('requisicoes_bp.mestre_requisicao_detalhe', id=id))

@requisicoes_bp.route('/mestre/requisicoes/<int:id>/entregar', methods=['POST'])
@login_required
def mestre_requisicao_entregar(id):
    """Almoxarife confirma entrega — baixa o estoque apenas dos itens aprovados."""
    req = RequisicaoMestre.query.get_or_404(id)
    u = usuario_atual()
    if u.perfil not in ('admin', 'ggo', 'almoxarife'):
        if request.headers.get('X-Requested-With') == 'XMLHttpRequest':
            return jsonify({'ok': False, 'msg': 'Acesso negado.'}), 403
        flash('Acesso negado.', 'danger')
        return redirect(url_for('requisicoes_bp.mestre_requisicoes'))
    if req.status not in ('pendente', 'aprovada', 'parcial'):
        if request.headers.get('X-Requested-With') == 'XMLHttpRequest':
            return jsonify({'ok': False, 'msg': 'Requisição já processada.'})
        flash('Requisição já foi entregue, recusada ou cancelada.', 'warning')
        return redirect(url_for('requisicoes_bp.mestre_requisicao_detalhe', id=id))

    # Itens a entregar: aprovados (ou todos se não houve aprovação por item)
    itens_a_entregar = [
        ri for ri in req.itens
        if ri.status_item in ('aprovado', 'pendente')
    ]

    erros = []
    for ri in itens_a_entregar:
        if ri.quantidade > ri.item.quantidade:
            erros.append(f'"{ri.item.nome}": apenas {ri.item.quantidade} {ri.item.unidade} disponível')

    if erros:
        if request.headers.get('X-Requested-With') == 'XMLHttpRequest':
            return jsonify({'ok': False, 'msg': ' | '.join(erros)})
        for e in erros:
            flash(f'⚠️ {e}', 'danger')
        return redirect(url_for('requisicoes_bp.mestre_requisicao_detalhe', id=id))

    for ri in itens_a_entregar:
        ri.item.quantidade = round(ri.item.quantidade - ri.quantidade, 4)
        ri.status_item = 'aprovado'
        db.session.add(Movimentacao(
            tipo='saida',
            quantidade=ri.quantidade,
            responsavel=req.mestre.nome,
            observacao=f'Req. Mestre #{req.id} — Colaborador: {req.colaborador}',
            item_id=ri.item.id
        ))
        # Registrar automaticamente na FichaEPI se for EPI
        if ri.item.categoria == 'epi':
            _reg_epi(
                colaborador=req.colaborador,
                almoxarifado_id=req.almoxarifado_id,
                descricao=ri.item.nome,
                ca=ri.item.ca,
                quantidade=ri.quantidade,
                tamanho=None,
                registrado_por=u.nome
            )

    req.status = 'entregue'
    req.data_entrega = agora()
    req.entregue_por_id = u.id
    db.session.commit()

    if request.headers.get('X-Requested-With') == 'XMLHttpRequest':
        return jsonify({'ok': True, 'req_id': req.id})

    flash(f'✅ Entrega confirmada! Estoque atualizado para {len(itens_a_entregar)} item(ns).', 'success')
    return redirect(url_for('requisicoes_bp.mestre_requisicao_detalhe', id=id))
@requisicoes_bp.route('/mestre/requisicoes/<int:id>/foto', methods=['POST'])
@login_required
def mestre_requisicao_foto(id):
    """Salva foto de comprovante de entrega na requisição."""
    req = RequisicaoMestre.query.get_or_404(id)
    u = usuario_atual()
    if u.perfil not in ('admin', 'ggo', 'almoxarife'):
        return jsonify({'ok': False, 'error': 'Acesso negado.'}), 403
    data = request.get_json(silent=True) or {}
    foto = data.get('foto', '')
    if not foto or not foto.startswith('data:image'):
        return jsonify({'ok': False, 'error': 'Foto inválida.'})
    req.foto_url = foto
    db.session.commit()
    return jsonify({'ok': True})

@requisicoes_bp.route('/mestre/requisicoes/<int:id>/cancelar', methods=['POST'])
@login_required
def mestre_requisicao_cancelar(id):
    """Cancela uma requisição pendente ou aprovada."""
    req = RequisicaoMestre.query.get_or_404(id)
    u = usuario_atual()
    # Mestre, técnico de segurança e colaborador com pode_requisitar só cancelam as suas
    if u.perfil in ('mestre', 'tecnico_seguranca') and req.mestre_id != u.id:
        flash('Acesso negado.', 'danger')
        return redirect(url_for('requisicoes_bp.mestre_requisicoes'))
    if u.perfil == 'colaborador' and req.mestre_id != u.id:
        flash('Acesso negado.', 'danger')
        return redirect(url_for('requisicoes_bp.mestre_requisicoes'))
    if req.status == 'entregue':
        flash('Não é possível cancelar uma requisição já entregue.', 'warning')
        return redirect(url_for('requisicoes_bp.mestre_requisicao_detalhe', id=id))

    req.status = 'cancelada'
    db.session.commit()
    flash(f'Requisição #{req.id} cancelada.', 'warning')
    return redirect(url_for('requisicoes_bp.mestre_requisicoes'))

@requisicoes_bp.route('/api/mestre/notificacoes')
@login_required
def api_mestre_notificacoes():
    """Retorna notificações baseadas no status das requisições do mestre."""
    u = usuario_atual()
    if u.perfil not in ('mestre', 'tecnico_seguranca'):
        return jsonify([])
    # Busca requisições não-pendentes e não-canceladas do mestre
    # A notificação é baseada no status atual — o frontend controla o que já foi visto via localStorage
    reqs = RequisicaoMestre.query.filter_by(mestre_id=u.id).filter(
        RequisicaoMestre.status.in_(['aprovada', 'parcial', 'recusada', 'entregue'])
    ).order_by(RequisicaoMestre.data_criacao.desc()).limit(10).all()

    result = []
    for r in reqs:
        if r.status == 'aprovada':
            msg = f'✅ Requisição #{r.id} aprovada! Envie o colaborador buscar.'
            tipo = 'success'
        elif r.status == 'parcial':
            msg = f'⚠️ Requisição #{r.id} aprovada parcialmente. Verifique os itens.'
            tipo = 'warning'
        elif r.status == 'recusada':
            msg = f'❌ Requisição #{r.id} foi recusada pelo almoxarifado.'
            tipo = 'danger'
        elif r.status == 'entregue':
            msg = f'📦 Requisição #{r.id} entregue ao colaborador {r.colaborador}.'
            tipo = 'info'
        else:
            continue
        result.append({'id': r.id, 'msg': msg, 'tipo': tipo, 'status': r.status})
    return jsonify(result)

@requisicoes_bp.route('/api/mestre/notificacoes/marcar-lidas', methods=['POST'])
@login_required
def marcar_notificacoes_lidas():
    """Endpoint mantido para compatibilidade — controle feito no frontend."""
    return jsonify({'ok': True})

# ── ROTA ESPECIAL PARA REATIVAR TODOS OS ITENS ──────────────────────────────
