"""Catalogo Centralizado de Insumos."""
from flask import Blueprint, render_template, request, redirect, url_for, flash, jsonify, send_file
from extensions import db
from models import CatalogoInsumo, Item, Almoxarifado, agora
from core import login_required, admin_required, almoxarife_required, usuario_atual
import io, logging
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter

logger = logging.getLogger(__name__)

catalogo_bp = Blueprint('catalogo_bp', __name__)

CATEGORIAS = ['geral', 'epi', 'maquinario', 'eletrica', 'hidraulica', 'gas']
CAT_LABEL  = {
    'epi': 'EPI', 'maquinario': 'Maquinario', 'eletrica': 'Eletrica',
    'hidraulica': 'Hidraulica', 'gas': 'Gas', 'geral': 'Geral',
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
            flash('Nome e obrigatorio.', 'danger')
            return render_template('catalogo_form.html', insumo=None,
                                   categorias=CATEGORIAS, cat_label=CAT_LABEL,
                                   form_data=request.form)
        existente = CatalogoInsumo.query.filter(
            CatalogoInsumo.nome.ilike(nome), CatalogoInsumo.ativo == True
        ).first()
        if existente:
            flash(f'Ja existe um insumo com o nome "{existente.nome}" no catalogo.', 'warning')
            return render_template('catalogo_form.html', insumo=None,
                                   categorias=CATEGORIAS, cat_label=CAT_LABEL,
                                   form_data=request.form)
        try:
            valor = float(request.form.get('valor_unitario', '').replace(',', '.') or 0) or None
        except (ValueError, AttributeError):
            valor = None
        ins = CatalogoInsumo(
            nome=nome,
            codigo_ref=request.form.get('codigo_ref', '').strip() or None,
            unidade=request.form.get('unidade', 'un').strip(),
            categoria=request.form.get('categoria', 'geral'),
            ca=request.form.get('ca', '').strip() or None,
            descricao=request.form.get('descricao', '').strip() or None,
            valor_unitario=valor,
            criado_por=u.nome if u else None,
        )
        db.session.add(ins)
        db.session.commit()
        # Sincronizar valor com itens ja cadastrados com mesmo nome
        _sincronizar_valor_itens(ins)
        flash(f'Insumo "{ins.nome}" adicionado ao catalogo!', 'success')
        return redirect(url_for('catalogo_bp.catalogo_insumos'))
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
        try:
            ins.valor_unitario = float(request.form.get('valor_unitario', '').replace(',', '.') or 0) or None
        except (ValueError, AttributeError):
            ins.valor_unitario = None
        db.session.commit()
        # Sincronizar valor com itens cadastrados
        _sincronizar_valor_itens(ins)
        flash('Insumo atualizado!', 'success')
        return redirect(url_for('catalogo_bp.catalogo_insumos'))
    return render_template('catalogo_form.html', insumo=ins,
                           categorias=CATEGORIAS, cat_label=CAT_LABEL, form_data={})


@catalogo_bp.route('/catalogo/insumos/<int:id>/deletar', methods=['POST'])
@admin_required
def catalogo_deletar(id):
    ins = CatalogoInsumo.query.get_or_404(id)
    ins.ativo = False
    db.session.commit()
    flash(f'Insumo "{ins.nome}" removido do catalogo.', 'warning')
    return redirect(url_for('catalogo_bp.catalogo_insumos'))


@catalogo_bp.route('/catalogo/insumos/importar', methods=['GET', 'POST'])
@almoxarife_required
def catalogo_importar():
    """Importa insumos do Excel e sincroniza valores com o estoque."""
    u = usuario_atual()
    if request.method == 'POST':
        arquivo = request.files.get('arquivo')
        if not arquivo or not arquivo.filename.endswith(('.xlsx', '.xls')):
            flash('Envie um arquivo Excel (.xlsx ou .xls).', 'danger')
            return redirect(url_for('catalogo_bp.catalogo_importar'))
        try:
            wb = openpyxl.load_workbook(arquivo, data_only=True)
            ws = wb.active
            inseridos = atualizados = erros = 0
            for row_num, row in enumerate(ws.iter_rows(min_row=2, values_only=True), start=2):
                if not any(row):
                    continue
                try:
                    nome      = str(row[0]).strip() if row[0] else None
                    codigo_ref= str(row[1]).strip() if row[1] else None
                    unidade   = str(row[2]).strip() if row[2] else 'un'
                    try:
                        valor = float(str(row[3]).replace(',', '.')) if row[3] else None
                    except (ValueError, TypeError):
                        valor = None
                    categoria = str(row[4]).strip().lower() if len(row) > 4 and row[4] else 'geral'
                    if categoria not in CATEGORIAS:
                        categoria = 'geral'
                except Exception:
                    erros += 1
                    continue
                if not nome:
                    erros += 1
                    continue
                existente = CatalogoInsumo.query.filter(
                    CatalogoInsumo.nome.ilike(nome), CatalogoInsumo.ativo == True
                ).first()
                if existente:
                    # Atualizar valor se fornecido
                    if codigo_ref:
                        existente.codigo_ref = codigo_ref
                    if unidade:
                        existente.unidade = unidade
                    if valor is not None:
                        existente.valor_unitario = valor
                    existente.categoria = categoria
                    _sincronizar_valor_itens(existente)
                    atualizados += 1
                else:
                    novo = CatalogoInsumo(
                        nome=nome, codigo_ref=codigo_ref, unidade=unidade,
                        categoria=categoria, valor_unitario=valor,
                        criado_por=u.nome if u else None
                    )
                    db.session.add(novo)
                    db.session.flush()
                    _sincronizar_valor_itens(novo)
                    inseridos += 1
            db.session.commit()
            flash(f'Importacao concluida: {inseridos} inseridos, {atualizados} atualizados'
                  + (f', {erros} com erro.' if erros else '.'), 'success' if not erros else 'warning')
        except Exception as e:
            flash(f'Erro ao processar arquivo: {e}', 'danger')
        return redirect(url_for('catalogo_bp.catalogo_insumos'))
    return render_template('catalogo_importar.html')


@catalogo_bp.route('/catalogo/insumos/modelo-excel')
@login_required
def catalogo_modelo_excel():
    """Gera modelo Excel para importacao do catalogo."""
    wb = openpyxl.Workbook()
    ws = wb.active
    ws.title = 'Catalogo'
    h_fill = PatternFill('solid', fgColor='1A3A5C')
    h_font = Font(bold=True, color='FFFFFF', size=11)
    borda  = Border(left=Side(style='thin'), right=Side(style='thin'),
                    top=Side(style='thin'), bottom=Side(style='thin'))
    headers = ['Nome', 'Codigo Referencia', 'Unidade', 'Valor Unitario (R$)', 'Categoria']
    for col, h in enumerate(headers, 1):
        c = ws.cell(row=1, column=col, value=h)
        c.font = h_font; c.fill = h_fill
        c.alignment = Alignment(horizontal='center'); c.border = borda
    exemplos = [
        ('Capacete de Seguranca Classe A', 'CAP-001', 'un', 45.90, 'epi'),
        ('Cimento CP-II', 'CIM-001', 'sc', 38.50, 'geral'),
        ('Cabo Eletrico 2.5mm', 'CAB-025', 'm', 4.20, 'eletrica'),
    ]
    for r, ex in enumerate(exemplos, 2):
        for c, v in enumerate(ex, 1):
            ws.cell(row=r, column=c, value=v).border = borda
    for i, w in enumerate([40, 20, 10, 20, 15], 1):
        ws.column_dimensions[get_column_letter(i)].width = w
    buf = io.BytesIO()
    wb.save(buf); buf.seek(0)
    return send_file(buf, as_attachment=True, download_name='modelo_catalogo.xlsx',
                     mimetype='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')


@catalogo_bp.route('/catalogo/valor-estoque')
@login_required
def catalogo_valor_estoque():
    """Mostra valor total do estoque por almoxarifado (quantidade x valor_unitario)."""
    u = usuario_atual()
    if u.perfil == 'admin':
        alms = Almoxarifado.query.all()
    elif u.perfil == 'analista':
        alms = [db.session.get(Almoxarifado, u.almoxarifado_id)] if u.almoxarifado_id else []
    else:
        ids = u.almoxarifados_permitidos()
        alms = Almoxarifado.query.filter(Almoxarifado.id.in_(ids)).all() if ids else []

    resumo = []
    total_geral = 0.0
    for alm in alms:
        itens = Item.query.filter_by(almoxarifado_id=alm.id, ativo=True).all()
        valor_alm = 0.0
        itens_com_valor = []
        itens_sem_valor = 0
        for it in itens:
            if it.valor_unitario and it.valor_unitario > 0:
                vt = it.quantidade * it.valor_unitario
                valor_alm += vt
                itens_com_valor.append({
                    'item': it,
                    'valor_total': vt,
                    'valor_unitario': it.valor_unitario
                })
            else:
                itens_sem_valor += 1
        itens_com_valor.sort(key=lambda x: x['valor_total'], reverse=True)
        total_geral += valor_alm
        resumo.append({
            'almoxarifado': alm,
            'valor_total': valor_alm,
            'itens': itens_com_valor,
            'itens_sem_valor': itens_sem_valor,
            'total_itens': len(itens),
        })
    resumo.sort(key=lambda x: x['valor_total'], reverse=True)
    return render_template('catalogo_valor_estoque.html',
                           resumo=resumo, total_geral=total_geral)


@catalogo_bp.route('/api/catalogo/buscar')
@login_required
def api_catalogo_buscar():
    q   = request.args.get('q', '').strip()
    cat = request.args.get('categoria', '')
    query = CatalogoInsumo.query.filter_by(ativo=True)
    if q:
        query = query.filter(CatalogoInsumo.nome.ilike(f'%{q}%'))
    if cat:
        query = query.filter_by(categoria=cat)
    insumos = query.order_by(CatalogoInsumo.nome).limit(15).all()
    return jsonify([{
        'id': i.id, 'nome': i.nome, 'codigo_ref': i.codigo_ref or '',
        'unidade': i.unidade, 'categoria': i.categoria, 'ca': i.ca or '',
        'descricao': i.descricao or '',
        'valor_unitario': i.valor_unitario or 0,
    } for i in insumos])


def _sincronizar_valor_itens(ins):
    """Propaga valor_unitario do catalogo para todos os itens com mesmo nome."""
    if not ins.valor_unitario or ins.valor_unitario <= 0:
        return
    # Busca itens com nome exatamente igual (case-insensitive)
    itens = Item.query.filter(
        db.func.lower(Item.nome) == db.func.lower(ins.nome),
        Item.ativo == True
    ).all()
    for it in itens:
        it.valor_unitario = ins.valor_unitario
    if itens:
        try:
            db.session.commit()
            logger.info(f'Valor sincronizado: {len(itens)} itens com nome "{ins.nome}" -> R${ins.valor_unitario}')
        except Exception as e:
            logger.error(f'Erro ao sincronizar valor: {e}')
            db.session.rollback()
