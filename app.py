from flask import Flask, render_template, request, redirect, url_for, jsonify, flash, send_file, session
from flask_sqlalchemy import SQLAlchemy
from werkzeug.security import generate_password_hash, check_password_hash
from datetime import datetime, date
from functools import wraps
import io
import os
import json
import secrets
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter

app = Flask(__name__)
app.secret_key = os.environ.get('SECRET_KEY') or secrets.token_hex(32)
app.config['SQLALCHEMY_DATABASE_URI'] = (
    os.environ.get('DATABASE_URL') or
    os.environ.get('URI_DO_BANCO_DE_DADOS') or
    'sqlite:////app/instance/estoque.db'
)
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
    status_compra = db.Column(db.String(30), default='pendente')
    fixado = db.Column(db.Boolean, default=False)
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

class Requisicao(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    colaborador = db.Column(db.String(100), nullable=False)
    observacao = db.Column(db.String(200))
    quantidade = db.Column(db.Float, nullable=False)
    status = db.Column(db.String(20), default='aberta')  # aberta | devolvida
    data_retirada = db.Column(db.DateTime, default=datetime.utcnow)
    data_devolucao = db.Column(db.DateTime, nullable=True)
    item_id = db.Column(db.Integer, db.ForeignKey('item.id'), nullable=False)
    item = db.relationship('Item', backref='requisicoes')

class Usuario(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    nome = db.Column(db.String(100), nullable=False)
    login = db.Column(db.String(50), unique=True, nullable=False)
    senha_hash = db.Column(db.String(256), nullable=False)
    perfil = db.Column(db.String(20), default='colaborador')  # admin | colaborador
    almoxarifado_id = db.Column(db.Integer, db.ForeignKey('almoxarifado.id'), nullable=True)
    ativo = db.Column(db.Boolean, default=True)
    almoxarifado = db.relationship('Almoxarifado', backref='usuarios')
    acessos_extras = db.relationship('AcessoExtra', backref='usuario', lazy=True, cascade='all, delete-orphan')

    def set_senha(self, senha):
        self.senha_hash = generate_password_hash(senha)

    def check_senha(self, senha):
        # Compatibilidade: hashes antigos eram SHA-256 puro (64 chars hex)
        if len(self.senha_hash) == 64 and not self.senha_hash.startswith('pbkdf2:'):
            import hashlib
            if self.senha_hash == hashlib.sha256(senha.encode()).hexdigest():
                # Migra automaticamente para o novo hash seguro
                self.set_senha(senha)
                from flask_sqlalchemy import SQLAlchemy
                db.session.commit()
                return True
            return False
        return check_password_hash(self.senha_hash, senha)

    def almoxarifados_permitidos(self):
        ids = set()
        if self.almoxarifado_id:
            ids.add(self.almoxarifado_id)
        for a in self.acessos_extras:
            if a.ativo:
                ids.add(a.almoxarifado_id)
        return ids

class AcessoExtra(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    usuario_id = db.Column(db.Integer, db.ForeignKey('usuario.id'), nullable=False)
    almoxarifado_id = db.Column(db.Integer, db.ForeignKey('almoxarifado.id'), nullable=False)
    motivo = db.Column(db.String(200))
    data_inicio = db.Column(db.DateTime, default=datetime.utcnow)
    data_fim = db.Column(db.DateTime, nullable=True)
    concedido_por = db.Column(db.String(100))
    almoxarifado = db.relationship('Almoxarifado')

    @property
    def ativo(self):
        if self.data_fim and datetime.utcnow() > self.data_fim:
            return False
        return True

# ── DECORATORS DE ACESSO ─────────────────────────────────────────────────────

def login_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if 'usuario_id' not in session:
            flash('Faça login para continuar.', 'warning')
            return redirect(url_for('login'))
        u = Usuario.query.get(session['usuario_id'])
        if not u or not u.ativo:
            session.clear()
            flash('Sessão expirada. Faça login novamente.', 'warning')
            return redirect(url_for('login'))
        return f(*args, **kwargs)
    return decorated

def admin_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if 'usuario_id' not in session:
            return redirect(url_for('login'))
        u = Usuario.query.get(session['usuario_id'])
        if not u or u.perfil != 'admin':
            flash('Acesso restrito ao administrador.', 'danger')
            return redirect(url_for('index'))
        return f(*args, **kwargs)
    return decorated

def usuario_atual():
    if 'usuario_id' in session:
        return Usuario.query.get(session['usuario_id'])
    return None

# ── CONTEXT PROCESSOR ────────────────────────────────────────────────────────

@app.context_processor
def inject_sidebar():
    u = usuario_atual()
    if not u:
        return dict(sidebar_alms=[], usuario_atual=None)
    if u.perfil == 'admin':
        alms = Almoxarifado.query.all()
    else:
        ids = u.almoxarifados_permitidos()
        alms = Almoxarifado.query.filter(Almoxarifado.id.in_(ids)).all() if ids else []
    return dict(sidebar_alms=alms, usuario_atual=u)

def run_migrations():
    try:
        from sqlalchemy import text
        with db.engine.connect() as conn:
            try:
                conn.execute(text("ALTER TABLE item ADD COLUMN status_compra VARCHAR(30) DEFAULT 'pendente'"))
                conn.commit()
            except: pass
            try:
                conn.execute(text("ALTER TABLE item ADD COLUMN fixado BOOLEAN DEFAULT 0"))
                conn.commit()
            except: pass
            try:
                conn.execute(text("ALTER TABLE usuario ADD COLUMN senha_hash_new VARCHAR(256)"))
                conn.commit()
            except: pass
            conn.execute(text("""
                CREATE TABLE IF NOT EXISTS acesso_extra (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    usuario_id INTEGER NOT NULL REFERENCES usuario(id),
                    almoxarifado_id INTEGER NOT NULL REFERENCES almoxarifado(id),
                    motivo VARCHAR(200),
                    data_inicio DATETIME,
                    data_fim DATETIME,
                    concedido_por VARCHAR(100)
                )
            """))
            conn.commit()
    except Exception as e:
        print(f'Migração: {e}')

# ── LOGIN / LOGOUT ────────────────────────────────────────────────────────────

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        u = Usuario.query.filter_by(login=request.form['login'], ativo=True).first()
        if u and u.check_senha(request.form['senha']):
            session['usuario_id'] = u.id
            flash(f'Bem-vindo, {u.nome}!', 'success')
            return redirect(url_for('index'))
        flash('Login ou senha incorretos.', 'danger')
    return render_template('login.html')

@app.route('/logout')
def logout():
    session.clear()
    return redirect(url_for('login'))

# ── ROTAS PRINCIPAIS ─────────────────────────────────────────────────────────

@app.route('/')
@login_required
def index():
    u = usuario_atual()
    if u.perfil == 'admin':
        almoxarifados = Almoxarifado.query.all()
        alertas = Item.query.filter(Item.quantidade <= Item.estoque_minimo).all()
    else:
        ids = u.almoxarifados_permitidos()
        almoxarifados = Almoxarifado.query.filter(Almoxarifado.id.in_(ids)).all() if ids else []
        alertas = Item.query.filter(
            Item.quantidade <= Item.estoque_minimo,
            Item.almoxarifado_id.in_(ids)
        ).all() if ids else []
    stats = {
        'total_almoxarifados': len(almoxarifados),
        'total_itens': sum(len(a.itens) for a in almoxarifados),
        'itens_alerta': len([a for a in alertas if a.quantidade > 0]),
        'itens_criticos': len([a for a in alertas if a.quantidade <= 0]),
    }
    return render_template('index.html', almoxarifados=almoxarifados, alertas=alertas, stats=stats)

@app.route('/almoxarifado/<int:id>')
@login_required
def almoxarifado(id):
    u = usuario_atual()
    alm = Almoxarifado.query.get_or_404(id)
    if u.perfil != 'admin' and id not in u.almoxarifados_permitidos():
        flash('Acesso negado.', 'danger')
        return redirect(url_for('index'))
    itens = Item.query.filter_by(almoxarifado_id=id).all()
    return render_template('almoxarifado.html', almoxarifado=alm, itens=itens)

@app.route('/item/<int:id>')
@login_required
def item(id):
    it = Item.query.get_or_404(id)
    u = usuario_atual()
    if u.perfil != 'admin' and it.almoxarifado_id not in u.almoxarifados_permitidos():
        flash('Acesso negado.', 'danger')
        return redirect(url_for('index'))
    movs = Movimentacao.query.filter_by(item_id=id).order_by(Movimentacao.data.desc()).limit(50).all()
    return render_template('item.html', item=it, movimentacoes=movs)

# ── CRUD ALMOXARIFADO ────────────────────────────────────────────────────────

@app.route('/almoxarifado/novo', methods=['GET', 'POST'])
@admin_required
def novo_almoxarifado():
    if request.method == 'POST':
        alm = Almoxarifado(nome=request.form['nome'], descricao=request.form.get('descricao', ''))
        db.session.add(alm)
        db.session.commit()
        flash(f'Almoxarifado "{alm.nome}" criado!', 'success')
        return redirect(url_for('index'))
    return render_template('form_almoxarifado.html', almoxarifado=None)

@app.route('/almoxarifado/<int:id>/editar', methods=['GET', 'POST'])
@login_required
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
@admin_required
def deletar_almoxarifado(id):
    alm = Almoxarifado.query.get_or_404(id)
    db.session.delete(alm)
    db.session.commit()
    flash('Almoxarifado removido!', 'warning')
    return redirect(url_for('index'))

# ── CRUD ITEM ────────────────────────────────────────────────────────────────

@app.route('/item/novo', methods=['GET', 'POST'])
@login_required
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
@login_required
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
@login_required
def deletar_item(id):
    it = Item.query.get_or_404(id)
    alm_id = it.almoxarifado_id
    db.session.delete(it)
    db.session.commit()
    flash('Item removido!', 'warning')
    return redirect(url_for('almoxarifado', id=alm_id))

# ── MOVIMENTAÇÃO EM LOTE ─────────────────────────────────────────────────────

@app.route('/movimentacao/lote', methods=['GET', 'POST'])
@login_required
def movimentacao_lote():
    u = usuario_atual()
    almoxarifados = Almoxarifado.query.all() if u.perfil == 'admin' else \
        Almoxarifado.query.filter(Almoxarifado.id.in_(u.almoxarifados_permitidos())).all()

    # Histórico das últimas 20 movimentações
    if u.perfil == 'admin':
        historico = Movimentacao.query.order_by(Movimentacao.data.desc()).limit(20).all()
        requisicoes_hist = Requisicao.query.order_by(Requisicao.data_retirada.desc()).limit(20).all()
    else:
        ids = u.almoxarifados_permitidos()
        historico = (Movimentacao.query.join(Item)
                     .filter(Item.almoxarifado_id.in_(ids))
                     .order_by(Movimentacao.data.desc()).limit(20).all())
        requisicoes_hist = (Requisicao.query.join(Item)
                            .filter(Item.almoxarifado_id.in_(ids))
                            .order_by(Requisicao.data_retirada.desc()).limit(20).all())

    # Montar JSON com itens por almoxarifado
    itens_json = {}
    for alm in almoxarifados:
        itens_json[str(alm.id)] = [
            {'id': it.id, 'nome': it.nome, 'quantidade': it.quantidade, 'unidade': it.unidade}
            for it in alm.itens
        ]

    if request.method == 'POST':
        alm_id      = int(request.form['almoxarifado_id'])
        tipo        = request.form['tipo']
        responsavel = request.form.get('responsavel', '')
        observacao  = request.form.get('observacao', '')

        erros = []
        movs  = []

        # Coletar índices sem depender de total_linhas
        indices = set()
        for key in request.form.keys():
            if key.startswith('item_id_'):
                try:
                    indices.add(int(key.split('_')[-1]))
                except ValueError:
                    pass

        for i in sorted(indices):
            item_id = request.form.get(f'item_id_{i}')
            qtd_str = request.form.get(f'quantidade_{i}')
            colab   = request.form.get(f'colaborador_{i}', '')

            if not item_id or not qtd_str:
                continue

            it = Item.query.get(item_id)
            try:
                qtd = float(qtd_str)
            except ValueError:
                continue

            if not it or qtd <= 0:
                continue

            if tipo == 'saida' and qtd > it.quantidade:
                erros.append(f'"{it.nome}": estoque insuficiente ({it.quantidade} {it.unidade})')
                continue

            it.quantidade += qtd if tipo == 'entrada' else -qtd
            obs_linha = f'{observacao} | Colaborador: {colab}' if colab else observacao
            movs.append(Movimentacao(
                tipo=tipo, quantidade=qtd,
                responsavel=responsavel,
                observacao=obs_linha,
                item_id=it.id
            ))

        if movs:
            db.session.add_all(movs)
            db.session.commit()
            tipo_label = '📥 Entrada' if request.form['tipo'] == 'entrada' else '📤 Saída'
            alm = Almoxarifado.query.get(alm_id)
            flash(
                f'<strong>{tipo_label} registrada!</strong> '
                f'{len(movs)} item(ns) movimentado(s) em <strong>{alm.nome if alm else ""}</strong>. '
                f'<a href="/almoxarifado/{alm_id}" class="alert-link">Ver Almoxarifado</a>',
                'success'
            )
        elif not erros:
            flash('Adicione pelo menos um item antes de confirmar.', 'warning')

        for e in erros:
            flash(
                f'<strong>Estoque insuficiente:</strong> {e} '
                f'<a href="/almoxarifado/{alm_id}" class="alert-link">Consultar Estoque</a>',
                'danger'
            )

        return redirect(url_for('movimentacao_lote'))

    import json
    return render_template('movimentacao_lote.html',
                           almoxarifados=almoxarifados,
                           itens_json=json.dumps(itens_json),
                           historico=historico,
                           requisicoes=requisicoes_hist)

@app.route('/item/<int:id>/movimentar', methods=['POST'])
@login_required
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

# ── REQUISIÇÕES ──────────────────────────────────────────────────────────────

@app.route('/requisicoes')
@login_required
def requisicoes():
    colaborador  = request.args.get('colaborador', '')
    status       = request.args.get('status', '')
    data_ini     = request.args.get('data_ini', '')
    data_fim     = request.args.get('data_fim', '')

    q = Requisicao.query
    if colaborador:
        q = q.filter(Requisicao.colaborador.ilike(f'%{colaborador}%'))
    if status:
        q = q.filter(Requisicao.status == status)
    if data_ini:
        q = q.filter(Requisicao.data_retirada >= data_ini)
    if data_fim:
        q = q.filter(Requisicao.data_retirada <= data_fim + ' 23:59:59')

    reqs = q.order_by(Requisicao.data_retirada.desc()).all()

    return render_template('requisicoes.html',
        requisicoes=reqs,
        total=len(reqs),
        em_uso=sum(1 for r in reqs if r.status == 'aberta'),
        devolvidos=sum(1 for r in reqs if r.status == 'devolvida'),
        filtro_colaborador=colaborador,
        filtro_status=status,
        filtro_data_ini=data_ini,
        filtro_data_fim=data_fim
    )

@app.route('/requisicoes/nova', methods=['GET', 'POST'])
@login_required
def requisicao_nova():
    u = usuario_atual()
    almoxarifados = Almoxarifado.query.all() if u.perfil == 'admin' else \
        Almoxarifado.query.filter(Almoxarifado.id.in_(u.almoxarifados_permitidos())).all()
    itens_json = {}
    for alm in almoxarifados:
        itens_json[str(alm.id)] = [
            {'id': it.id, 'nome': it.nome, 'quantidade': it.quantidade, 'unidade': it.unidade}
            for it in alm.itens
        ]

    if request.method == 'POST':
        colaborador = request.form.get('colaborador', '')
        observacao  = request.form.get('observacao', '')
        criados = 0

        # Coletar todos os item_id_N do formulário sem depender de total_linhas
        indices = set()
        for key in request.form.keys():
            if key.startswith('item_id_'):
                try:
                    indices.add(int(key.split('_')[-1]))
                except ValueError:
                    pass

        for i in sorted(indices):
            item_id = request.form.get(f'item_id_{i}')
            qtd_str = request.form.get(f'quantidade_{i}')
            if not item_id or not qtd_str:
                continue
            it  = Item.query.get(item_id)
            try:
                qtd = float(qtd_str)
            except ValueError:
                continue
            if not it or qtd <= 0:
                continue
            if qtd > it.quantidade:
                flash(
                    f'<strong>Estoque insuficiente:</strong> "{it.nome}" tem apenas '
                    f'<strong>{it.quantidade} {it.unidade}</strong> disponível. '
                    f'<a href="/almoxarifado/{it.almoxarifado_id}" class="alert-link">Consultar Estoque</a>',
                    'danger'
                )
                continue

            it.quantidade -= qtd
            db.session.add(Requisicao(
                colaborador=colaborador,
                observacao=observacao,
                quantidade=qtd,
                item_id=it.id
            ))
            db.session.add(Movimentacao(
                tipo='saida', quantidade=qtd,
                responsavel=colaborador,
                observacao=f'Requisicao — {observacao}',
                item_id=it.id
            ))
            criados += 1

        if criados:
            db.session.commit()
            flash(
                f'<strong>✅ Requisição registrada!</strong> '
                f'{criados} item(ns) retirado(s) com sucesso para <strong>{colaborador}</strong>. '
                f'<a href="/requisicoes" class="alert-link">Ver Requisições</a>',
                'success'
            )
        elif not any(True for key in request.form.keys() if key.startswith('item_id_')):
            flash('Adicione pelo menos um item antes de registrar.', 'warning')
        return redirect(url_for('requisicoes'))

    import json
    return render_template('requisicao_nova.html',
                           almoxarifados=almoxarifados,
                           itens_json=json.dumps(itens_json))

@app.route('/requisicoes/<int:id>/devolver', methods=['POST'])
@login_required
def devolver_requisicao(id):
    req = Requisicao.query.get_or_404(id)
    if req.status == 'aberta':
        req.status = 'devolvida'
        req.data_devolucao = datetime.utcnow()
        req.item.quantidade += req.quantidade
        db.session.add(Movimentacao(
            tipo='entrada', quantidade=req.quantidade,
            responsavel=req.colaborador,
            observacao=f'Devolução de requisição #{req.id}',
            item_id=req.item_id
        ))
        db.session.commit()
        flash(f'Devolução de "{req.item.nome}" registrada!', 'success')
    return redirect(url_for('requisicoes'))

@app.route('/almoxarifado/<int:id>/importar', methods=['GET', 'POST'])
@login_required
def importar_itens(id):
    u = usuario_atual()
    alm = Almoxarifado.query.get_or_404(id)
    if u.perfil != 'admin' and id not in u.almoxarifados_permitidos():
        flash('Acesso negado.', 'danger')
        return redirect(url_for('index'))

    if request.method == 'POST':
        arquivo = request.files.get('arquivo')
        modo = request.form.get('modo', 'adicionar')  # adicionar | atualizar | substituir

        if not arquivo or not arquivo.filename.endswith(('.xlsx', '.xls')):
            flash('Envie um arquivo Excel (.xlsx ou .xls).', 'danger')
            return redirect(url_for('importar_itens', id=id))

        try:
            wb = openpyxl.load_workbook(arquivo, data_only=True)
            ws = wb.active

            inseridos = 0
            atualizados = 0
            erros = []

            # Pular cabeçalho (linha 1)
            for row_num, row in enumerate(ws.iter_rows(min_row=2, values_only=True), start=2):
                # Ignorar linhas completamente vazias
                if not any(row):
                    continue

                # Colunas esperadas: codigo | nome | unidade | quantidade | estoque_minimo
                try:
                    codigo    = str(row[0]).strip() if row[0] else None
                    nome      = str(row[1]).strip() if row[1] else None
                    unidade   = str(row[2]).strip() if row[2] else 'un'
                    quantidade = float(row[3]) if row[3] is not None else 0
                    est_min   = float(row[4]) if row[4] is not None else 0
                except Exception:
                    erros.append(f'Linha {row_num}: formato inválido')
                    continue

                if not codigo or not nome:
                    erros.append(f'Linha {row_num}: código ou nome vazio')
                    continue

                item_existente = Item.query.filter_by(codigo=codigo).first()

                if item_existente:
                    if modo in ('atualizar', 'substituir'):
                        item_existente.nome = nome
                        item_existente.unidade = unidade
                        item_existente.estoque_minimo = est_min
                        if modo == 'substituir':
                            item_existente.quantidade = quantidade
                        else:
                            item_existente.quantidade += quantidade
                        item_existente.almoxarifado_id = id
                        atualizados += 1
                    # modo 'adicionar' ignora duplicatas
                else:
                    novo = Item(
                        codigo=codigo, nome=nome, unidade=unidade,
                        quantidade=quantidade, estoque_minimo=est_min,
                        almoxarifado_id=id
                    )
                    db.session.add(novo)
                    inseridos += 1

            db.session.commit()

            msg = f'Importação concluída: {inseridos} inseridos, {atualizados} atualizados.'
            if erros:
                msg += f' {len(erros)} linha(s) com erro.'
            flash(msg, 'success' if not erros else 'warning')
            for e in erros[:10]:  # mostrar até 10 erros
                flash(e, 'danger')

        except Exception as e:
            flash(f'Erro ao processar arquivo: {str(e)}', 'danger')

        return redirect(url_for('almoxarifado', id=id))

    return render_template('importar_itens.html', almoxarifado=alm)

@app.route('/almoxarifado/<int:id>/modelo_excel')
@login_required
def modelo_excel(id):
    alm = Almoxarifado.query.get_or_404(id)
    wb = openpyxl.Workbook()
    ws = wb.active
    ws.title = 'Itens'
    h_fill = PatternFill('solid', fgColor='1A3A5C')
    h_font = Font(bold=True, color='FFFFFF', size=11)
    borda  = Border(left=Side(style='thin'), right=Side(style='thin'),
                    top=Side(style='thin'), bottom=Side(style='thin'))
    for col, h in enumerate(['Codigo', 'Nome', 'Unidade', 'Quantidade', 'Estoque Minimo'], 1):
        c = ws.cell(row=1, column=col, value=h)
        c.font = h_font; c.fill = h_fill
        c.alignment = Alignment(horizontal='center'); c.border = borda
    for r, ex in enumerate([('CIM-001','Cimento CP-II','sc',500,100),
                             ('ARG-002','Areia Grossa','m3',30,5),
                             ('EPI-003','Capacete','un',20,5)], 2):
        for c, v in enumerate(ex, 1):
            ws.cell(row=r, column=c, value=v).border = borda
    for i, w in enumerate([14,40,10,14,16], 1):
        ws.column_dimensions[get_column_letter(i)].width = w
    buf = io.BytesIO()
    wb.save(buf); buf.seek(0)
    return send_file(buf, as_attachment=True,
                     download_name=f'modelo_{alm.nome.replace(" ","_")}.xlsx',
                     mimetype='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')

@app.route('/relatorios/consumo')
@login_required
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
@login_required
def relatorio_alertas():
    itens = Item.query.filter(Item.quantidade <= Item.estoque_minimo).order_by(
        Item.fixado.desc(), Item.quantidade.asc()
    ).all()
    return render_template('relatorio_alertas.html', itens=itens)

@app.route('/item/<int:id>/status_compra', methods=['POST'])
@login_required
def atualizar_status_compra(id):
    it = Item.query.get_or_404(id)
    it.status_compra = request.form.get('status_compra', 'pendente')
    db.session.commit()
    return ('', 204)

@app.route('/item/<int:id>/fixar', methods=['POST'])
@login_required
def fixar_item(id):
    it = Item.query.get_or_404(id)
    it.fixado = not it.fixado
    db.session.commit()
    return jsonify({'fixado': it.fixado})

# ── EXPORTAR EXCEL ───────────────────────────────────────────────────────────

@app.route('/almoxarifado/<int:id>/exportar')
@login_required
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
@login_required
def api_alertas():
    itens = Item.query.filter(Item.quantidade <= Item.estoque_minimo).all()
    return jsonify([{
        'id': i.id, 'nome': i.nome, 'codigo': i.codigo,
        'quantidade': i.quantidade, 'estoque_minimo': i.estoque_minimo,
        'unidade': i.unidade, 'status': i.status,
        'almoxarifado': i.almoxarifado.nome
    } for i in itens])

# ── GERENCIAR USUÁRIOS (só admin) ────────────────────────────────────────────

@app.route('/usuarios')
@admin_required
def usuarios():
    return render_template('usuarios.html', usuarios=Usuario.query.all())

@app.route('/usuarios/novo', methods=['GET', 'POST'])
@admin_required
def novo_usuario():
    almoxarifados = Almoxarifado.query.all()
    if request.method == 'POST':
        u = Usuario(
            nome=request.form['nome'],
            login=request.form['login'],
            perfil=request.form['perfil'],
            almoxarifado_id=request.form.get('almoxarifado_id') or None
        )
        u.set_senha(request.form['senha'])
        db.session.add(u)
        db.session.commit()
        flash(f'Usuário "{u.nome}" criado!', 'success')
        return redirect(url_for('usuarios'))
    return render_template('form_usuario.html', usuario=None, almoxarifados=almoxarifados)

@app.route('/usuarios/<int:id>/editar', methods=['GET', 'POST'])
@admin_required
def editar_usuario(id):
    u = Usuario.query.get_or_404(id)
    almoxarifados = Almoxarifado.query.all()
    if request.method == 'POST':
        u.nome = request.form['nome']
        u.login = request.form['login']
        u.perfil = request.form['perfil']
        u.almoxarifado_id = request.form.get('almoxarifado_id') or None
        u.ativo = 'ativo' in request.form
        if request.form.get('senha'):
            u.set_senha(request.form['senha'])
        db.session.commit()
        flash('Usuário atualizado!', 'success')
        return redirect(url_for('usuarios'))
    return render_template('form_usuario.html', usuario=u, almoxarifados=almoxarifados)

@app.route('/usuarios/<int:id>/deletar', methods=['POST'])
@admin_required
def deletar_usuario(id):
    u = Usuario.query.get_or_404(id)
    db.session.delete(u)
    db.session.commit()
    flash('Usuário removido!', 'warning')
    return redirect(url_for('usuarios'))

# ── ACESSO EXTRA (substituto temporário) ─────────────────────────────────────

@app.route('/usuarios/<int:id>/acesso_extra', methods=['POST'])
@admin_required
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
    return redirect(url_for('editar_usuario', id=id))

@app.route('/acesso_extra/<int:id>/revogar', methods=['POST'])
@admin_required
def revogar_acesso_extra(id):
    a = AcessoExtra.query.get_or_404(id)
    uid = a.usuario_id
    db.session.delete(a)
    db.session.commit()
    flash('Acesso revogado!', 'warning')
    return redirect(url_for('editar_usuario', id=uid))

def seed_data():
    if Almoxarifado.query.count() == 0:
        db.session.add_all([
            Almoxarifado(nome='Almoxarifado do Acampamento', descricao='Materiais de uso geral do acampamento'),
            Almoxarifado(nome='Almoxarifado de Infraestrutura', descricao='Materiais de construcao e manutencao'),
            Almoxarifado(nome='Almoxarifado de Forma', descricao='Formas, escoramentos e materiais de forma'),
        ])
        db.session.commit()
    if Usuario.query.count() == 0:
        admin = Usuario(nome='Administrador', login='admin', perfil='admin')
        admin.set_senha('admin123')
        db.session.add(admin)
        db.session.commit()
        print('=' * 60)
        print('AVISO: Usuário admin criado com senha padrão: admin123')
        print('Altere a senha imediatamente após o primeiro login!')
        print('=' * 60)

with app.app_context():
    db.create_all()
    run_migrations()
    seed_data()

if __name__ == '__main__':
    with app.app_context():
        db.create_all()
        # Migração: adicionar colunas novas se não existirem
        try:
            from sqlalchemy import text
            with db.engine.connect() as conn:
                # Item: status_compra
                try:
                    conn.execute(text("ALTER TABLE item ADD COLUMN status_compra VARCHAR(30) DEFAULT 'pendente'"))
                    conn.commit()
                except: pass
                # Item: fixado
                try:
                    conn.execute(text("ALTER TABLE item ADD COLUMN fixado BOOLEAN DEFAULT 0"))
                    conn.commit()
                except: pass
                # Usuario: acessos_extras (tabela nova)
                conn.execute(text("""
                    CREATE TABLE IF NOT EXISTS acesso_extra (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        usuario_id INTEGER NOT NULL REFERENCES usuario(id),
                        almoxarifado_id INTEGER NOT NULL REFERENCES almoxarifado(id),
                        motivo VARCHAR(200),
                        data_inicio DATETIME,
                        data_fim DATETIME,
                        concedido_por VARCHAR(100)
                    )
                """))
                conn.commit()
        except Exception as e:
            print(f'Migração: {e}')
        seed_data()
    port = int(os.environ.get('PORT', 5000))
    print(f"Iniciando servidor na porta {port}")
    app.run(debug=False, host='0.0.0.0', port=port)

# Inicialização para produção (gunicorn)
with app.app_context():
    try:
        db.create_all()
        seed_data()
    except Exception as e:
        print(f'Inicialização: {e}')
