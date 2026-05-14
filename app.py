from flask import Flask, render_template, request, redirect, url_for, jsonify, flash, send_file, session
from flask_sqlalchemy import SQLAlchemy
from werkzeug.security import generate_password_hash, check_password_hash
from datetime import datetime, date, timezone, timedelta
from functools import wraps
import io
import os
import json
import secrets
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter

# ── CARREGA VARIÁVEIS DE AMBIENTE (com fallback para valores padrão) ─────────
# Se as variáveis não estiverem definidas no Railway, usa valores padrão
if not os.environ.get('BACKUP_EMAIL_FROM'):
    os.environ['BACKUP_EMAIL_FROM'] = 'rickgouveia157@gmail.com'
if not os.environ.get('BACKUP_EMAIL_PASS'):
    os.environ['BACKUP_EMAIL_PASS'] = 'bzesuxmiqaupvnly'
if not os.environ.get('BACKUP_EMAIL_TO'):
    os.environ['BACKUP_EMAIL_TO'] = 'rickgouveia157@gmail.com'

# ── DIAGNÓSTICO DE VARIÁVEIS DE AMBIENTE ─────────────────────────────────────
print('=' * 60)
print('DIAGNÓSTICO DE VARIÁVEIS DE AMBIENTE:')
print(f'  BACKUP_EMAIL_FROM = {os.environ.get("BACKUP_EMAIL_FROM", "NÃO DEFINIDO")}')
_pass = os.environ.get("BACKUP_EMAIL_PASS", "")
print(f'  BACKUP_EMAIL_PASS = {"SIM (" + _pass[:3] + "***)" if _pass else "NÃO DEFINIDO"}')
print(f'  BACKUP_EMAIL_TO   = {os.environ.get("BACKUP_EMAIL_TO", "NÃO DEFINIDO")}')
print(f'  DATABASE_URL      = {"SIM" if os.environ.get("DATABASE_URL") else "NÃO DEFINIDO"}')
print('=' * 60)

# Fuso horário de Brasília (UTC-3)
TZ_BRASILIA = timezone(timedelta(hours=-3))

def agora():
    """Retorna o datetime atual no horário de Brasília"""
    return datetime.now(TZ_BRASILIA).replace(tzinfo=None)

app = Flask(__name__)
app.secret_key = os.environ.get('SECRET_KEY') or secrets.token_hex(32)

# Railway fornece DATABASE_URL com prefixo "postgres://" (formato antigo),
# mas o SQLAlchemy 1.4+ exige "postgresql://". Corrige automaticamente.
_db_url = (
    os.environ.get('DATABASE_URL') or
    os.environ.get('URI_DO_BANCO_DE_DADOS') or
    f'sqlite:///{os.path.join(os.path.dirname(os.path.abspath(__file__)), "instance", "estoque.db")}'
)
if _db_url.startswith('postgres://'):
    _db_url = _db_url.replace('postgres://', 'postgresql://', 1)

app.config['SQLALCHEMY_DATABASE_URI'] = _db_url
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False

db = SQLAlchemy(app)

# ── FILTRO JINJA2 — formata quantidades sem ponto flutuante feio ─────────────
@app.template_filter('fmt_qtd')
def fmt_qtd(value):
    """Formata quantidade: inteiros sem decimal, decimais com até 4 casas limpas."""
    try:
        v = round(float(value), 4)
        if v == int(v):
            return str(int(v))
        # Remove zeros à direita
        return f"{v:.4f}".rstrip('0').rstrip('.')
    except (TypeError, ValueError):
        return value

# ── MODELOS ──────────────────────────────────────────────────────────────────

class Almoxarifado(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    nome = db.Column(db.String(100), nullable=False)
    descricao = db.Column(db.String(200))
    itens = db.relationship('Item', backref='almoxarifado', lazy=True, cascade='all, delete-orphan')

class Item(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    nome = db.Column(db.String(300), nullable=False)
    codigo = db.Column(db.String(50), unique=True, nullable=False)
    unidade = db.Column(db.String(20), nullable=False)
    quantidade = db.Column(db.Float, default=0)
    estoque_minimo = db.Column(db.Float, default=0)
    almoxarifado_id = db.Column(db.Integer, db.ForeignKey('almoxarifado.id'), nullable=False)
    status_compra = db.Column(db.String(30), default='pendente')
    fixado = db.Column(db.Boolean, default=False)
    ativo = db.Column(db.Boolean, default=True)
    categoria = db.Column(db.String(30), default='geral')  # 'epi', 'maquinario', 'geral'
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
    data = db.Column(db.DateTime, default=agora)
    item_id = db.Column(db.Integer, db.ForeignKey('item.id'), nullable=False)

class Requisicao(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    colaborador = db.Column(db.String(100), nullable=False)
    observacao = db.Column(db.String(200))
    quantidade = db.Column(db.Float, nullable=False)
    status = db.Column(db.String(20), default='aberta')  # aberta | devolvida
    data_retirada = db.Column(db.DateTime, default=agora)
    data_devolucao = db.Column(db.DateTime, nullable=True)
    item_id = db.Column(db.Integer, db.ForeignKey('item.id'), nullable=False)
    item = db.relationship('Item', backref='requisicoes')

# ── REQUISIÇÃO DO MESTRE ──────────────────────────────────────────────────────

class RequisicaoMestre(db.Model):
    """Requisição feita pelo mestre de obra ao almoxarifado.
    Fluxo: pendente → aprovada (almoxarife separa) → entregue (baixa no estoque)
    """
    id = db.Column(db.Integer, primary_key=True)
    mestre_id = db.Column(db.Integer, db.ForeignKey('usuario.id'), nullable=False)
    mestre = db.relationship('Usuario', foreign_keys=[mestre_id])
    colaborador = db.Column(db.String(100), nullable=False)
    almoxarifado_id = db.Column(db.Integer, db.ForeignKey('almoxarifado.id'), nullable=False)
    almoxarifado = db.relationship('Almoxarifado')
    observacao = db.Column(db.String(300))
    # Status: pendente | aprovada | parcial | recusada | entregue | cancelada
    status = db.Column(db.String(20), default='pendente')
    data_criacao = db.Column(db.DateTime, default=agora)
    data_entrega = db.Column(db.DateTime, nullable=True)
    entregue_por_id = db.Column(db.Integer, db.ForeignKey('usuario.id'), nullable=True)
    entregue_por = db.relationship('Usuario', foreign_keys=[entregue_por_id])
    # Itens da requisição
    itens = db.relationship('RequisicaoMestreItem', backref='requisicao', lazy=True, cascade='all, delete-orphan')

class RequisicaoMestreItem(db.Model):
    """Cada item dentro de uma RequisicaoMestre."""
    id = db.Column(db.Integer, primary_key=True)
    requisicao_id = db.Column(db.Integer, db.ForeignKey('requisicao_mestre.id'), nullable=False)
    item_id = db.Column(db.Integer, db.ForeignKey('item.id'), nullable=False)
    item = db.relationship('Item')
    quantidade = db.Column(db.Float, nullable=False)
    observacao = db.Column(db.String(200))

class Usuario(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    nome = db.Column(db.String(100), nullable=False)
    login = db.Column(db.String(50), unique=True, nullable=False)
    senha_hash = db.Column(db.String(256), nullable=False)
    perfil = db.Column(db.String(20), default='colaborador')  # admin | colaborador | mestre | almoxarife
    almoxarifado_id = db.Column(db.Integer, db.ForeignKey('almoxarifado.id'), nullable=True)
    email = db.Column(db.String(120), nullable=True)
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

class Colaborador(db.Model):
    """Banco de dados de colaboradores/peões que retiram material no almoxarifado."""
    id = db.Column(db.Integer, primary_key=True)
    nome = db.Column(db.String(100), nullable=False)
    funcao = db.Column(db.String(50))  # ex: pedreiro, servente, eletricista
    ativo = db.Column(db.Boolean, default=True)
    data_cadastro = db.Column(db.DateTime, default=agora)

class AcessoExtra(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    usuario_id = db.Column(db.Integer, db.ForeignKey('usuario.id'), nullable=False)
    almoxarifado_id = db.Column(db.Integer, db.ForeignKey('almoxarifado.id'), nullable=False)
    motivo = db.Column(db.String(200))
    data_inicio = db.Column(db.DateTime, default=agora)
    data_fim = db.Column(db.DateTime, nullable=True)
    concedido_por = db.Column(db.String(100))
    almoxarifado = db.relationship('Almoxarifado')

    @property
    def ativo(self):
        if self.data_fim and agora() > self.data_fim:
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

def almoxarife_required(f):
    """Permite acesso a admin e almoxarife."""
    @wraps(f)
    def decorated(*args, **kwargs):
        if 'usuario_id' not in session:
            return redirect(url_for('login'))
        u = Usuario.query.get(session['usuario_id'])
        if not u or u.perfil not in ('admin', 'almoxarife'):
            flash('Acesso restrito ao almoxarife.', 'danger')
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
    elif u.perfil == 'mestre':
        # Mestre vê só o almoxarifado dele
        alms = [u.almoxarifado] if u.almoxarifado_id else []
    else:
        ids = u.almoxarifados_permitidos()
        alms = Almoxarifado.query.filter(Almoxarifado.id.in_(ids)).all() if ids else []
    return dict(sidebar_alms=alms, usuario_atual=u)

def run_migrations():
    """Executa migrações de schema de forma segura usando SAVEPOINT no PostgreSQL."""
    try:
        from sqlalchemy import text
        is_pg = 'postgresql' in str(db.engine.url)

        def safe_exec(conn, sql):
            """Executa um comando DDL isolado — usa SAVEPOINT no PG para não quebrar a transação."""
            try:
                if is_pg:
                    conn.execute(text("SAVEPOINT mig"))
                conn.execute(text(sql))
                if is_pg:
                    conn.execute(text("RELEASE SAVEPOINT mig"))
                conn.commit()
            except Exception:
                if is_pg:
                    try:
                        conn.execute(text("ROLLBACK TO SAVEPOINT mig"))
                        conn.execute(text("RELEASE SAVEPOINT mig"))
                    except Exception:
                        pass
                else:
                    try:
                        conn.rollback()
                    except Exception:
                        pass

        with db.engine.connect() as conn:
            pk_type = 'SERIAL PRIMARY KEY' if is_pg else 'INTEGER PRIMARY KEY AUTOINCREMENT'
            dt_type = 'TIMESTAMP' if is_pg else 'DATETIME'

            # ── Colunas em item ──────────────────────────────────────────────
            safe_exec(conn, "ALTER TABLE item ADD COLUMN status_compra VARCHAR(30) DEFAULT 'pendente'")
            safe_exec(conn, "ALTER TABLE item ADD COLUMN fixado BOOLEAN DEFAULT 0")
            safe_exec(conn, "ALTER TABLE item ADD COLUMN ativo BOOLEAN DEFAULT 1")
            safe_exec(conn, "ALTER TABLE item ADD COLUMN categoria VARCHAR(30) DEFAULT 'geral'")
            if is_pg:
                safe_exec(conn, "ALTER TABLE item ALTER COLUMN nome TYPE VARCHAR(300)")

            # ── Coluna email em usuario (crítica — deve rodar cedo) ──────────
            safe_exec(conn, "ALTER TABLE usuario ADD COLUMN email VARCHAR(120)")
            safe_exec(conn, "ALTER TABLE usuario ADD COLUMN senha_hash_new VARCHAR(256)")

            # ── Tabela acesso_extra ──────────────────────────────────────────
            if is_pg:
                safe_exec(conn, """
                    CREATE TABLE IF NOT EXISTS acesso_extra (
                        id SERIAL PRIMARY KEY,
                        usuario_id INTEGER NOT NULL REFERENCES usuario(id),
                        almoxarifado_id INTEGER NOT NULL REFERENCES almoxarifado(id),
                        motivo VARCHAR(200),
                        data_inicio TIMESTAMP,
                        data_fim TIMESTAMP,
                        concedido_por VARCHAR(100)
                    )
                """)
            else:
                safe_exec(conn, """
                    CREATE TABLE IF NOT EXISTS acesso_extra (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        usuario_id INTEGER NOT NULL REFERENCES usuario(id),
                        almoxarifado_id INTEGER NOT NULL REFERENCES almoxarifado(id),
                        motivo VARCHAR(200),
                        data_inicio DATETIME,
                        data_fim DATETIME,
                        concedido_por VARCHAR(100)
                    )
                """)

            # ── Tabelas do mestre ────────────────────────────────────────────
            safe_exec(conn, f"""
                CREATE TABLE IF NOT EXISTS requisicao_mestre (
                    id {pk_type},
                    mestre_id INTEGER NOT NULL REFERENCES usuario(id),
                    colaborador VARCHAR(100) NOT NULL,
                    almoxarifado_id INTEGER NOT NULL REFERENCES almoxarifado(id),
                    observacao VARCHAR(300),
                    status VARCHAR(20) DEFAULT 'pendente',
                    data_criacao {dt_type},
                    data_entrega {dt_type},
                    entregue_por_id INTEGER REFERENCES usuario(id),
                    notificado BOOLEAN DEFAULT FALSE
                )
            """)
            safe_exec(conn, f"""
                CREATE TABLE IF NOT EXISTS requisicao_mestre_item (
                    id {pk_type},
                    requisicao_id INTEGER NOT NULL REFERENCES requisicao_mestre(id),
                    item_id INTEGER NOT NULL REFERENCES item(id),
                    quantidade FLOAT NOT NULL,
                    observacao VARCHAR(200),
                    status_item VARCHAR(20) DEFAULT 'pendente',
                    motivo_recusa VARCHAR(200)
                )
            """)
            safe_exec(conn, f"""
                CREATE TABLE IF NOT EXISTS colaborador (
                    id {pk_type},
                    nome VARCHAR(100) NOT NULL,
                    funcao VARCHAR(50),
                    ativo BOOLEAN DEFAULT TRUE,
                    data_cadastro {dt_type}
                )
            """)

            # ── Colunas adicionais em tabelas existentes ─────────────────────
            safe_exec(conn, "ALTER TABLE requisicao_mestre ADD COLUMN notificado BOOLEAN DEFAULT FALSE")
            safe_exec(conn, "ALTER TABLE requisicao_mestre_item ADD COLUMN status_item VARCHAR(20) DEFAULT 'pendente'")
            safe_exec(conn, "ALTER TABLE requisicao_mestre_item ADD COLUMN motivo_recusa VARCHAR(200)")

            # ── Valores padrão para colunas que podem estar NULL ─────────────
            safe_exec(conn, "UPDATE requisicao_mestre_item SET status_item = 'pendente' WHERE status_item IS NULL")
            safe_exec(conn, "UPDATE requisicao_mestre SET notificado = FALSE WHERE notificado IS NULL")

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
    # Mestre só acessa a tela de requisições
    if u.perfil == 'mestre':
        return redirect(url_for('mestre_requisicoes'))
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
    # Mestre não acessa almoxarifado diretamente
    if u.perfil == 'mestre':
        flash('Acesso restrito. Use a tela de requisições.', 'warning')
        return redirect(url_for('mestre_requisicoes'))
    alm = Almoxarifado.query.get_or_404(id)
    if u.perfil != 'admin' and id not in u.almoxarifados_permitidos():
        flash('Acesso negado.', 'danger')
        return redirect(url_for('index'))
    # Mostrar todos os itens (ativos e desativados) para permitir reativação
    itens = Item.query.filter_by(almoxarifado_id=id).order_by(Item.ativo.desc(), Item.nome).all()
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
            almoxarifado_id=int(request.form['almoxarifado_id']),
            categoria=request.form.get('categoria', 'geral')
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
        it.categoria = request.form.get('categoria', 'geral')
        # Atualizar quantidade se informada (admin pode corrigir o valor)
        qtd_str = request.form.get('quantidade')
        if qtd_str is not None and qtd_str != '':
            try:
                nova_qtd = float(qtd_str)
                if nova_qtd != it.quantidade:
                    # Registrar ajuste como movimentação
                    u = usuario_atual()
                    diff = nova_qtd - it.quantidade
                    tipo = 'entrada' if diff > 0 else 'saida'
                    db.session.add(Movimentacao(
                        tipo=tipo,
                        quantidade=abs(diff),
                        responsavel=u.nome if u else 'Sistema',
                        observacao=f'Ajuste manual: {it.quantidade} → {nova_qtd} {it.unidade}',
                        item_id=it.id
                    ))
                    it.quantidade = nova_qtd
            except ValueError:
                pass
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

            it.quantidade = round(it.quantidade + qtd if tipo == 'entrada' else it.quantidade - qtd, 4)
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
    responsavel = request.form.get('responsavel', '').strip()
    observacao = request.form.get('observacao', '').strip()

    if tipo == 'saida' and qtd > it.quantidade:
        flash('Quantidade insuficiente em estoque!', 'danger')
        return redirect(url_for('item', id=id))

    # Na saída, a observação já vem montada pelo JS como "liberado P/ Nome | req X"
    obs_final = observacao

    it.quantidade = round(it.quantidade + qtd if tipo == 'entrada' else it.quantidade - qtd, 4)
    mov = Movimentacao(
        tipo=tipo, quantidade=qtd,
        responsavel=responsavel,
        observacao=obs_final,
        item_id=id
    )
    db.session.add(mov)
    db.session.commit()
    flash(f'{"Entrada" if tipo == "entrada" else "Saída"} de {qtd} {it.unidade} registrada!', 'success')
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
        req.data_devolucao = agora()
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
                        item_existente.almoxarifado_id = id
                        if modo == 'substituir':
                            # Só sobrescreve a quantidade se o item não tiver movimentações
                            # Isso protege contra reimportação acidental de dados antigos
                            tem_movimentacoes = Movimentacao.query.filter_by(item_id=item_existente.id).count() > 0
                            if not tem_movimentacoes:
                                item_existente.quantidade = quantidade
                            # Se tiver movimentações, ignora a quantidade do Excel (protege o saldo real)
                        else:  # atualizar = somar
                            item_existente.quantidade += quantidade
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

@app.route('/relatorios/consumo/exportar')
@login_required
def exportar_consumo():
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

    wb = openpyxl.Workbook()
    ws = wb.active
    ws.title = 'Consumo'
    h_fill = PatternFill('solid', fgColor='1A3A5C')
    h_font = Font(bold=True, color='FFFFFF', size=11)
    borda   = Border(left=Side(style='thin'), right=Side(style='thin'),
                     top=Side(style='thin'), bottom=Side(style='thin'))

    alm_nome = Almoxarifado.query.get(alm_id).nome if alm_id else 'Todos os Almoxarifados'
    ws.merge_cells('A1:G1')
    ws['A1'] = f'Relatório de Consumo — {alm_nome}'
    ws['A1'].font = Font(bold=True, size=13, color='1A3A5C')
    ws.merge_cells('A2:G2')
    ws['A2'] = f'Período: {data_ini} a {data_fim}   |   Gerado em: {agora().strftime("%d/%m/%Y %H:%M")}'
    ws['A2'].font = Font(italic=True, size=10, color='666666')

    headers = ['Data', 'Código', 'Item', 'Almoxarifado', 'Quantidade', 'Responsável', 'Observação']
    for col, h in enumerate(headers, 1):
        c = ws.cell(row=4, column=col, value=h)
        c.font = h_font; c.fill = h_fill
        c.alignment = Alignment(horizontal='center'); c.border = borda

    for row_num, mov in enumerate(movs, 5):
        dados = [
            mov.data.strftime('%d/%m/%Y %H:%M'),
            mov.item.codigo, mov.item.nome,
            mov.item.almoxarifado.nome,
            f'{mov.quantidade} {mov.item.unidade}',
            mov.responsavel or '', mov.observacao or ''
        ]
        for col, val in enumerate(dados, 1):
            c = ws.cell(row=row_num, column=col, value=val)
            c.border = borda
            if row_num % 2 == 0:
                c.fill = PatternFill('solid', fgColor='F0F4F8')

    for i, w in enumerate([18, 14, 45, 35, 14, 20, 40], 1):
        ws.column_dimensions[get_column_letter(i)].width = w

    buf = io.BytesIO()
    wb.save(buf); buf.seek(0)
    return send_file(buf, as_attachment=True,
                     download_name=f'consumo_{data_ini}_a_{data_fim}.xlsx',
                     mimetype='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')

@app.route('/relatorios/consumo-por-pessoa')
@login_required
def relatorio_consumo_pessoa():
    """Relatório de consumo agrupado por colaborador (extraído da observação)."""
    alm_id = request.args.get('almoxarifado_id', type=int)
    data_ini = request.args.get('data_ini', str(date.today().replace(day=1)))
    data_fim = request.args.get('data_fim', str(date.today()))
    responsavel_filtro = request.args.get('responsavel', '').strip()

    query = Movimentacao.query.filter(
        Movimentacao.tipo == 'saida',
        Movimentacao.data >= data_ini,
        Movimentacao.data <= data_fim + ' 23:59:59'
    )
    if alm_id:
        query = query.join(Item).filter(Item.almoxarifado_id == alm_id)

    movs = query.order_by(Movimentacao.data.desc()).all()

    import re

    def extrair_colaborador(mov):
        """Extrai o nome do colaborador da observação ou usa o responsável."""
        obs = mov.observacao or ''
        # Formato: "liberado P/ Nome" ou "liberado para Nome"
        m = re.search(r'liberado\s+[Pp][/\s]+(.+)', obs, re.IGNORECASE)
        if m:
            return m.group(1).strip()
        # Formato: "req XXXX | Colaborador: Nome"
        m = re.search(r'[Cc]olaborador[:\s]+([^|]+)', obs)
        if m:
            return m.group(1).strip()
        # Formato: "Req. Mestre #X — Colaborador: Nome"
        m = re.search(r'Colaborador[:\s]+(.+)', obs)
        if m:
            return m.group(1).strip()
        # Se não achou colaborador na obs, usa o responsável
        return mov.responsavel or 'Sem responsável'

    # Filtrar por nome se informado
    from collections import defaultdict
    por_pessoa = defaultdict(list)
    for mov in movs:
        colaborador = extrair_colaborador(mov)
        if responsavel_filtro and responsavel_filtro.lower() not in colaborador.lower():
            continue
        por_pessoa[colaborador].append(mov)

    # Calcular totais por pessoa
    resumo = []
    for nome, lista in sorted(por_pessoa.items()):
        total_movs = len(lista)
        itens_distintos = len(set(m.item_id for m in lista))
        resumo.append({
            'nome': nome,
            'movimentacoes': lista,
            'total_movs': total_movs,
            'itens_distintos': itens_distintos,
        })

    almoxarifados = Almoxarifado.query.all()
    return render_template('relatorio_consumo_pessoa.html',
                           resumo=resumo,
                           almoxarifados=almoxarifados,
                           data_ini=data_ini,
                           data_fim=data_fim,
                           alm_id=alm_id,
                           responsavel_filtro=responsavel_filtro,
                           total_geral=sum(p['total_movs'] for p in resumo))

@app.route('/relatorios/ficha-epi')
@login_required
def ficha_epi():
    """Página para gerar ficha de EPI individual por funcionário."""
    import re
    funcionarios = set()
    movs = Movimentacao.query.join(Item).filter(
        Movimentacao.tipo == 'saida', Item.categoria == 'epi').all()
    for mov in movs:
        obs = mov.observacao or ''
        m = re.search(r'liberado\s+[Pp][/\s]+(.+)', obs, re.IGNORECASE)
        if m:
            funcionarios.add(m.group(1).strip())
        elif 'Colaborador:' in obs:
            nome = obs.split('Colaborador:')[-1].split('|')[0].strip()
            if nome:
                funcionarios.add(nome)
    return render_template('ficha_epi.html',
                           funcionarios=sorted(funcionarios),
                           data_ini='2020-01-01',
                           data_fim=str(date.today()))


@app.route('/relatorios/ficha-epi/exportar')
@login_required
def exportar_ficha_epi():
    """Exporta FORM.SEG.014 — Ficha de Controle de EPIs e Uniformes."""
    import re
    funcionario = request.args.get('funcionario', '').strip()
    data_ini    = request.args.get('data_ini', '2020-01-01')
    data_fim    = request.args.get('data_fim', str(date.today()))

    if not funcionario:
        flash('Selecione um funcionário.', 'warning')
        return redirect(url_for('ficha_epi'))

    movs_todas = (Movimentacao.query.join(Item)
                  .filter(Movimentacao.tipo == 'saida',
                          Item.categoria == 'epi',
                          Movimentacao.data >= data_ini,
                          Movimentacao.data <= data_fim + ' 23:59:59')
                  .order_by(Movimentacao.data.asc()).all())

    def extrair_colab(mov):
        obs = mov.observacao or ''
        m = re.search(r'liberado\s+[Pp][/\s]+(.+)', obs, re.IGNORECASE)
        if m: return m.group(1).strip()
        m = re.search(r'[Cc]olaborador[:\s]+([^|]+)', obs)
        if m: return m.group(1).strip()
        return mov.responsavel or ''

    lista = [m for m in movs_todas if extrair_colab(m).lower() == funcionario.lower()]

    if not lista:
        flash(f'Nenhuma retirada de EPI encontrada para "{funcionario}" no período.', 'warning')
        return redirect(url_for('ficha_epi'))

    # ── Estilos ──────────────────────────────────────────────────────────────
    borda = Border(left=Side(style='thin'), right=Side(style='thin'),
                   top=Side(style='thin'), bottom=Side(style='thin'))
    borda_med = Border(left=Side(style='medium'), right=Side(style='medium'),
                       top=Side(style='medium'), bottom=Side(style='medium'))
    centro = Alignment(horizontal='center', vertical='center', wrap_text=True)
    esq    = Alignment(horizontal='left',   vertical='center', wrap_text=True)
    azul_esc = PatternFill('solid', fgColor='1F3864')
    azul_cla = PatternFill('solid', fgColor='BDD7EE')
    cinza    = PatternFill('solid', fgColor='F2F2F2')

    wb = openpyxl.Workbook()
    ws = wb.active
    ws.title = funcionario[:28]

    # Larguras: A=Qtd | B=Descrição | C=C.A. | D=Data Ent | E=Assin Ent | F=Data Dev | G=Assin Dev | H=Motivo
    for col, w in zip('ABCDEFGH', [8, 40, 10, 14, 26, 14, 26, 18]):
        ws.column_dimensions[col].width = w

    def celula(ws, ref, val='', font=None, fill=None, aln=None, brd=None, height=None):
        c = ws[ref]
        c.value = val
        if font:  c.font = font
        if fill:  c.fill = fill
        if aln:   c.alignment = aln
        if brd:   c.border = brd
        return c

    def merge_row(ws, row, col_ini, col_fim, val='', font=None, fill=None, aln=None, height=None):
        ws.merge_cells(f'{col_ini}{row}:{col_fim}{row}')
        c = ws[f'{col_ini}{row}']
        c.value = val
        if font:  c.font = font
        if fill:  c.fill = fill
        if aln:   c.alignment = aln
        for col in range(ord(col_ini)-64, ord(col_fim)-64+1):
            ws.cell(row=row, column=col).border = borda
        if height: ws.row_dimensions[row].height = height
        return c

    # ── LINHA 1: Cabeçalho principal ─────────────────────────────────────────
    ws.row_dimensions[1].height = 36
    ws.merge_cells('A1:C1')
    ws['A1'].value = 'STANZA'
    ws['A1'].font = Font(bold=True, size=22, color='808080')
    ws['A1'].alignment = centro
    for c in range(1,4): ws.cell(1,c).border = borda_med

    ws.merge_cells('D1:F1')
    ws['D1'].value = 'FICHA DE CONTROLE DE EPI\'S E UNIFORMES'
    ws['D1'].font = Font(bold=True, size=13, color='1F3864')
    ws['D1'].alignment = centro
    ws['D1'].fill = azul_cla
    for c in range(4,7): ws.cell(1,c).border = borda_med

    ws.merge_cells('G1:H1')
    ws['G1'].value = 'FORM.SEG.014'
    ws['G1'].font = Font(bold=True, size=9, color='1F3864')
    ws['G1'].alignment = centro
    ws['G1'].fill = azul_cla
    for c in range(7,9): ws.cell(1,c).border = borda_med

    # ── LINHA 2: Data elaboração ──────────────────────────────────────────────
    ws.row_dimensions[2].height = 14
    ws.merge_cells('D2:F2')
    ws['D2'].value = 'Data Elaboração/Revisão: 20/10/2024'
    ws['D2'].font = Font(size=8, italic=True, color='595959')
    ws['D2'].alignment = centro
    ws.merge_cells('G2:H2')
    ws['G2'].value = 'Revisão: 00'
    ws['G2'].font = Font(size=8, italic=True, color='595959')
    ws['G2'].alignment = centro
    for c in range(1,9): ws.cell(2,c).border = borda

    # ── LINHA 3: Dados do funcionário ────────────────────────────────────────
    ws.row_dimensions[3].height = 22
    ws.merge_cells('A3:B3')
    ws['A3'].value = f'NOME: {funcionario.upper()}'
    ws['A3'].font = Font(bold=True, size=10)
    ws['A3'].alignment = esq
    ws['A3'].fill = cinza

    ws['C3'].value = f'MATRÍCULA:'
    ws['C3'].font = Font(size=9)
    ws['C3'].alignment = centro
    ws['C3'].fill = cinza

    ws.merge_cells('D3:E3')
    ws['D3'].value = 'FUNÇÃO:'
    ws['D3'].font = Font(size=9)
    ws['D3'].alignment = esq
    ws['D3'].fill = cinza

    ws.merge_cells('F3:G3')
    ws['F3'].value = f'ADMISSÃO:'
    ws['F3'].font = Font(size=9)
    ws['F3'].alignment = esq
    ws['F3'].fill = cinza

    ws['H3'].value = ''
    ws['H3'].fill = cinza
    for c in range(1,9): ws.cell(3,c).border = borda

    # ── LINHA 4: Cabeçalho da tabela ─────────────────────────────────────────
    ws.row_dimensions[4].height = 20
    for ref, val in [('A4','QUANT'), ('B4','DESCRIÇÃO'), ('C4','C.A.')]:
        ws[ref].value = val
        ws[ref].font = Font(bold=True, color='FFFFFF', size=9)
        ws[ref].fill = azul_esc
        ws[ref].alignment = centro
        ws[ref].border = borda

    ws.merge_cells('D4:E4')
    ws['D4'].value = 'ENTREGA'
    ws['D4'].font = Font(bold=True, color='FFFFFF', size=9)
    ws['D4'].fill = azul_esc
    ws['D4'].alignment = centro
    for c in range(4,6): ws.cell(4,c).border = borda

    ws.merge_cells('F4:G4')
    ws['F4'].value = 'DEVOLUÇÃO'
    ws['F4'].font = Font(bold=True, color='FFFFFF', size=9)
    ws['F4'].fill = azul_esc
    ws['F4'].alignment = centro
    for c in range(6,8): ws.cell(4,c).border = borda

    ws['H4'].value = 'MOTIVO'
    ws['H4'].font = Font(bold=True, color='FFFFFF', size=9)
    ws['H4'].fill = azul_esc
    ws['H4'].alignment = centro
    ws['H4'].border = borda

    # ── LINHA 5: Sub-cabeçalho ───────────────────────────────────────────────
    ws.row_dimensions[5].height = 16
    for ref, val in [('A5',''), ('B5',''), ('C5',''),
                     ('D5','DATA'), ('E5','ASSINATURA'),
                     ('F5','DATA'), ('G5','ASSINATURA'), ('H5','')]:
        ws[ref].value = val
        ws[ref].font = Font(bold=True, color='FFFFFF', size=8)
        ws[ref].fill = azul_esc
        ws[ref].alignment = centro
        ws[ref].border = borda

    # ── LINHAS DE DADOS ──────────────────────────────────────────────────────
    row = 6
    for mov in lista:
        ws.row_dimensions[row].height = 18
        fill_z = PatternFill('solid', fgColor='EBF3FB') if row % 2 == 0 else None
        for col, val in zip('ABCDEFGH', [
            f'{mov.quantidade} {mov.item.unidade}',
            mov.item.nome, '',
            mov.data.strftime('%d/%m/%Y'), '',
            '', '', ''
        ]):
            c = ws[f'{col}{row}']
            c.value = val
            c.font = Font(size=9)
            c.alignment = esq if col == 'B' else centro
            c.border = borda
            if fill_z: c.fill = fill_z
        row += 1

    # Linhas em branco (mínimo 14 no total conforme formulário)
    total_linhas = max(14, len(lista) + 4)
    while row <= 5 + total_linhas:
        ws.row_dimensions[row].height = 18
        for col in 'ABCDEFGH':
            ws[f'{col}{row}'].border = borda
            ws[f'{col}{row}'].value = '/    /' if col in ('D','F') else ''
            ws[f'{col}{row}'].font = Font(size=9, color='BFBFBF')
            ws[f'{col}{row}'].alignment = centro
        row += 1

    # ── TERMO DE RESPONSABILIDADE ────────────────────────────────────────────
    row += 1
    ws.row_dimensions[row].height = 16
    ws.merge_cells(f'A{row}:H{row}')
    ws[f'A{row}'].value = 'TERMO DE RESPONSABILIDADE'
    ws[f'A{row}'].font = Font(bold=True, size=10, color='1F3864')
    ws[f'A{row}'].alignment = centro
    ws[f'A{row}'].fill = azul_cla
    for c in range(1,9): ws.cell(row,c).border = borda
    row += 1

    ws.row_dimensions[row].height = 70
    ws.merge_cells(f'A{row}:H{row}')
    ws[f'A{row}'].value = (
        'Pelo presente declaro que recebi da empresa STANZA INCORPORAÇÃO E CONSTRUÇÃO LTDA., os materiais '
        'relacionados nesta ficha, assumindo o compromisso nos termos das letras "a" e "b" do ítem 1.8 da NR 1 '
        'e letras "a","b"e "c" do ítem 6.7.1 da NR 6, de usá-los em atividades ligadas ao trabalho, zelar pela '
        'sua guarda, conservação e devolvê-lo ao setor competente quando se tornar impróprio para uso ou por '
        'motivo de demissão ou afastamento.\n'
        'Em caso de perda, extravio e inutilização proposital do material recebido, autorizo a empresa, na forma '
        'prevista no parágrafo primeiro do art. 462 da CLT - Consolidação das leis do trabalho. A descontar de '
        'meu salário, inclusive no que me couber a título de indenização por rescisão de contrato de trabalho, '
        'a importância correspondente ao valor do material.'
    )
    ws[f'A{row}'].font = Font(size=8)
    ws[f'A{row}'].alignment = Alignment(horizontal='left', vertical='top', wrap_text=True)
    for c in range(1,9): ws.cell(row,c).border = borda
    row += 2

    # Linha de data e assinatura
    ws.row_dimensions[row].height = 28
    ws.merge_cells(f'A{row}:C{row}')
    ws[f'A{row}'].value = 'Data:        /        /'
    ws[f'A{row}'].font = Font(size=10)
    ws[f'A{row}'].alignment = esq
    for c in range(1,4): ws.cell(row,c).border = borda

    ws.merge_cells(f'D{row}:H{row}')
    ws[f'D{row}'].value = 'EMPREGADO'
    ws[f'D{row}'].font = Font(bold=True, size=10, color='1F3864')
    ws[f'D{row}'].alignment = centro
    ws[f'D{row}'].fill = azul_cla
    for c in range(4,9): ws.cell(row,c).border = borda

    buf = io.BytesIO()
    wb.save(buf); buf.seek(0)
    nome_safe = funcionario.replace(' ', '_').replace('/', '-')
    return send_file(buf, as_attachment=True,
                     download_name=f'FORM-SEG-014_{nome_safe}_{data_fim}.xlsx',
                     mimetype='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')


@app.route('/movimentacoes/excluir', methods=['POST'])
@admin_required
def excluir_movimentacoes():
    ids = request.form.getlist('mov_ids')
    if not ids:
        flash('Nenhuma movimentação selecionada.', 'warning')
        return redirect(request.referrer or url_for('relatorio_consumo_pessoa'))
    
    excluidas = 0
    for mov_id in ids:
        mov = Movimentacao.query.get(mov_id)
        if mov:
            # Reverter o estoque ao excluir saída
            if mov.tipo == 'saida':
                mov.item.quantidade += mov.quantidade
            elif mov.tipo == 'entrada':
                mov.item.quantidade -= mov.quantidade
            db.session.delete(mov)
            excluidas += 1
    
    db.session.commit()
    flash(f'{excluidas} movimentação(ões) excluída(s) e estoque revertido.', 'success')
    return redirect(request.referrer or url_for('relatorio_consumo_pessoa'))

@app.route('/relatorios/alertas')
@login_required
def relatorio_alertas():
    u = usuario_atual()
    if u.perfil == 'admin':
        itens = Item.query.filter(Item.quantidade <= Item.estoque_minimo).order_by(
            Item.fixado.desc(), Item.quantidade.asc()
        ).all()
    else:
        ids = u.almoxarifados_permitidos()
        itens = Item.query.filter(
            Item.quantidade <= Item.estoque_minimo,
            Item.almoxarifado_id.in_(ids)
        ).order_by(Item.fixado.desc(), Item.quantidade.asc()).all() if ids else []
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

@app.route('/item/<int:id>/desativar', methods=['POST'])
@login_required
def desativar_item(id):
    it = Item.query.get_or_404(id)
    u = usuario_atual()
    if u.perfil != 'admin' and it.almoxarifado_id not in u.almoxarifados_permitidos():
        flash('Acesso negado.', 'danger')
        return redirect(url_for('item', id=id))
    
    # Registrar saída do saldo restante se houver
    if it.quantidade > 0:
        mov = Movimentacao(
            tipo='saida',
            quantidade=it.quantidade,
            responsavel=u.nome,
            observacao=f'Item desativado - saldo restante: {it.quantidade} {it.unidade}',
            item_id=id
        )
        db.session.add(mov)
        it.quantidade = 0
    
    it.ativo = False
    db.session.commit()
    flash(f'Item "{it.nome}" desativado com sucesso!', 'warning')
    return redirect(url_for('item', id=id))

@app.route('/item/<int:id>/reativar', methods=['POST'])
@login_required
def reativar_item(id):
    it = Item.query.get_or_404(id)
    u = usuario_atual()
    if u.perfil != 'admin' and it.almoxarifado_id not in u.almoxarifados_permitidos():
        flash('Acesso negado.', 'danger')
        return redirect(url_for('item', id=id))
    
    it.ativo = True
    db.session.commit()
    flash(f'Item "{it.nome}" reativado com sucesso!', 'success')
    return redirect(url_for('item', id=id))

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

@app.route('/api/colaboradores')
@login_required
def api_colaboradores():
    """Autocomplete — busca nomes do banco de colaboradores + histórico de requisições."""
    q = request.args.get('q', '').strip()
    nomes = set()
    like = f"%{q}%"
    is_pg = 'postgresql' in str(db.engine.url)
    ilike = 'ILIKE' if is_pg else 'LIKE'

    from sqlalchemy import text

    # 1. Banco de colaboradores cadastrados
    rows = db.session.execute(
        text(f"SELECT nome FROM colaborador WHERE ativo = TRUE AND nome {ilike} :q ORDER BY nome LIMIT 10"),
        {"q": like}
    ).fetchall()
    for r in rows:
        nomes.add(r[0])

    # 2. Histórico de requisições do mestre
    rows = db.session.execute(
        text(f"SELECT DISTINCT colaborador FROM requisicao_mestre WHERE colaborador {ilike} :q ORDER BY colaborador LIMIT 10"),
        {"q": like}
    ).fetchall()
    for r in rows:
        nomes.add(r[0])

    # 3. Histórico de requisições simples
    rows = db.session.execute(
        text(f"SELECT DISTINCT colaborador FROM requisicao WHERE colaborador {ilike} :q ORDER BY colaborador LIMIT 10"),
        {"q": like}
    ).fetchall()
    for r in rows:
        nomes.add(r[0])

    resultado = sorted(nomes)[:10]
    return jsonify([{'nome': n} for n in resultado])

# ── GERENCIAR COLABORADORES ──────────────────────────────────────────────────

@app.route('/colaboradores')
@login_required
def colaboradores():
    u = usuario_atual()
    if u.perfil not in ('admin', 'almoxarife'):
        flash('Acesso negado.', 'danger')
        return redirect(url_for('index'))
    cols = Colaborador.query.order_by(Colaborador.ativo.desc(), Colaborador.nome).all()
    return render_template('colaboradores.html', colaboradores=cols)

@app.route('/colaboradores/novo', methods=['POST'])
@login_required
def novo_colaborador():
    u = usuario_atual()
    if u.perfil not in ('admin', 'almoxarife'):
        flash('Acesso negado.', 'danger')
        return redirect(url_for('index'))
    nome = request.form.get('nome', '').strip()
    funcao = request.form.get('funcao', '').strip()
    if not nome:
        flash('Informe o nome do colaborador.', 'warning')
        return redirect(url_for('colaboradores'))
    # Evita duplicata
    if Colaborador.query.filter(Colaborador.nome.ilike(nome)).first():
        flash(f'Colaborador "{nome}" já está cadastrado.', 'warning')
        return redirect(url_for('colaboradores'))
    db.session.add(Colaborador(nome=nome, funcao=funcao or None))
    db.session.commit()
    flash(f'✅ Colaborador "{nome}" cadastrado!', 'success')
    return redirect(url_for('colaboradores'))

@app.route('/colaboradores/<int:id>/desativar', methods=['POST'])
@login_required
def desativar_colaborador(id):
    u = usuario_atual()
    if u.perfil not in ('admin', 'almoxarife'):
        flash('Acesso negado.', 'danger')
        return redirect(url_for('index'))
    c = Colaborador.query.get_or_404(id)
    c.ativo = False
    db.session.commit()
    flash(f'Colaborador "{c.nome}" desativado.', 'warning')
    return redirect(url_for('colaboradores'))

@app.route('/colaboradores/<int:id>/reativar', methods=['POST'])
@login_required
def reativar_colaborador(id):
    u = usuario_atual()
    if u.perfil not in ('admin', 'almoxarife'):
        flash('Acesso negado.', 'danger')
        return redirect(url_for('index'))
    c = Colaborador.query.get_or_404(id)
    c.ativo = True
    db.session.commit()
    flash(f'Colaborador "{c.nome}" reativado.', 'success')
    return redirect(url_for('colaboradores'))

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
            almoxarifado_id=request.form.get('almoxarifado_id') or None,
            email=request.form.get('email', '').strip() or None
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
    
    # Proteção: Henrique (r111) não pode ser editado por outros admins
    if u.login == 'r111' and usuario_atual().login != 'r111':
        flash('❌ Este usuário está protegido e não pode ser editado.', 'danger')
        return redirect(url_for('usuarios'))
    
    almoxarifados = Almoxarifado.query.all()
    if request.method == 'POST':
        u.nome = request.form['nome']
        u.login = request.form['login']
        u.perfil = request.form['perfil']
        u.almoxarifado_id = request.form.get('almoxarifado_id') or None
        u.email = request.form.get('email', '').strip() or None
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
    
    # Proteção: Henrique (r111) não pode ser deletado
    if u.login == 'r111':
        flash('❌ Este usuário está protegido e não pode ser removido.', 'danger')
        return redirect(url_for('usuarios'))
    
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

# ── REQUISIÇÕES DO MESTRE DE OBRA ────────────────────────────────────────────

@app.route('/mestre/requisicoes')
@login_required
def mestre_requisicoes():
    """Lista de requisições do mestre logado."""
    u = usuario_atual()
    if u.perfil == 'mestre':
        reqs = RequisicaoMestre.query.filter_by(mestre_id=u.id).order_by(RequisicaoMestre.data_criacao.desc()).all()
    elif u.perfil in ('admin', 'almoxarife'):
        # Almoxarife vê requisições do seu almoxarifado
        if u.perfil == 'almoxarife' and u.almoxarifado_id:
            reqs = RequisicaoMestre.query.filter_by(almoxarifado_id=u.almoxarifado_id).order_by(RequisicaoMestre.data_criacao.desc()).all()
        else:
            reqs = RequisicaoMestre.query.order_by(RequisicaoMestre.data_criacao.desc()).all()
    else:
        flash('Acesso negado.', 'danger')
        return redirect(url_for('index'))
    return render_template('mestre_requisicoes.html', requisicoes=reqs)

@app.route('/mestre/requisicoes/nova', methods=['GET', 'POST'])
@login_required
def mestre_requisicao_nova():
    """Mestre cria nova requisição."""
    u = usuario_atual()
    if u.perfil not in ('mestre', 'admin'):
        flash('Apenas mestres podem criar requisições.', 'danger')
        return redirect(url_for('index'))

    # Almoxarifado do mestre
    if u.perfil == 'mestre':
        if not u.almoxarifado_id:
            flash('Você não está vinculado a nenhum almoxarifado. Contate o administrador.', 'warning')
            return redirect(url_for('mestre_requisicoes'))
        almoxarifados = [u.almoxarifado]
    else:
        almoxarifados = Almoxarifado.query.all()

    itens_json = {}
    for alm in almoxarifados:
        itens_json[str(alm.id)] = [
            {'id': it.id, 'nome': it.nome, 'quantidade': it.quantidade, 'unidade': it.unidade}
            for it in alm.itens if it.ativo
        ]

    if request.method == 'POST':
        colaborador = request.form.get('colaborador', '').strip()
        alm_id = int(request.form.get('almoxarifado_id', u.almoxarifado_id or 0))
        observacao = request.form.get('observacao', '')

        if not colaborador:
            flash('Informe o nome do colaborador que vai buscar os materiais.', 'warning')
            return redirect(url_for('mestre_requisicao_nova'))

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
            return redirect(url_for('mestre_requisicao_nova'))

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
        flash(f'✅ Requisição <strong>#{req.id}</strong> enviada ao almoxarifado! Aguarde a separação.', 'success')
        return redirect(url_for('mestre_requisicoes'))

    return render_template('mestre_requisicao_nova.html',
                           almoxarifados=almoxarifados,
                           itens_json=json.dumps(itens_json))

@app.route('/mestre/requisicoes/<int:id>')
@login_required
def mestre_requisicao_detalhe(id):
    """Detalhe de uma requisição do mestre."""
    req = RequisicaoMestre.query.get_or_404(id)
    u = usuario_atual()
    # Mestre só vê as suas
    if u.perfil == 'mestre' and req.mestre_id != u.id:
        flash('Acesso negado.', 'danger')
        return redirect(url_for('mestre_requisicoes'))
    # Almoxarife só vê do seu almoxarifado
    if u.perfil == 'almoxarife' and req.almoxarifado_id != u.almoxarifado_id:
        flash('Acesso negado.', 'danger')
        return redirect(url_for('mestre_requisicoes'))
    return render_template('mestre_requisicao_detalhe.html', req=req)

@app.route('/mestre/requisicoes/<int:id>/editar', methods=['GET', 'POST'])
@login_required
def mestre_requisicao_editar(id):
    """Almoxarife ou admin edita uma requisição pendente."""
    req = RequisicaoMestre.query.get_or_404(id)
    u = usuario_atual()
    if u.perfil not in ('admin', 'almoxarife'):
        flash('Apenas almoxarife ou admin pode editar requisições.', 'danger')
        return redirect(url_for('mestre_requisicoes'))
    if req.status == 'entregue':
        flash('Não é possível editar uma requisição já entregue.', 'warning')
        return redirect(url_for('mestre_requisicao_detalhe', id=id))

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
        return redirect(url_for('mestre_requisicao_detalhe', id=id))

    return render_template('mestre_requisicao_editar.html', req=req)

@app.route('/mestre/requisicoes/<int:id>/aprovar', methods=['POST'])
@login_required
def mestre_requisicao_aprovar(id):
    """Almoxarife aprova ou recusa a requisição inteira."""
    req = RequisicaoMestre.query.get_or_404(id)
    u = usuario_atual()
    if u.perfil not in ('admin', 'almoxarife'):
        flash('Acesso negado.', 'danger')
        return redirect(url_for('mestre_requisicoes'))
    if req.status != 'pendente':
        flash('Requisição não está pendente.', 'warning')
        return redirect(url_for('mestre_requisicao_detalhe', id=id))

    decisao = request.form.get('decisao', 'aprovada')
    req.status = decisao  # 'aprovada' ou 'recusada'
    db.session.commit()

    if req.status == 'aprovada':
        flash(f'✅ Requisição #{req.id} aprovada! Separe os materiais e confirme a entrega.', 'success')
    else:
        flash(f'❌ Requisição #{req.id} recusada.', 'danger')

    return redirect(url_for('mestre_requisicao_detalhe', id=id))

@app.route('/mestre/requisicoes/<int:id>/entregar', methods=['POST'])
@login_required
def mestre_requisicao_entregar(id):
    """Almoxarife confirma entrega — baixa o estoque."""
    req = RequisicaoMestre.query.get_or_404(id)
    u = usuario_atual()
    if u.perfil not in ('admin', 'almoxarife'):
        flash('Acesso negado.', 'danger')
        return redirect(url_for('mestre_requisicoes'))
    if req.status not in ('pendente', 'aprovada'):
        flash('Requisição já foi entregue, recusada ou cancelada.', 'warning')
        return redirect(url_for('mestre_requisicao_detalhe', id=id))

    erros = []
    for ri in req.itens:
        if ri.quantidade > ri.item.quantidade:
            erros.append(f'"{ri.item.nome}": apenas {ri.item.quantidade} {ri.item.unidade} disponível')

    if erros:
        for e in erros:
            flash(f'⚠️ {e}', 'danger')
        return redirect(url_for('mestre_requisicao_detalhe', id=id))

    for ri in req.itens:
        ri.item.quantidade = round(ri.item.quantidade - ri.quantidade, 4)
        db.session.add(Movimentacao(
            tipo='saida',
            quantidade=ri.quantidade,
            responsavel=req.mestre.nome,
            observacao=f'Req. Mestre #{req.id} — Colaborador: {req.colaborador}',
            item_id=ri.item.id
        ))

    req.status = 'entregue'
    req.data_entrega = agora()
    req.entregue_por_id = u.id
    db.session.commit()
    flash(f'✅ Entrega confirmada! Estoque atualizado para {len(req.itens)} item(ns).', 'success')
    return redirect(url_for('mestre_requisicao_detalhe', id=id))

@app.route('/mestre/requisicoes/<int:id>/cancelar', methods=['POST'])
@login_required
def mestre_requisicao_cancelar(id):
    """Cancela uma requisição pendente ou aprovada."""
    req = RequisicaoMestre.query.get_or_404(id)
    u = usuario_atual()
    if u.perfil == 'mestre' and req.mestre_id != u.id:
        flash('Acesso negado.', 'danger')
        return redirect(url_for('mestre_requisicoes'))
    if req.status == 'entregue':
        flash('Não é possível cancelar uma requisição já entregue.', 'warning')
        return redirect(url_for('mestre_requisicao_detalhe', id=id))

    req.status = 'cancelada'
    db.session.commit()
    flash(f'Requisição #{req.id} cancelada.', 'warning')
    return redirect(url_for('mestre_requisicoes'))

@app.route('/api/mestre/notificacoes')
@login_required
def api_mestre_notificacoes():
    """Retorna notificações baseadas no status das requisições do mestre."""
    u = usuario_atual()
    if u.perfil != 'mestre':
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

@app.route('/api/mestre/notificacoes/marcar-lidas', methods=['POST'])
@login_required
def marcar_notificacoes_lidas():
    """Endpoint mantido para compatibilidade — controle feito no frontend."""
    return jsonify({'ok': True})

# ── ROTA ESPECIAL PARA REATIVAR TODOS OS ITENS ──────────────────────────────

@app.route('/admin/reativar-todos-itens', methods=['GET', 'POST'])
@admin_required
def reativar_todos_itens():
    if request.method == 'POST':
        try:
            # Reativar todos os itens
            itens_desativados = Item.query.filter_by(ativo=False).all()
            count = 0
            for item in itens_desativados:
                item.ativo = True
                count += 1
            
            # Também garantir que itens com ativo=None sejam ativados
            from sqlalchemy import text
            with db.engine.connect() as conn:
                result = conn.execute(text("UPDATE item SET ativo = 1 WHERE ativo IS NULL OR ativo = 0"))
                conn.commit()
            
            db.session.commit()
            
            total_ativos = Item.query.filter_by(ativo=True).count()
            flash(f'✅ Sucesso! {count} itens reativados. Total de itens ativos: {total_ativos}', 'success')
            
        except Exception as e:
            flash(f'❌ Erro ao reativar itens: {str(e)}', 'danger')
        
        return redirect(url_for('reativar_todos_itens'))
    
    # GET - mostrar página de confirmação
    itens_desativados = Item.query.filter_by(ativo=False).count()
    total_itens = Item.query.count()
    
    return f'''
    <!DOCTYPE html>
    <html>
    <head>
        <title>Reativar Todos os Itens</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">🔧 Reativar Todos os Itens</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <strong>📊 Status atual:</strong><br>
                                • Total de itens: {total_itens}<br>
                                • Itens desativados: {itens_desativados}<br>
                                • Itens ativos: {total_itens - itens_desativados}
                            </div>
                            
                            <p>Esta ação irá reativar todos os itens que estão marcados como "desativado" no sistema.</p>
                            
                            <form method="POST" onsubmit="return confirm('Tem certeza que deseja reativar todos os itens desativados?')">
                                <button type="submit" class="btn btn-success btn-lg w-100">
                                    ✅ Reativar Todos os Itens
                                </button>
                            </form>
                            
                            <div class="mt-3">
                                <a href="/" class="btn btn-secondary">← Voltar ao Sistema</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Tema laranja v2.0 -->
    </body>
    </html>
    '''

# ── BACKUP ───────────────────────────────────────────────────────────────────

def gerar_excel_backup_almoxarifado(alm):
    """Gera um Excel com apenas um almoxarifado (para envio ao almoxarife)."""
    wb = openpyxl.Workbook()
    h_fill = PatternFill('solid', fgColor='1A3A5C')
    h_font = Font(bold=True, color='FFFFFF', size=11)
    ok_fill  = PatternFill('solid', fgColor='D4EDDA')
    al_fill  = PatternFill('solid', fgColor='FFF3CD')
    cr_fill  = PatternFill('solid', fgColor='F8D7DA')
    borda = Border(left=Side(style='thin'), right=Side(style='thin'),
                   top=Side(style='thin'), bottom=Side(style='thin'))

    itens = Item.query.filter_by(almoxarifado_id=alm.id).all()
    ws = wb.active
    ws.title = alm.nome[:31]

    ws.merge_cells('A1:G1')
    ws['A1'] = f'Backup — {alm.nome}'
    ws['A1'].font = Font(bold=True, size=13, color='1A3A5C')
    ws['A1'].alignment = Alignment(horizontal='center')
    ws.merge_cells('A2:G2')
    ws['A2'] = f'Gerado em: {datetime.now().strftime("%d/%m/%Y %H:%M")}'
    ws['A2'].font = Font(italic=True, color='888888')
    ws['A2'].alignment = Alignment(horizontal='center')

    headers = ['Codigo', 'Item', 'Unidade', 'Qtd. Atual', 'Est. Minimo', 'Status', 'Ativo']
    for col, h in enumerate(headers, 1):
        c = ws.cell(row=4, column=col, value=h)
        c.font = h_font; c.fill = h_fill
        c.alignment = Alignment(horizontal='center'); c.border = borda

    for r, it in enumerate(itens, 5):
        status = 'ZERADO' if it.status == 'critico' else ('ABAIXO DO MINIMO' if it.status == 'alerta' else 'OK')
        fill = cr_fill if it.status == 'critico' else (al_fill if it.status == 'alerta' else ok_fill)
        for c, v in enumerate([it.codigo, it.nome, it.unidade, it.quantidade,
                                it.estoque_minimo, status, 'Sim' if it.ativo else 'Não'], 1):
            cell = ws.cell(row=r, column=c, value=v)
            cell.fill = fill; cell.border = borda
            cell.alignment = Alignment(horizontal='left' if c == 2 else 'center')

    for i, w in enumerate([14, 40, 10, 12, 14, 20, 8], 1):
        ws.column_dimensions[get_column_letter(i)].width = w

    buf = io.BytesIO()
    wb.save(buf)
    buf.seek(0)
    return buf

def gerar_excel_backup():
    """Gera um Excel completo com todos os almoxarifados como backup."""
    wb = openpyxl.Workbook()
    h_fill = PatternFill('solid', fgColor='1A3A5C')
    h_font = Font(bold=True, color='FFFFFF', size=11)
    ok_fill  = PatternFill('solid', fgColor='D4EDDA')
    al_fill  = PatternFill('solid', fgColor='FFF3CD')
    cr_fill  = PatternFill('solid', fgColor='F8D7DA')
    borda = Border(left=Side(style='thin'), right=Side(style='thin'),
                   top=Side(style='thin'), bottom=Side(style='thin'))

    almoxarifados = Almoxarifado.query.all()
    primeira = True

    for alm in almoxarifados:
        itens = Item.query.filter_by(almoxarifado_id=alm.id).all()
        ws = wb.active if primeira else wb.create_sheet()
        primeira = False
        ws.title = alm.nome[:31]  # Excel limita 31 chars no nome da aba

        # Título
        ws.merge_cells('A1:G1')
        ws['A1'] = f'Backup — {alm.nome}'
        ws['A1'].font = Font(bold=True, size=13, color='1A3A5C')
        ws['A1'].alignment = Alignment(horizontal='center')
        ws.merge_cells('A2:G2')
        ws['A2'] = f'Gerado em: {datetime.now().strftime("%d/%m/%Y %H:%M")}'
        ws['A2'].font = Font(italic=True, color='888888')
        ws['A2'].alignment = Alignment(horizontal='center')

        # Cabeçalho
        headers = ['Codigo', 'Item', 'Unidade', 'Qtd. Atual', 'Est. Minimo', 'Status', 'Ativo']
        for col, h in enumerate(headers, 1):
            c = ws.cell(row=4, column=col, value=h)
            c.font = h_font; c.fill = h_fill
            c.alignment = Alignment(horizontal='center'); c.border = borda

        for r, it in enumerate(itens, 5):
            status = 'ZERADO' if it.status == 'critico' else ('ABAIXO DO MINIMO' if it.status == 'alerta' else 'OK')
            fill = cr_fill if it.status == 'critico' else (al_fill if it.status == 'alerta' else ok_fill)
            for c, v in enumerate([it.codigo, it.nome, it.unidade, it.quantidade,
                                    it.estoque_minimo, status, 'Sim' if it.ativo else 'Não'], 1):
                cell = ws.cell(row=r, column=c, value=v)
                cell.fill = fill; cell.border = borda
                cell.alignment = Alignment(horizontal='left' if c == 2 else 'center')

        for i, w in enumerate([14, 40, 10, 12, 14, 20, 8], 1):
            ws.column_dimensions[get_column_letter(i)].width = w

    buf = io.BytesIO()
    wb.save(buf)
    buf.seek(0)
    return buf

def enviar_backup_email(buf):
    """Envia o backup completo por email (para admin ou destinatário fixo)."""
    import smtplib
    from email.mime.multipart import MIMEMultipart
    from email.mime.base import MIMEBase
    from email.mime.text import MIMEText
    from email import encoders

    remetente = os.environ.get('BACKUP_EMAIL_FROM')
    senha     = os.environ.get('BACKUP_EMAIL_PASS')
    destinatario = os.environ.get('BACKUP_EMAIL_TO', 'rickgouveia17@gmail.com')

    if not remetente or not senha:
        print('BACKUP: variáveis BACKUP_EMAIL_FROM e BACKUP_EMAIL_PASS não configuradas.')
        return False

    msg = MIMEMultipart()
    msg['From'] = remetente
    msg['To'] = destinatario
    msg['Subject'] = f'Backup Estoque Obra Patamares — {date.today().strftime("%d/%m/%Y")}'

    corpo = f"""
    Backup automático do sistema de estoque.
    Data: {datetime.now().strftime("%d/%m/%Y %H:%M")}
    
    Este arquivo contém todos os dados de estoque de todos os almoxarifados.
    Guarde em local seguro.
    """
    msg.attach(MIMEText(corpo, 'plain'))

    part = MIMEBase('application', 'octet-stream')
    part.set_payload(buf.read())
    encoders.encode_base64(part)
    part.add_header('Content-Disposition',
                    f'attachment; filename="backup_estoque_{date.today()}.xlsx"')
    msg.attach(part)

    try:
        with smtplib.SMTP_SSL('smtp.gmail.com', 465) as smtp:
            smtp.login(remetente, senha)
            smtp.send_message(msg)
        print(f'BACKUP: enviado para {destinatario}')
        return True
    except Exception as e:
        print(f'BACKUP: erro ao enviar email — {e}')
        return False


def _smtp_connect():
    """Retorna conexão SMTP autenticada ou None se não configurado."""
    import smtplib
    remetente = os.environ.get('BACKUP_EMAIL_FROM')
    senha     = os.environ.get('BACKUP_EMAIL_PASS')
    if not remetente or not senha:
        return None, None, None
    smtp = smtplib.SMTP_SSL('smtp.gmail.com', 465)
    smtp.login(remetente, senha)
    return smtp, remetente, senha


def enviar_backup_por_almoxarifado():
    """Envia backup individual para cada almoxarife com email cadastrado,
    e o backup completo para admins com email cadastrado e para o destinatário fixo.
    Usa Resend API para envio de emails."""
    import resend
    import base64

    resend_api_key = os.environ.get('RESEND_API_KEY')
    destinatario_fixo = os.environ.get('BACKUP_EMAIL_TO', 'rickgouveia157@gmail.com')

    if not resend_api_key:
        print('BACKUP: variável RESEND_API_KEY não configurada.')
        return False, 'Variável RESEND_API_KEY não configurada no Railway.'

    resend.api_key = resend_api_key
    # Usa o email verificado do Resend (onboarding@resend.dev é o padrão de teste)
    remetente = "onboarding@resend.dev"
    
    hoje = date.today().strftime('%d/%m/%Y')
    enviados = 0
    erros = 0

    try:
        # 1. Envia backup individual para cada almoxarife com email
        almoxarifados = Almoxarifado.query.all()
        for alm in almoxarifados:
            destinatarios_alm = [
                u.email for u in alm.usuarios
                if u.perfil == 'almoxarife' and u.ativo and u.email
            ]
            if not destinatarios_alm:
                continue

            buf_alm = gerar_excel_backup_almoxarifado(alm)
            nome_arquivo = f"backup_{alm.nome.replace(' ', '_')}_{date.today()}.xlsx"
            
            # Converte o buffer para base64
            buf_alm.seek(0)
            arquivo_base64 = base64.b64encode(buf_alm.read()).decode('utf-8')

            corpo = f"""Backup automático do almoxarifado: {alm.nome}
Data: {datetime.now().strftime("%d/%m/%Y %H:%M")}

Este arquivo contém o estoque atual do seu almoxarifado.
Guarde em local seguro."""

            try:
                resend.Emails.send({
                    "from": f"Sistema Estoque <{remetente}>",
                    "to": destinatarios_alm,
                    "subject": f"Backup {alm.nome} — {hoje}",
                    "text": corpo,
                    "attachments": [{
                        "filename": nome_arquivo,
                        "content": arquivo_base64
                    }]
                })
                print(f'BACKUP: enviado para almoxarife(s) de "{alm.nome}": {destinatarios_alm}')
                enviados += 1
            except Exception as e:
                print(f'BACKUP: erro ao enviar para "{alm.nome}" — {e}')
                erros += 1

        # 2. Envia backup completo para admins com email
        admins_emails = [
            u.email for u in Usuario.query.filter_by(perfil='admin', ativo=True).all()
            if u.email
        ]
        # Inclui o destinatário fixo se não estiver na lista
        todos_admins = list(set(admins_emails + [destinatario_fixo]))

        buf_completo = gerar_excel_backup()
        buf_completo.seek(0)
        arquivo_completo_base64 = base64.b64encode(buf_completo.read()).decode('utf-8')

        corpo_admin = f"""Backup automático completo do sistema de estoque.
Data: {datetime.now().strftime("%d/%m/%Y %H:%M")}

Este arquivo contém todos os almoxarifados.
Guarde em local seguro."""

        try:
            resend.Emails.send({
                "from": f"Sistema Estoque <{remetente}>",
                "to": todos_admins,
                "subject": f"Backup Completo Estoque — {hoje}",
                "text": corpo_admin,
                "attachments": [{
                    "filename": f"backup_completo_{date.today()}.xlsx",
                    "content": arquivo_completo_base64
                }]
            })
            print(f'BACKUP: backup completo enviado para admins: {todos_admins}')
            enviados += 1
        except Exception as e:
            print(f'BACKUP: erro ao enviar backup completo — {e}')
            erros += 1

    except Exception as e:
        print(f'BACKUP: erro ao enviar emails via Resend — {e}')
        return False, str(e)

    return erros == 0, None

@app.route('/admin/debug-env')
@admin_required
def debug_env():
    """Rota temporária de diagnóstico — remove após resolver o problema."""
    from_val = os.environ.get('BACKUP_EMAIL_FROM', 'NÃO DEFINIDO')
    pass_val = os.environ.get('BACKUP_EMAIL_PASS', 'NÃO DEFINIDO')
    to_val   = os.environ.get('BACKUP_EMAIL_TO',   'NÃO DEFINIDO')
    # Mostra apenas primeiros/últimos chars para não expor a senha
    pass_preview = (pass_val[:3] + '***' + pass_val[-3:]) if len(pass_val) > 6 else pass_val
    return f"""
    <pre>
    BACKUP_EMAIL_FROM = {from_val}
    BACKUP_EMAIL_PASS = {pass_preview}
    BACKUP_EMAIL_TO   = {to_val}
    Todas as env vars: {[k for k in os.environ.keys() if 'BACKUP' in k]}
    </pre>
    """

@app.route('/admin/backup', methods=['GET', 'POST'])
@admin_required
def backup_manual():
    """Admin faz backup manual — baixa Excel ou envia por email."""
    if request.method == 'POST':
        acao = request.form.get('acao', 'download')

        if acao == 'email':
            try:
                ok, erro_msg = enviar_backup_por_almoxarifado()
                if ok:
                    flash('✅ Backup enviado por email com sucesso! Admins receberam o backup completo e cada almoxarife recebeu o seu.', 'success')
                else:
                    detalhe = f' Detalhe: {erro_msg}' if erro_msg else ' Verifique as configurações BACKUP_EMAIL_FROM e BACKUP_EMAIL_PASS no Railway.'
                    flash(f'❌ Erro ao enviar email.{detalhe}', 'danger')
            except Exception as e:
                flash(f'❌ Erro inesperado ao enviar email: {str(e)}', 'danger')
            return redirect(url_for('backup_manual'))

        # Download direto
        try:
            buf = gerar_excel_backup()
            nome = f"backup_estoque_{date.today()}.xlsx"
            return send_file(buf, as_attachment=True, download_name=nome,
                             mimetype='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        except Exception as e:
            flash(f'❌ Erro ao gerar backup: {str(e)}', 'danger')
            return redirect(url_for('backup_manual'))

    # GET — mostra a página
    email_configurado = bool(os.environ.get('BACKUP_EMAIL_FROM', '').strip())
    return render_template('backup.html', email_configurado=email_configurado)

@app.route('/api/backup-automatico', methods=['GET'])
def api_backup_automatico():
    """API pública para backup automático via cron job externo.
    Acesse: https://seu-site.railway.app/api/backup-automatico
    
    Executa o backup em background e retorna imediatamente.
    """
    import threading
    
    def executar_backup_background():
        """Executa o backup em uma thread separada."""
        with app.app_context():
            try:
                ok, erro_msg = enviar_backup_por_almoxarifado()
                if ok:
                    print(f'✅ BACKUP API: Backup enviado com sucesso às {datetime.now().strftime("%d/%m/%Y %H:%M:%S")}')
                else:
                    print(f'❌ BACKUP API: Erro ao enviar backup: {erro_msg}')
            except Exception as e:
                print(f'❌ BACKUP API: Erro inesperado: {str(e)}')
    
    # Inicia o backup em background
    thread = threading.Thread(target=executar_backup_background)
    thread.daemon = True
    thread.start()
    
    # Retorna imediatamente
    return jsonify({
        'success': True,
        'message': 'Backup iniciado! Os emails serão enviados em alguns minutos.',
        'timestamp': datetime.now().strftime('%d/%m/%Y %H:%M:%S')
    }), 200

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

def inicializar_banco():
    """Roda migrações, cria tabelas e seed — executado uma única vez."""
    try:
        run_migrations()
        db.create_all()
        seed_data()
    except Exception as e:
        print(f'Inicialização do banco: {e}')

# Inicialização única — funciona tanto para gunicorn quanto para python app.py
with app.app_context():
    inicializar_banco()

# ── BACKUP AUTOMÁTICO DIÁRIO ─────────────────────────────────────────────────
def job_backup_diario():
    """Executa o backup automático todo dia às 20h (horário de Brasília)."""
    agora_str = datetime.now().strftime("%d/%m/%Y %H:%M")
    print(f'BACKUP AUTOMÁTICO: iniciando às {agora_str}')

    # Diagnóstico das variáveis de ambiente
    remetente = os.environ.get('BACKUP_EMAIL_FROM', '')
    senha = os.environ.get('BACKUP_EMAIL_PASS', '')
    destino_fixo = os.environ.get('BACKUP_EMAIL_TO', 'rickgouveia17@gmail.com')
    print(f'BACKUP AUTOMÁTICO: remetente configurado = {"SIM (" + remetente + ")" if remetente else "NÃO — BACKUP_EMAIL_FROM não definido"}')
    print(f'BACKUP AUTOMÁTICO: senha configurada = {"SIM" if senha else "NÃO — BACKUP_EMAIL_PASS não definido"}')
    print(f'BACKUP AUTOMÁTICO: destino fixo = {destino_fixo}')

    with app.app_context():
        try:
            # Log dos emails cadastrados
            admins = [u for u in Usuario.query.filter_by(perfil='admin', ativo=True).all() if u.email]
            almoxarifes = [u for u in Usuario.query.filter_by(perfil='almoxarife', ativo=True).all() if u.email]
            print(f'BACKUP AUTOMÁTICO: admins com email = {[u.email for u in admins]}')
            print(f'BACKUP AUTOMÁTICO: almoxarifes com email = {[u.email for u in almoxarifes]}')

            ok, erro_msg = enviar_backup_por_almoxarifado()
            if ok:
                print('BACKUP AUTOMÁTICO: ✅ enviado com sucesso!')
            else:
                print(f'BACKUP AUTOMÁTICO: ❌ falha no envio. Detalhe: {erro_msg}')
        except Exception as e:
            print(f'BACKUP AUTOMÁTICO: erro — {e}')

try:
    from apscheduler.schedulers.background import BackgroundScheduler
    from apscheduler.triggers.cron import CronTrigger

    # America/Sao_Paulo = UTC-3 (Brasília). Usar este em vez de America/Bahia
    # para garantir compatibilidade com o horário de verão.
    scheduler = BackgroundScheduler(timezone='America/Sao_Paulo')
    scheduler.add_job(
        job_backup_diario,
        CronTrigger(hour=20, minute=0, timezone='America/Sao_Paulo'),
        id='backup_diario',
        replace_existing=True
    )
    scheduler.start()
    print('BACKUP AUTOMÁTICO: ✅ agendado para todo dia às 20:00 (horário de Brasília/São Paulo)')
except Exception as e:
    print(f'BACKUP AUTOMÁTICO: ❌ erro ao iniciar agendador — {e}')

if __name__ == '__main__':
    port = int(os.environ.get('PORT', 5000))
    app.run(debug=False, host='0.0.0.0', port=port)
