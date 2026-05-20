import os
import re
import secrets
import logging
from datetime import datetime, date, timezone, timedelta
from functools import wraps
from flask import flash, redirect, request, url_for, session
from markupsafe import Markup, escape

from . import db
from .models import Usuario, Almoxarifado, Item, Colaborador, RequisicaoMestre, AcessoExtra

logger = logging.getLogger(__name__)
TZ_BRASILIA = timezone(timedelta(hours=-3))


def agora():
    return datetime.now(TZ_BRASILIA).replace(tzinfo=None)


def flash_html(message, category='info'):
    flash(Markup(message), category)

_login_attempts = {}
_LOGIN_ATTEMPT_LIMIT = 10
_LOGIN_LOCKOUT_SECONDS = 300


def registrar_tentativa_login(ip):
    now = datetime.now().timestamp()
    _login_attempts.setdefault(ip, []).append(now)
    _login_attempts[ip] = [t for t in _login_attempts[ip] if now - t <= _LOGIN_LOCKOUT_SECONDS]


def valida_limite_login(ip):
    now = datetime.now().timestamp()
    tentativas = [t for t in _login_attempts.get(ip, []) if now - t <= _LOGIN_LOCKOUT_SECONDS]
    return len(tentativas) < _LOGIN_ATTEMPT_LIMIT


def usuario_tem_acesso_almoxarifado(u, alm_id):
    return u.perfil == 'admin' or (alm_id in u.almoxarifados_permitidos())


def usuario_tem_acesso_item(u, it):
    return u.perfil == 'admin' or (it and it.almoxarifado_id in u.almoxarifados_permitidos())


def login_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if 'usuario_id' not in session:
            flash('Faça login para continuar.', 'warning')
            return redirect(url_for('login'))
        u = db.session.get(Usuario, session['usuario_id'])
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
        u = db.session.get(Usuario, session['usuario_id'])
        if not u or u.perfil != 'admin':
            flash('Acesso restrito ao administrador.', 'danger')
            return redirect(url_for('index'))
        return f(*args, **kwargs)
    return decorated


def almoxarife_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if 'usuario_id' not in session:
            return redirect(url_for('login'))
        u = db.session.get(Usuario, session['usuario_id'])
        if not u or u.perfil not in ('admin', 'almoxarife'):
            flash('Acesso restrito ao almoxarife.', 'danger')
            return redirect(url_for('index'))
        return f(*args, **kwargs)
    return decorated


def usuario_atual():
    if 'usuario_id' in session:
        return db.session.get(Usuario, session['usuario_id'])
    return None


def extrair_colaborador(mov):
    obs = mov.observacao or ''
    m = re.search(r'liberado\s+[Pp][/\s]+(.+)', obs, re.IGNORECASE)
    if m:
        return m.group(1).strip()
    m = re.search(r'[Cc]olaborador[:\s]+([^|]+)', obs)
    if m:
        return m.group(1).strip()
    return mov.responsavel or 'Sem responsável'


def configure_app(app):
    _secret = os.environ.get('SECRET_KEY')
    if not _secret:
        if os.environ.get('DATABASE_URL') or os.environ.get('URI_DO_BANCO_DE_DADOS'):
            raise RuntimeError('SECRET_KEY não definida em produção. Configure SECRET_KEY no Railway.')
        _secret = 'dev-key-apenas-local'
    app.secret_key = _secret

    _db_url = (
        os.environ.get('DATABASE_URL') or
        os.environ.get('URI_DO_BANCO_DE_DADOS') or
        f'sqlite:///{os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "instance", "estoque.db")}'
    )
    if _db_url.startswith('postgres://'):
        _db_url = _db_url.replace('postgres://', 'postgresql://', 1)

    app.config['SQLALCHEMY_DATABASE_URI'] = _db_url
    app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
    app.config['WTF_CSRF_TIME_LIMIT'] = 7200
    app.config['PREFERRED_URL_SCHEME'] = 'https'
    app.config['SESSION_COOKIE_HTTPONLY'] = True
    app.config['SESSION_COOKIE_SAMESITE'] = 'Lax'
    app.config['SESSION_COOKIE_SECURE'] = bool(os.environ.get('DATABASE_URL') or os.environ.get('URI_DO_BANCO_DE_DADOS'))
    app.config['JSONIFY_PRETTYPRINT_REGULAR'] = False


def enforce_https_in_production():
    if request.scheme != 'https' and (os.environ.get('DATABASE_URL') or os.environ.get('URI_DO_BANCO_DE_DADOS')):
        return redirect(request.url.replace('http://', 'https://', 1))


def set_security_headers(response):
    response.headers['X-Content-Type-Options'] = 'nosniff'
    response.headers['X-Frame-Options'] = 'SAMEORIGIN'
    response.headers['Referrer-Policy'] = 'strict-origin-when-cross-origin'
    response.headers['X-XSS-Protection'] = '1; mode=block'
    response.headers['Strict-Transport-Security'] = 'max-age=63072000; includeSubDomains; preload'
    response.headers['Content-Security-Policy'] = (
        "default-src 'self'; "
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
        "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
        "font-src 'self' https://cdn.jsdelivr.net data:; "
        "img-src 'self' data:; "
        "connect-src 'self';"
    )
    response.headers['Permissions-Policy'] = 'geolocation=(), microphone=()'
    return response


def fmt_qtd(value):
    try:
        v = round(float(value), 4)
        if v == int(v):
            return str(int(v))
        return f"{v:.4f}".rstrip('0').rstrip('.')
    except (TypeError, ValueError):
        return value


def inject_sidebar():
    u = usuario_atual()
    if not u:
        return dict(sidebar_alms=[], usuario_atual=None)
    if u.perfil == 'admin':
        alms = Almoxarifado.query.all()
    elif u.perfil in ('mestre', 'tecnico_seguranca'):
        alms = [u.almoxarifado] if u.almoxarifado_id else []
    else:
        ids = u.almoxarifados_permitidos()
        alms = Almoxarifado.query.filter(Almoxarifado.id.in_(ids)).all() if ids else []
    return dict(sidebar_alms=alms, usuario_atual=u)


def run_migrations():
    from sqlalchemy import text
    from sqlalchemy.exc import SQLAlchemyError

    is_pg = 'postgresql' in str(db.engine.url)

    def safe_exec(conn, sql):
        try:
            if is_pg:
                conn.execute(text('SAVEPOINT mig'))
            conn.execute(text(sql))
            if is_pg:
                conn.execute(text('RELEASE SAVEPOINT mig'))
            conn.commit()
        except Exception:
            if is_pg:
                try:
                    conn.execute(text('ROLLBACK TO SAVEPOINT mig'))
                    conn.execute(text('RELEASE SAVEPOINT mig'))
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

        safe_exec(conn, "ALTER TABLE item ADD COLUMN status_compra VARCHAR(30) DEFAULT 'pendente'")
        safe_exec(conn, "ALTER TABLE item ADD COLUMN fixado BOOLEAN DEFAULT 0")
        safe_exec(conn, "ALTER TABLE item ADD COLUMN ativo BOOLEAN DEFAULT 1")
        safe_exec(conn, "ALTER TABLE item ADD COLUMN categoria VARCHAR(30) DEFAULT 'geral'")
        safe_exec(conn, "ALTER TABLE item ADD COLUMN ca VARCHAR(20)")
        if is_pg:
            safe_exec(conn, "ALTER TABLE item ALTER COLUMN nome TYPE VARCHAR(300)")

        safe_exec(conn, "ALTER TABLE usuario ADD COLUMN email VARCHAR(120)")
        safe_exec(conn, "ALTER TABLE usuario ADD COLUMN senha_hash_new VARCHAR(256)")

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
        safe_exec(conn, "ALTER TABLE requisicao_mestre ADD COLUMN notificado BOOLEAN DEFAULT FALSE")
        safe_exec(conn, "ALTER TABLE requisicao_mestre_item ADD COLUMN status_item VARCHAR(20) DEFAULT 'pendente'")
        safe_exec(conn, "ALTER TABLE requisicao_mestre_item ADD COLUMN motivo_recusa VARCHAR(200)")
        safe_exec(conn, "UPDATE requisicao_mestre_item SET status_item = 'pendente' WHERE status_item IS NULL")
        safe_exec(conn, "UPDATE requisicao_mestre SET notificado = FALSE WHERE notificado IS NULL")


def seed_data():
    if Almoxarifado.query.count() == 0:
        db.session.add_all([
            Almoxarifado(nome='Almoxarifado do Acampamento', descricao='Materiais de uso geral do acampamento'),
            Almoxarifado(nome='Almoxarifado de Infraestrutura', descricao='Materiais de construcao e manutencao'),
            Almoxarifado(nome='Almoxarifado de Forma', descricao='Formas, escoramentos e materiais de forma'),
        ])
        db.session.commit()
    if Usuario.query.count() == 0:
        senha_inicial = secrets.token_urlsafe(12)
        admin = Usuario(nome='Administrador', login='admin', perfil='admin')
        admin.set_senha(senha_inicial)
        db.session.add(admin)
        db.session.commit()
        logger.info('=' * 60)
        logger.warning('⚠️  PRIMEIRO ACESSO — GUARDE ESTAS CREDENCIAIS:')
        logger.info(f'   Login: admin')
        logger.info(f'   Senha: {senha_inicial}')
        logger.warning('   Altere a senha imediatamente após o primeiro login!')
        logger.info('=' * 60)


def classificar_categorias_itens():
    palavras_epi = [
        'bota', 'capacete', 'carneira', 'cinto de segurança', 'capa de chuva',
        'calça brim', 'camisa brim', 'macacão', 'mascara', 'máscara',
        'luva vaqueta', 'luva flextactil', 'perneira', 'protetor auricular',
        'óculos de proteção', 'óculos de segurança', 'óculos de sobrepor',
        'talabarte', 'trava-quedas', 'mosquetão oval', 'cinto paraquedista',
        'uniforme', 'colete refletivo'
    ]
    palavras_maq = [
        'broca diamantada', 'disco diamantado', 'disco de desbaste',
        'maçarico', 'perfuratriz'
    ]
    try:
        itens = Item.query.filter_by(categoria='geral').all()
        atualizados = 0
        for it in itens:
            nome_lower = it.nome.lower()
            if any(p in nome_lower for p in palavras_epi):
                it.categoria = 'epi'
                atualizados += 1
            elif any(p in nome_lower for p in palavras_maq):
                it.categoria = 'maquinario'
                atualizados += 1
        if atualizados:
            db.session.commit()
            logger.info(f'Categorias: {atualizados} itens classificados automaticamente.')
    except Exception as e:
        logger.info(f'Categorias: erro ao classificar — {e}')


def inicializar_banco():
    try:
        run_migrations()
        db.create_all()
        seed_data()
        classificar_categorias_itens()
    except Exception as e:
        logger.error(f'Inicialização do banco: {e}')
