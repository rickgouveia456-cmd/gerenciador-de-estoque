import json
from flask import Blueprint, render_template, request, redirect, url_for, flash
from extensions import db
from models import agora, Almoxarifado, Item, Kit, KitItem
from core import login_required, usuario_atual, usuario_tem_acesso_almoxarifado

kits_bp = Blueprint('kits_bp', __name__)

# Perfis que NÃO têm acesso ao módulo de kits
_PERFIS_BLOQUEADOS = ('mestre', 'tecnico_seguranca')


def _checar_acesso(u, alm_id):
    """Retorna mensagem de erro ou None se acesso permitido."""
    if u.perfil in _PERFIS_BLOQUEADOS:
        return 'Acesso negado.'
    if not usuario_tem_acesso_almoxarifado(u, alm_id):
        return 'Acesso negado a este almoxarifado.'
    return None


def _itens_json(alm_id):
    """Retorna JSON com itens ativos do almoxarifado para o formulário."""
    itens = Item.query.filter_by(almoxarifado_id=alm_id, ativo=True).order_by(Item.nome).all()
    return json.dumps([
        {
            'id': it.id,
            'nome': it.nome,
            'codigo': it.codigo,
            'unidade': it.unidade,
            'quantidade': it.quantidade,
        }
        for it in itens
    ])


# ── LISTAGEM ─────────────────────────────────────────────────────────────────

@kits_bp.route('/almoxarifado/<int:alm_id>/kits')
@login_required
def kits(alm_id):
    u = usuario_atual()
    erro = _checar_acesso(u, alm_id)
    if erro:
        flash(erro, 'danger')
        return redirect(url_for('main_bp.index'))

    alm = Almoxarifado.query.get_or_404(alm_id)
    lista = (Kit.query
             .filter_by(almoxarifado_id=alm_id, ativo=True)
             .order_by(Kit.nome)
             .all())
    return render_template('kits.html', almoxarifado=alm, kits=lista)


# ── CRIAR ─────────────────────────────────────────────────────────────────────

@kits_bp.route('/almoxarifado/<int:alm_id>/kits/novo', methods=['GET', 'POST'])
@login_required
def kit_novo(alm_id):
    u = usuario_atual()
    erro = _checar_acesso(u, alm_id)
    if erro:
        flash(erro, 'danger')
        return redirect(url_for('main_bp.index'))

    # Analista só visualiza, não cria
    if u.perfil == 'analista':
        flash('Analistas não podem criar kits.', 'danger')
        return redirect(url_for('kits_bp.kits', alm_id=alm_id))

    alm = Almoxarifado.query.get_or_404(alm_id)

    if request.method == 'POST':
        nome = request.form.get('nome', '').strip()
        descricao = request.form.get('descricao', '').strip()

        if not nome:
            flash('O nome do kit é obrigatório.', 'danger')
            return render_template('kit_form.html', almoxarifado=alm,
                                   kit=None, itens_json=_itens_json(alm_id))

        kit = Kit(
            nome=nome,
            descricao=descricao or None,
            almoxarifado_id=alm_id,
            criado_por=u.nome,
            data_criacao=agora(),
        )
        db.session.add(kit)
        db.session.flush()  # obtém kit.id antes do commit

        _salvar_itens_kit(kit, request.form)

        db.session.commit()
        flash(f'Kit "{kit.nome}" criado com sucesso!', 'success')
        return redirect(url_for('kits_bp.kits', alm_id=alm_id))

    return render_template('kit_form.html', almoxarifado=alm,
                           kit=None, itens_json=_itens_json(alm_id))


# ── EDITAR ────────────────────────────────────────────────────────────────────

@kits_bp.route('/almoxarifado/<int:alm_id>/kits/<int:kit_id>/editar', methods=['GET', 'POST'])
@login_required
def kit_editar(alm_id, kit_id):
    u = usuario_atual()
    erro = _checar_acesso(u, alm_id)
    if erro:
        flash(erro, 'danger')
        return redirect(url_for('main_bp.index'))

    if u.perfil == 'analista':
        flash('Analistas não podem editar kits.', 'danger')
        return redirect(url_for('kits_bp.kits', alm_id=alm_id))

    alm = Almoxarifado.query.get_or_404(alm_id)
    kit = Kit.query.filter_by(id=kit_id, almoxarifado_id=alm_id, ativo=True).first_or_404()

    if request.method == 'POST':
        nome = request.form.get('nome', '').strip()
        descricao = request.form.get('descricao', '').strip()

        if not nome:
            flash('O nome do kit é obrigatório.', 'danger')
            return render_template('kit_form.html', almoxarifado=alm,
                                   kit=kit, itens_json=_itens_json(alm_id))

        kit.nome = nome
        kit.descricao = descricao or None

        # Recriar os itens do kit
        KitItem.query.filter_by(kit_id=kit.id).delete()
        _salvar_itens_kit(kit, request.form)

        db.session.commit()
        flash(f'Kit "{kit.nome}" atualizado!', 'success')
        return redirect(url_for('kits_bp.kits', alm_id=alm_id))

    return render_template('kit_form.html', almoxarifado=alm,
                           kit=kit, itens_json=_itens_json(alm_id))


# ── EXCLUIR ───────────────────────────────────────────────────────────────────

@kits_bp.route('/almoxarifado/<int:alm_id>/kits/<int:kit_id>/excluir', methods=['POST'])
@login_required
def kit_excluir(alm_id, kit_id):
    u = usuario_atual()
    erro = _checar_acesso(u, alm_id)
    if erro:
        flash(erro, 'danger')
        return redirect(url_for('main_bp.index'))

    if u.perfil == 'analista':
        flash('Analistas não podem excluir kits.', 'danger')
        return redirect(url_for('kits_bp.kits', alm_id=alm_id))

    kit = Kit.query.filter_by(id=kit_id, almoxarifado_id=alm_id).first_or_404()
    kit.ativo = False
    db.session.commit()
    flash(f'Kit "{kit.nome}" removido.', 'warning')
    return redirect(url_for('kits_bp.kits', alm_id=alm_id))


# ── HELPER ────────────────────────────────────────────────────────────────────

def _salvar_itens_kit(kit, form):
    """Lê item_id_0, quantidade_0, item_id_1, ... do form e salva KitItems."""
    idx = 0
    while True:
        item_id_str = form.get(f'item_id_{idx}')
        qtd_str = form.get(f'quantidade_{idx}')
        if item_id_str is None:
            break
        try:
            item_id = int(item_id_str)
            qtd = float(qtd_str)
            if item_id > 0 and qtd > 0:
                db.session.add(KitItem(
                    kit_id=kit.id,
                    item_id=item_id,
                    quantidade=qtd,
                ))
        except (ValueError, TypeError):
            pass
        idx += 1
