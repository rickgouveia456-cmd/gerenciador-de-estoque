from flask import Flask, render_template, request, redirect, url_for, jsonify, flash, send_file
from flask_sqlalchemy import SQLAlchemy
from datetime import datetime, date
import io
import os
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter

app = Flask(__name__)
app.secret_key = 'estoque_secret_2024'
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///estoque.db'
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False

db = SQLAlchemy(app)

# ── MODELOS ──────────────────────────────────────────────────────────────────

class Almoxarifado(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    nome = db.Column(db.String(100), nullable=False)
    descricao = db.Column(db.String(200))
    itens = db.relationship('Item', backref='almoxarifado', lazy=True, cascade='all, delete-orphan')

class Item(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    nome = db.Column(db.String(100), nullable=False)
    codigo = db.Column(db.String(50), unique=True, nullable=False)
    unidade = db.Column(db.String(20), nullable=False)
    quantidade = db.Column(db.Float, default=0)
    estoque_minimo = db.Column(db.Float, default=0)
    almoxarifado_id = db.Column(db.Integer, db.ForeignKey('almoxarifado.id'), nullable=False)
    movimentacoes = db.relationship('Movimentacao', backref='item', lazy=True, cascade='all, delete-orphan')

    @property
    def status(self):
        if self.quantidade <= 0:
            return 'critico'
        elif self.quantidade <= self.estoque_minimo:
            return 'alerta'
        return 'ok'

class Movimentacao(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    tipo = db.Column(db.String(10), nullable=False)
    quantidade = db.Column(db.Float, nullable=False)
    responsavel = db.Column(db.String(100))
    observacao = db.Column(db.String(200))
    data = db.Column(db.DateTime, default=datetime.utcnow)
    item_id = db.Column(db.Integer, db.ForeignKey('item.id'), nullable=False)

# ── CONTEXT PROCESSOR ────────────────────────────────────────────────────────

@app.context_processor
def inject_sidebar():
    return dict(sidebar_alms=Almoxarifado.query.all())

# ── ROTAS PRINCIPAIS ─────────────────────────────────────────────────────────

@app.route('/')
def index():
    almoxarifados = Almoxarifado.query.all()
    alertas = Item.query.filter(Item.quantidade <= Item.estoque_minimo).all()
    stats = {
        'total_almoxarifados': len(almoxarifados),
        'total_itens': Item.query.count(),
        'itens_alerta': len([a for a in alertas if a.quantidade > 0]),
        'itens_criticos': len([a for a in alertas if a.quantidade <= 0]),
    }
    return render_template('index.html', almoxarifados=almoxarifados, alertas=alertas, stats=stats)

@app.route('/almoxarifado/<int:id>')
def almoxarifado(id):
    alm = Almoxarifado.query.get_or_404(id)
    itens = Item.query.filter_by(almoxarifado_id=id).all()
    return render_template('almoxarifado.html', almoxarifado=alm, itens=itens)

@app.route('/item/<int:id>')
def item(id):
    it = Item.query.get_or_404(id)
    movs = Movimentacao.query.filter_by(item_id=id).order_by(Movimentacao.data.desc()).limit(50).all()
    return render_template('item.html', item=it, movimentacoes=movs)

# ── CRUD ALMOXARIFADO ────────────────────────────────────────────────────────

@app.route('/almoxarifado/novo', methods=['GET', 'POST'])
def novo_almoxarifado():
    if request.method == 'POST':
        alm = Almoxarifado(nome=request.form['nome'], descricao=request.form.get('descricao', ''))
        db.session.add(alm)
        db.session.commit()
        flash(f'Almoxarifado "{alm.nome}" criado!', 'success')
        return redirect(url_for('index'))
    return render_template('form_almoxarifado.html', almoxarifado=None)

@app.route('/almoxarifado/<int:id>/editar', methods=['GET', 'POST'])
def editar_almoxarifado(id):
    alm = Almoxarifado.query.get_or_404(id)
    if request.method == 'POST':
        alm.nome = request.form['nome']
        alm.descricao = request.form.get('descricao', '')
        db.session.commit()
        flash('Almoxarifado atualizado!', 'success')
        return redirect(url_for('index'))
    return render_template('form_almoxarifado.html', almoxarifado=alm)

@app.route('/almoxarifado/<int:id>/deletar', methods=['POST'])
def deletar_almoxarifado(id):
    alm = Almoxarifado.query.get_or_404(id)
    db.session.delete(alm)
    db.session.commit()
    flash('Almoxarifado removido!', 'warning')
    return redirect(url_for('index'))

# ── CRUD ITEM ────────────────────────────────────────────────────────────────

@app.route('/item/novo', methods=['GET', 'POST'])
def novo_item():
    almoxarifados = Almoxarifado.query.all()
    if request.method == 'POST':
        it = Item(
            nome=request.form['nome'],
            codigo=request.form['codigo'],
            unidade=request.form['unidade'],
            quantidade=float(request.form.get('quantidade', 0)),
            estoque_minimo=float(request.form.get('estoque_minimo', 0)),
            almoxarifado_id=int(request.form['almoxarifado_id'])
        )
        db.session.add(it)
        db.session.commit()
        flash(f'Item "{it.nome}" cadastrado!', 'success')
        return redirect(url_for('almoxarifado', id=it.almoxarifado_id))
    return render_template('form_item.html', item=None, almoxarifados=almoxarifados)

@app.route('/item/<int:id>/editar', methods=['GET', 'POST'])
def editar_item(id):
    it = Item.query.get_or_404(id)
    almoxarifados = Almoxarifado.query.all()
    if request.method == 'POST':
        it.nome = request.form['nome']
        it.codigo = request.form['codigo']
        it.unidade = request.form['unidade']
        it.estoque_minimo = float(request.form.get('estoque_minimo', 0))
        it.almoxarifado_id = int(request.form['almoxarifado_id'])
        db.session.commit()
        flash('Item atualizado!', 'success')
        return redirect(url_for('almoxarifado', id=it.almoxarifado_id))
    return render_template('form_item.html', item=it, almoxarifados=almoxarifados)

@app.route('/item/<int:id>/deletar', methods=['POST'])
def deletar_item(id):
    it = Item.query.get_or_404(id)
    alm_id = it.almoxarifado_id
    db.session.delete(it)
    db.session.commit()
    flash('Item removido!', 'warning')
    return redirect(url_for('almoxarifado', id=alm_id))

# ── MOVIMENTAÇÕES ────────────────────────────────────────────────────────────

@app.route('/item/<int:id>/movimentar', methods=['POST'])
def movimentar(id):
    it = Item.query.get_or_404(id)
    tipo = request.form['tipo']
    qtd = float(request.form['quantidade'])
    if tipo == 'saida' and qtd > it.quantidade:
        flash('Quantidade insuficiente em estoque!', 'danger')
        return redirect(url_for('item', id=id))
    it.quantidade += qtd if tipo == 'entrada' else -qtd
    mov = Movimentacao(
        tipo=tipo, quantidade=qtd,
        responsavel=request.form.get('responsavel', ''),
        observacao=request.form.get('observacao', ''),
        item_id=id
    )
    db.session.add(mov)
    db.session.commit()
    flash(f'{"Entrada" if tipo == "entrada" else "Saida"} de {qtd} {it.unidade} registrada!', 'success')
    return redirect(url_for('item', id=id))

# ── RELATÓRIOS ───────────────────────────────────────────────────────────────

@app.route('/relatorios/consumo')
def relatorio_consumo():
    alm_id = request.args.get('almoxarifado_id', type=int)
    data_ini = request.args.get('data_ini', str(date.today().replace(day=1)))
    data_fim = request.args.get('data_fim', str(date.today()))
    query = Movimentacao.query.filter(
        Movimentacao.tipo == 'saida',
        Movimentacao.data >= data_ini,
        Movimentacao.data <= data_fim + ' 23:59:59'
    )
    if alm_id:
        query = query.join(Item).filter(Item.almoxarifado_id == alm_id)
    movs = query.order_by(Movimentacao.data.desc()).all()
    almoxarifados = Almoxarifado.query.all()
    return render_template('relatorio_consumo.html', movimentacoes=movs,
                           almoxarifados=almoxarifados, data_ini=data_ini,
                           data_fim=data_fim, alm_id=alm_id)

@app.route('/relatorios/alertas')
def relatorio_alertas():
    itens = Item.query.filter(Item.quantidade <= Item.estoque_minimo).all()
    return render_template('relatorio_alertas.html', itens=itens)

# ── EXPORTAR EXCEL ───────────────────────────────────────────────────────────

@app.route('/almoxarifado/<int:id>/exportar')
def exportar_almoxarifado(id):
    alm = Almoxarifado.query.get_or_404(id)
    itens = Item.query.filter_by(almoxarifado_id=id).all()

    wb = openpyxl.Workbook()
    h_fill = PatternFill('solid', fgColor='1A3A5C')
    h_font = Font(bold=True, color='FFFFFF', size=11)
    ok_fill  = PatternFill('solid', fgColor='D4EDDA')
    al_fill  = PatternFill('solid', fgColor='FFF3CD')
    cr_fill  = PatternFill('solid', fgColor='F8D7DA')
    en_fill  = PatternFill('solid', fgColor='D4EDDA')
    sa_fill  = PatternFill('solid', fgColor='F8D7DA')
    borda = Border(left=Side(style='thin'), right=Side(style='thin'),
                   top=Side(style='thin'), bottom=Side(style='thin'))

    def titulo(ws, texto, cols):
        ws.merge_cells(f'A1:{get_column_letter(cols)}1')
        ws['A1'] = texto
        ws['A1'].font = Font(bold=True, size=13, color='1A3A5C')
        ws['A1'].alignment = Alignment(horizontal='center')
        ws.merge_cells(f'A2:{get_column_letter(cols)}2')
        ws['A2'] = f'Gerado em: {datetime.now().strftime("%d/%m/%Y %H:%M")}'
        ws['A2'].font = Font(italic=True, color='888888')
        ws['A2'].alignment = Alignment(horizontal='center')

    def cabecalho(ws, row, headers):
        for col, h in enumerate(headers, 1):
            c = ws.cell(row=row, column=col, value=h)
            c.font = h_font; c.fill = h_fill
            c.alignment = Alignment(horizontal='center'); c.border = borda

    # Aba 1 — Estoque Atual
    ws1 = wb.active
    ws1.title = 'Estoque Atual'
    titulo(ws1, f'Estoque Atual — {alm.nome}', 7)
    cabecalho(ws1, 4, ['Codigo', 'Item', 'Unidade', 'Qtd. Atual', 'Est. Minimo', 'Deficit', 'Status'])
    for r, it in enumerate(itens, 5):
        deficit = max(0, it.estoque_minimo - it.quantidade)
        status = 'ZERADO' if it.status == 'critico' else ('ABAIXO DO MINIMO' if it.status == 'alerta' else 'OK')
        fill = cr_fill if it.status == 'critico' else (al_fill if it.status == 'alerta' else ok_fill)
        for c, v in enumerate([it.codigo, it.nome, it.unidade, it.quantidade, it.estoque_minimo, deficit, status], 1):
            cell = ws1.cell(row=r, column=c, value=v)
            cell.fill = fill; cell.border = borda
            cell.alignment = Alignment(horizontal='left' if c == 2 else 'center')
    for i, w in enumerate([14, 35, 10, 12, 14, 12, 20], 1):
        ws1.column_dimensions[get_column_letter(i)].width = w

    # Aba 2 — Movimentações
    ws2 = wb.create_sheet('Movimentacoes')
    titulo(ws2, f'Historico de Movimentacoes — {alm.nome}', 6)
    cabecalho(ws2, 4, ['Data', 'Item', 'Tipo', 'Quantidade', 'Responsavel', 'Observacao'])
    movs = (Movimentacao.query.join(Item)
            .filter(Item.almoxarifado_id == id)
            .order_by(Movimentacao.data.desc()).all())
    for r, mov in enumerate(movs, 5):
        fill = en_fill if mov.tipo == 'entrada' else sa_fill
        for c, v in enumerate([
            mov.data.strftime('%d/%m/%Y %H:%M'), mov.item.nome,
            'Entrada' if mov.tipo == 'entrada' else 'Saida',
            f'{mov.quantidade} {mov.item.unidade}',
            mov.responsavel or '', mov.observacao or ''
        ], 1):
            cell = ws2.cell(row=r, column=c, value=v)
            cell.fill = fill; cell.border = borda
            cell.alignment = Alignment(horizontal='left')
    for i, w in enumerate([18, 35, 10, 14, 22, 30], 1):
        ws2.column_dimensions[get_column_letter(i)].width = w

    # Aba 3 — Consumo por Item
    ws3 = wb.create_sheet('Consumo por Item')
    titulo(ws3, f'Consumo por Item — {alm.nome}', 4)
    cabecalho(ws3, 4, ['Item', 'Total Entradas', 'Total Saidas', 'Saldo Atual'])
    for r, it in enumerate(itens, 5):
        entradas = sum(m.quantidade for m in it.movimentacoes if m.tipo == 'entrada')
        saidas   = sum(m.quantidade for m in it.movimentacoes if m.tipo == 'saida')
        for c, v in enumerate([it.nome, entradas, saidas, it.quantidade], 1):
            cell = ws3.cell(row=r, column=c, value=v)
            cell.border = borda
            cell.alignment = Alignment(horizontal='left' if c == 1 else 'center')
    for i, w in enumerate([35, 16, 14, 14], 1):
        ws3.column_dimensions[get_column_letter(i)].width = w

    buf = io.BytesIO()
    wb.save(buf)
    buf.seek(0)
    nome = f"estoque_{alm.nome.replace(' ', '_')}_{date.today()}.xlsx"
    return send_file(buf, as_attachment=True, download_name=nome,
                     mimetype='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')

# ── API ──────────────────────────────────────────────────────────────────────

@app.route('/api/alertas')
def api_alertas():
    itens = Item.query.filter(Item.quantidade <= Item.estoque_minimo).all()
    return jsonify([{
        'id': i.id, 'nome': i.nome, 'codigo': i.codigo,
        'quantidade': i.quantidade, 'estoque_minimo': i.estoque_minimo,
        'unidade': i.unidade, 'status': i.status,
        'almoxarifado': i.almoxarifado.nome
    } for i in itens])

# ── SEED + INICIALIZAÇÃO ─────────────────────────────────────────────────────

def seed_data():
    if Almoxarifado.query.count() == 0:
        db.session.add_all([
            Almoxarifado(nome='Almoxarifado do Acampamento', descricao='Materiais de uso geral do acampamento'),
            Almoxarifado(nome='Almoxarifado de Infraestrutura', descricao='Materiais de construcao e manutencao'),
            Almoxarifado(nome='Almoxarifado de Forma', descricao='Formas, escoramentos e materiais de forma'),
        ])
        db.session.commit()

if __name__ == '__main__':
    with app.app_context():
        db.create_all()
        seed_data()
    port = int(os.environ.get('PORT', 5000))
    app.run(debug=False, host='0.0.0.0', port=port)
