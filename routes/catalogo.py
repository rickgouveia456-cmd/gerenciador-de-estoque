"""Catálogo Centralizado de Insumos."""
from flask import Blueprint, render_template, request, redirect, url_for, flash, jsonify
from extensions import db
from models import CatalogoInsumo, agora
from core import login_required, admin_required, almoxarife_required, usuario_atual

catalogo_bp = Blueprint('catalogo_bp', __name__)

CATEGORIAS = ['geral', 'epi', 'maquinario', 'eletrica', 'hidraulica', 'gas']
CAT_LABEL  = {
    'epi': 'EPI',
    'maquinario': 'Maquinário',
    'eletrica': 'Elétrica',
    'hidraulica': 'Hidráulica',
    'gas': 'Gás',
    'geral': 'Geral',
}


@catalogo_bp.route('/catalogo/insumos')
@login_required
def catalogo_insumos():
    q     = request.args.get('q', '').strip()
    cat   = request.args.get('categoria', '')
    query = CatalogoInsumo.query.filter_by(ativo=True)
    if q:
        query = query.filter(CatalogoInsumo.nome.ilike(f'%{q}%'))
    if cat:
        query = query.filter_by(categoria=cat)
    insumos = query.order_by(CatalogoInsumo.nome).all()
    return render_template('catalogo_insumos.html',
                           insumos=insumos, q=q, cat=cat,
                           categorias=CATEGORIAS, cat_label=CAT_LABEL)


@catalogo_bp.route('/catalogo/insumos/novo', methods=['GET', 'POST'])
@almoxarife_required
def catalogo_novo():
    u = usuario_atual()
    if request.method == 'POST':
        nome = request.form.get('nome', '').strip()
        if not nome:
            flash('Nome é obrigatório.', 'danger')
            return render_template('catalogo_form.html', insumo=None,
                                   categorias=CATEGORIAS, cat_label=CAT_LABEL,
                                   form_data=request.form)
        # Verificar duplicata por nome (case-insensitive)
        existente = CatalogoInsumo.query.filter(
            CatalogoInsumo.nome.ilike(nome),
            CatalogoInsumo.ativo == True
        ).first()
        if existente:
            flash(f'Já existe um insumo com o nome "{existente.nome}" no catálogo.', 'warning')
            return render_template('catalogo_form.html', insumo=None,
                                   categorias=CATEGORIAS, cat_label=CAT_LABEL,
                                   form_data=request.form)
        ins = CatalogoInsumo(
            nome=nome,
            codigo_ref=request.form.get('codigo_ref', '').strip() or None,
            unidade=request.form.get('unidade', 'un').strip(),
            categoria=request.form.get('categoria', 'geral'),
            ca=request.form.get('ca', '').strip() or None,
            descricao=request.form.get('descricao', '').strip() or None,
            criado_por=u.nome if u else None,
        )
        db.session.add(ins)
        db.session.commit()
        flash(f'✅ Insumo "{ins.nome}" adicionado ao catálogo!', 'success')
        return redirect(url_for('catalogo_insumos'))
    return render_template('catalogo_form.html', insumo=None,
                           categorias=CATEGORIAS, cat_label=CAT_LABEL, form_data={})


@catalogo_bp.route('/catalogo/insumos/<int:id>/editar', methods=['GET', 'POST'])
@almoxarife_required
def catalogo_editar(id):
    ins = CatalogoInsumo.query.get_or_404(id)
    if request.method == 'POST':
        ins.nome       = request.form.get('nome', ins.nome).strip()
        ins.codigo_ref = request.form.get('codigo_ref', '').strip() or None
        ins.unidade    = request.form.get('unidade', ins.unidade).strip()
        ins.categoria  = request.form.get('categoria', ins.categoria)
        ins.ca         = request.form.get('ca', '').strip() or None
        ins.descricao  = request.form.get('descricao', '').strip() or None
        db.session.commit()
        flash('Insumo atualizado!', 'success')
        return redirect(url_for('catalogo_insumos'))
    return render_template('catalogo_form.html', insumo=ins,
                           categorias=CATEGORIAS, cat_label=CAT_LABEL, form_data={})


@catalogo_bp.route('/catalogo/insumos/<int:id>/deletar', methods=['POST'])
@admin_required
def catalogo_deletar(id):
    ins = CatalogoInsumo.query.get_or_404(id)
    ins.ativo = False
    db.session.commit()
    flash(f'Insumo "{ins.nome}" removido do catálogo.', 'warning')
    return redirect(url_for('catalogo_insumos'))


@catalogo_bp.route('/api/catalogo/buscar')
@login_required
def api_catalogo_buscar():
    """Busca insumos no catálogo para autocomplete no form_item."""
    q   = request.args.get('q', '').strip()
    cat = request.args.get('categoria', '')
    query = CatalogoInsumo.query.filter_by(ativo=True)
    if q:
        query = query.filter(CatalogoInsumo.nome.ilike(f'%{q}%'))
    if cat:
        query = query.filter_by(categoria=cat)
    insumos = query.order_by(CatalogoInsumo.nome).limit(15).all()
    return jsonify([{
        'id': i.id,
        'nome': i.nome,
        'codigo_ref': i.codigo_ref or '',
        'unidade': i.unidade,
        'categoria': i.categoria,
        'ca': i.ca or '',
        'descricao': i.descricao or '',
    } for i in insumos])
