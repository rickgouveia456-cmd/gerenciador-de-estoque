
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
usuarios_bp = Blueprint('usuarios_bp', __name__)

@usuarios_bp.route('/usuarios')
@admin_ou_ggo_required
def usuarios():
    from collections import OrderedDict
    u = usuario_atual()
    if u.perfil == 'ggo':
        cidade = (u.escopo or '').strip().lower()
        if cidade:
            # GGO vê apenas usuários vinculados a almoxarifados da sua cidade
            ids_alm = [a.id for a in Almoxarifado.query.filter(
                db.func.lower(Almoxarifado.cidade) == cidade
            ).all()]
            todos = Usuario.query.filter(
                db.or_(
                    Usuario.almoxarifado_id.in_(ids_alm),
                    Usuario.id == u.id  # sempre vê a si mesmo
                )
            ).order_by(Usuario.nome).all()
        else:
            # GGO sem cidade definida — só vê a si mesmo
            todos = [u]
    else:
        todos = Usuario.query.order_by(Usuario.nome).all()
    # Agrupar por perfil
    grupos = OrderedDict([
        ('admin',             {'label': '👑 Admin / Fundador',     'label_curto': 'Admin',      'icone': '👑', 'cor': '#7c3aed', 'usuarios': []}),
        ('ggo',               {'label': '🏗️ GGO',                  'label_curto': 'GGO',        'icone': '🏗️', 'cor': '#f59e0b', 'usuarios': []}),
        ('almoxarife',        {'label': '📦 Almoxarife',           'label_curto': 'Almoxarife', 'icone': '📦', 'cor': '#0ea5e9', 'usuarios': []}),
        ('mestre',            {'label': '🦺 Mestre de Obra',       'label_curto': 'Mestre',     'icone': '🦺', 'cor': '#f0a500', 'usuarios': []}),
        ('tecnico_seguranca', {'label': '🔒 Técnico de Segurança', 'label_curto': 'Téc. Seg.',  'icone': '🔒', 'cor': '#3b82f6', 'usuarios': []}),
        ('analista',          {'label': '📊 Analista',             'label_curto': 'Analista',   'icone': '📊', 'cor': '#10b981', 'usuarios': []}),
        ('colaborador',       {'label': '👔 Engenheiro',           'label_curto': 'Engenheiro', 'icone': '👔', 'cor': '#64748b', 'usuarios': []}),
    ])
    for usr in todos:
        perfil = usr.perfil if usr.perfil in grupos else 'colaborador'
        grupos[perfil]['usuarios'].append(usr)
    return render_template('usuarios.html', grupos=grupos,
                           usuarios=todos,
                           permissoes_disponiveis=PERMISSOES_DISPONIVEIS)



# ── PERMISSÕES EXTRAS DE FUNÇÃO ───────────────────────────────────────────────

PERMISSOES_DISPONIVEIS = {
    'fazer_requisicao': 'Fazer Requisições ao Almoxarifado',
    'ver_relatorios':   'Ver Relatórios (Consumo, Ficha EPI)',
    'ver_alertas':      'Ver Alertas de Estoque',
}

@usuarios_bp.route('/usuarios/novo', methods=['GET', 'POST'])
@admin_ou_ggo_required
def novo_usuario():
    almoxarifados = Almoxarifado.query.all()
    if request.method == 'POST':
        login = request.form['login'].strip()
        # Verifica se login já existe
        if Usuario.query.filter_by(login=login).first():
            flash(f'⚠️ O login "{login}" já está em uso. Escolha outro.', 'danger')
            return render_template('form_usuario.html', usuario=None, almoxarifados=almoxarifados,
                                   permissoes_disponiveis=PERMISSOES_DISPONIVEIS)
        u = Usuario(
            nome=request.form['nome'],
            login=login,
            perfil=request.form['perfil'],
            almoxarifado_id=request.form.get('almoxarifado_id') or None,
            email=request.form.get('email', '').strip() or None,
            escopo=request.form.get('escopo', '').strip() or None,
            regiao=request.form.get('regiao', '').strip() or None
        )
        senha_nova = request.form.get('senha', '')
        if len(senha_nova) < 8:
            flash('A senha deve ter pelo menos 8 caracteres.', 'danger')
            return render_template('form_usuario.html', usuario=None, almoxarifados=almoxarifados,
                                   permissoes_disponiveis=PERMISSOES_DISPONIVEIS)
        u.set_senha(senha_nova)
        db.session.add(u)
        db.session.commit()
        flash(f'Usuário "{u.nome}" criado!', 'success')
        return redirect(url_for('usuarios_bp.usuarios'))
    return render_template('form_usuario.html', usuario=None, almoxarifados=almoxarifados,
                           permissoes_disponiveis=PERMISSOES_DISPONIVEIS)

@usuarios_bp.route('/usuarios/<int:id>/editar', methods=['GET', 'POST'])
@admin_ou_ggo_required
def editar_usuario(id):
    u = Usuario.query.get_or_404(id)
    atual = usuario_atual()

    # Admin não pode editar a si mesmo via esta rota (evita auto-lockout acidental)
    # e não pode editar outro admin de nível igual, exceto a si mesmo
    if u.id != atual.id and u.perfil == 'admin' and atual.perfil == 'admin':
        # Permite apenas se o usuário atual for o mesmo sendo editado
        # ou se o alvo não for admin — proteção contra escalada de privilégio
        pass  # admins podem editar outros admins (necessário para gestão)

    almoxarifados = Almoxarifado.query.all()
    if request.method == 'POST':
        # Impede que um admin remova o próprio perfil de admin acidentalmente
        novo_perfil = request.form['perfil']
        if u.id == atual.id and novo_perfil != 'admin':
            flash('Você não pode remover seu próprio perfil de administrador.', 'danger')
            return redirect(url_for('usuarios_bp.editar_usuario', id=id))
        u.nome = request.form['nome']
        u.login = request.form['login']
        u.perfil = novo_perfil
        u.almoxarifado_id = request.form.get('almoxarifado_id') or None
        u.email = request.form.get('email', '').strip() or None
        u.escopo = request.form.get('escopo', '').strip() or None
        u.regiao = request.form.get('regiao', '').strip() or None
        u.ativo = 'ativo' in request.form
        # Impede que admin desative a si mesmo
        if u.id == atual.id:
            u.ativo = True
        if request.form.get('senha'):
            if len(request.form['senha']) < 8:
                flash('A senha deve ter pelo menos 8 caracteres.', 'danger')
                return redirect(url_for('usuarios_bp.editar_usuario', id=id))
            u.set_senha(request.form['senha'])
        u.pode_requisitar = 'pode_requisitar' in request.form
        u.pode_ver_alertas = 'pode_ver_alertas' in request.form
        db.session.commit()
        flash('Usuário atualizado!', 'success')
        return redirect(url_for('usuarios_bp.usuarios'))
    return render_template('form_usuario.html', usuario=u, almoxarifados=almoxarifados,
                           permissoes_disponiveis=PERMISSOES_DISPONIVEIS)

@usuarios_bp.route('/usuarios/<int:id>/deletar', methods=['POST'])
@admin_ou_ggo_required
def deletar_usuario(id):
    u = Usuario.query.get_or_404(id)
    atual = usuario_atual()

    # Fundador (rick) pode deletar qualquer conta exceto a própria
    # Admin comum não pode deletar a si mesmo
    if u.id == atual.id:
        flash('Você não pode remover sua própria conta.', 'danger')
        return redirect(url_for('usuarios_bp.usuarios'))

    try:
        # Desvincular requisições vinculadas antes de deletar (evita erro de FK)
        RequisicaoMestre.query.filter_by(mestre_id=u.id).update({'mestre_id': atual.id})
        RequisicaoMestre.query.filter_by(entregue_por_id=u.id).update({'entregue_por_id': None})
        db.session.flush()

        db.session.delete(u)
        db.session.commit()
        flash(f'Usuário "{u.nome}" removido!', 'warning')
    except Exception as e:
        db.session.rollback()
        flash('Não foi possível remover o usuário. Ele pode ter registros vinculados no sistema.', 'danger')

    return redirect(url_for('usuarios_bp.usuarios'))

# ── ACESSO EXTRA (substituto temporário) ─────────────────────────────────────

@usuarios_bp.route('/usuarios/<int:id>/acesso_extra', methods=['POST'])
@admin_ou_ggo_required
def conceder_acesso_extra(id):
    u = Usuario.query.get_or_404(id)
    admin = usuario_atual()
    alm_id = request.form.get('almoxarifado_id', type=int)
    motivo = request.form.get('motivo', '')
    data_fim_str = request.form.get('data_fim', '')
    data_fim = datetime.strptime(data_fim_str, '%Y-%m-%dT%H:%M') if data_fim_str else None

    acesso = AcessoExtra(
        usuario_id=id,
        almoxarifado_id=alm_id,
        motivo=motivo,
        data_fim=data_fim,
        concedido_por=admin.nome
    )
    db.session.add(acesso)
    db.session.commit()
    flash(f'Acesso temporário concedido a {u.nome}!', 'success')
    return redirect(url_for('usuarios_bp.editar_usuario', id=id))

@usuarios_bp.route('/acesso_extra/<int:id>/revogar', methods=['POST'])
@admin_ou_ggo_required
def revogar_acesso_extra(id):
    a = AcessoExtra.query.get_or_404(id)
    uid = a.usuario_id
    db.session.delete(a)
    db.session.commit()
    flash('Acesso revogado!', 'warning')
    return redirect(url_for('usuarios_bp.editar_usuario', id=uid))

@usuarios_bp.route('/usuarios/<int:id>/permissao', methods=['POST'])
@admin_ou_ggo_required
def conceder_permissao(id):
    u = Usuario.query.get_or_404(id)
    admin = usuario_atual()
    permissao = request.form.get('permissao', '').strip()
    if permissao not in PERMISSOES_DISPONIVEIS:
        flash('Permissão inválida.', 'danger')
        return redirect(url_for('usuarios_bp.editar_usuario', id=id))
    # Evita duplicata
    ja_existe = PermissaoExtra.query.filter_by(usuario_id=id, permissao=permissao).first()
    if ja_existe:
        flash(f'Usuário já tem a permissão "{PERMISSOES_DISPONIVEIS[permissao]}".', 'info')
        return redirect(url_for('usuarios_bp.editar_usuario', id=id))
    db.session.add(PermissaoExtra(
        usuario_id=id,
        permissao=permissao,
        concedido_por=admin.nome,
        data_concessao=agora()
    ))
    db.session.commit()
    flash(f'Permissão "{PERMISSOES_DISPONIVEIS[permissao]}" concedida a {u.nome}!', 'success')
    return redirect(url_for('usuarios_bp.editar_usuario', id=id))

@usuarios_bp.route('/permissao_extra/<int:pid>/revogar', methods=['POST'])
@admin_ou_ggo_required
def revogar_permissao(pid):
    p = PermissaoExtra.query.get_or_404(pid)
    uid = p.usuario_id
    nome_permissao = PERMISSOES_DISPONIVEIS.get(p.permissao, p.permissao)
    db.session.delete(p)
    db.session.commit()
    flash(f'Permissão "{nome_permissao}" revogada!', 'warning')
    return redirect(url_for('usuarios_bp.editar_usuario', id=uid))

# ── REQUISIÇÕES DO MESTRE DE OBRA ────────────────────────────────────────────
