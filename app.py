"""Logi-Prime — Factory do app Flask modularizado."""
from flask import Flask, request, redirect, session
from flask_wtf.csrf import CSRFError
from datetime import timedelta
import os
import logging

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s'
)
logger = logging.getLogger(__name__)

logger.info('=' * 60)
logger.info('DIAGNOSTICO DE VARIAVEIS DE AMBIENTE:')
logger.info(f'  DATABASE_URL = {"SIM" if os.environ.get("DATABASE_URL") else "NAO DEFINIDO"}')
logger.info('=' * 60)

from extensions import db, csrf
from models import Almoxarifado, Item, Ferramenta, ItemEPI
from core import usuario_atual
from db_init import inicializar_banco

# Blueprints
from routes.auth import auth_bp
from routes.main import main_bp
from routes.relatorios import relatorios_bp
from routes.ferramentas import ferramentas_bp
from routes.epis import epis_bp
from routes.colaboradores import colaboradores_bp
from routes.usuarios import usuarios_bp
from routes.requisicoes import requisicoes_bp
from routes.api import api_bp
from routes.admin import admin_bp
from routes.catalogo import catalogo_bp
from routes.kits import kits_bp
from routes.epi_modulo import epi_modulo_bp


def configure_app(app):
    _secret = os.environ.get('SECRET_KEY')
    if not _secret:
        if os.environ.get('DATABASE_URL') or os.environ.get('URI_DO_BANCO_DE_DADOS'):
            raise RuntimeError('SECRET_KEY nao definida em producao. Configure SECRET_KEY no Railway.')
        import secrets as _s
        _secret = _s.token_hex(32)
    app.secret_key = _secret

    _db_url = (
        os.environ.get('DATABASE_URL') or
        os.environ.get('URI_DO_BANCO_DE_DADOS') or
        f'sqlite:///{os.path.join(os.path.dirname(os.path.abspath(__file__)), "instance", "estoque.db")}'
    )
    # Compatibilidade postgres:// → postgresql://
    if _db_url.startswith('postgres://'):
        _db_url = _db_url.replace('postgres://', 'postgresql://', 1)
    # Compatibilidade mysql:// → mysql+pymysql://
    if _db_url.startswith('mysql://'):
        _db_url = _db_url.replace('mysql://', 'mysql+pymysql://', 1)

    app.config['SQLALCHEMY_DATABASE_URI'] = _db_url
    # Configurações extras para MySQL/MariaDB
    if 'mysql' in _db_url:
        app.config['SQLALCHEMY_ENGINE_OPTIONS'] = {
            'pool_recycle': 280,
            'pool_pre_ping': True,
            'connect_args': {'charset': 'utf8mb4'},
        }
    app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
    app.config['WTF_CSRF_TIME_LIMIT'] = 7200
    app.config['PREFERRED_URL_SCHEME'] = 'https'
    app.config['SESSION_COOKIE_HTTPONLY'] = True
    app.config['SESSION_COOKIE_SAMESITE'] = 'Lax'
    app.config['SESSION_COOKIE_SECURE'] = bool(
        os.environ.get('DATABASE_URL') or os.environ.get('URI_DO_BANCO_DE_DADOS')
    )
    app.config['PERMANENT_SESSION_LIFETIME'] = timedelta(minutes=60)
    app.config['JSONIFY_PRETTYPRINT_REGULAR'] = False


def create_app():
    app = Flask(__name__, static_folder='static', static_url_path='/static')
    configure_app(app)
    db.init_app(app)
    csrf.init_app(app)

    # Registrar todos os blueprints sem prefixo — mantém url_for() compativel
    for bp in [auth_bp, main_bp, relatorios_bp, ferramentas_bp, epis_bp,
               colaboradores_bp, usuarios_bp, requisicoes_bp, api_bp, admin_bp, catalogo_bp,
               kits_bp, epi_modulo_bp]:
        app.register_blueprint(bp)

    # Filtro Jinja2 para quantidades
    @app.template_filter('fmt_qtd')
    def fmt_qtd(value):
        try:
            v = round(float(value), 4)
            if v == int(v):
                return str(int(v))
            return f"{v:.4f}".rstrip('0').rstrip('.')
        except (TypeError, ValueError):
            return value

    # Filtro para valor unitário: R$ 12,50/kg | R$ 8,00/un | R$ 3,20/L
    @app.template_filter('fmt_valor_unit')
    def fmt_valor_unit(value, unidade='un'):
        try:
            v = float(value)
            unidade = (unidade or 'un').strip().lower()
            # Mapeia variações para rótulo amigável
            label_map = {
                'kg': 'kg', 'kilo': 'kg', 'quilograma': 'kg',
                'l': 'L', 'lt': 'L', 'litro': 'L', 'litros': 'L',
                'm': 'm', 'metro': 'm', 'metros': 'm',
                'm2': 'm²', 'm3': 'm³',
                'un': 'un', 'und': 'un', 'unid': 'un', 'unidade': 'un',
                'pct': 'pct', 'par': 'par', 'cx': 'cx', 'sc': 'sc',
            }
            label = label_map.get(unidade, unidade)
            return f"R$ {v:,.2f}/{label}".replace(',', 'X').replace('.', ',').replace('X', '.')
        except (TypeError, ValueError):
            return '—'

    # Filtro para valor total: quantidade × valor_unitario
    @app.template_filter('fmt_valor_total')
    def fmt_valor_total(quantidade, valor_unitario):
        try:
            total = float(quantidade) * float(valor_unitario)
            return f"R$ {total:,.2f}".replace(',', 'X').replace('.', ',').replace('X', '.')
        except (TypeError, ValueError):
            return '—'

    # Context processor — sidebar e usuario
    @app.context_processor
    def inject_sidebar():
        u = usuario_atual()
        if not u:
            return dict(sidebar_alms=[], usuario_atual=None, sidebar_contadores={})
        if u.perfil == 'admin':
            alms = Almoxarifado.query.all()
        elif u.perfil == 'analista':
            if u.almoxarifado_id:
                alm_ref = db.session.get(Almoxarifado, u.almoxarifado_id)
                alms = [alm_ref] if alm_ref else []
            else:
                alms = []
        elif u.perfil in ('mestre', 'tecnico_seguranca'):
            if u.perfil == 'tecnico_seguranca':
                ids = u.almoxarifados_permitidos()
                alms = Almoxarifado.query.filter(Almoxarifado.id.in_(ids)).all() if ids else (
                    [u.almoxarifado] if u.almoxarifado_id else []
                )
            else:
                alms = [u.almoxarifado] if u.almoxarifado_id else []
        else:
            ids = u.almoxarifados_permitidos()
            alms = Almoxarifado.query.filter(Almoxarifado.id.in_(ids)).all() if ids else []

        alms = sorted(alms, key=lambda a: (a.cidade or 'zzz', a.obra or 'zzz', a.nome))

        # ── Contadores em UMA única query por tabela ──────────────────────
        sidebar_contadores = {}
        if alms:
            alm_ids = [a.id for a in alms]

            from sqlalchemy import func, case
            # Ferramentas ativas
            ferr_counts = dict(db.session.query(
                Ferramenta.almoxarifado_id,
                func.count(Ferramenta.id)
            ).filter(
                Ferramenta.almoxarifado_id.in_(alm_ids),
                Ferramenta.ativo == True
            ).group_by(Ferramenta.almoxarifado_id).all())

            # EPIs ativos
            epi_counts = dict(db.session.query(
                ItemEPI.almoxarifado_id,
                func.count(ItemEPI.id)
            ).filter(
                ItemEPI.almoxarifado_id.in_(alm_ids),
                ItemEPI.ativo == True
            ).group_by(ItemEPI.almoxarifado_id).all())

            # Itens: total e OK (acima do mínimo) — tudo em 1 query
            itens_stats = db.session.query(
                Item.almoxarifado_id,
                func.count(Item.id).label('total'),
                func.sum(
                    case((Item.quantidade > Item.estoque_minimo, 1), else_=0)
                ).label('ok')
            ).filter(
                Item.almoxarifado_id.in_(alm_ids),
                Item.ativo == True
            ).group_by(Item.almoxarifado_id).all()

            itens_total = {r.almoxarifado_id: r.total for r in itens_stats}
            itens_ok    = {r.almoxarifado_id: (r.ok or 0) for r in itens_stats}

            for alm in alms:
                n_itens = itens_total.get(alm.id, 0)
                n_ok    = itens_ok.get(alm.id, 0)
                pct     = round(n_ok / n_itens * 100) if n_itens > 0 else 100
                sidebar_contadores[alm.id] = {
                    'ferr': ferr_counts.get(alm.id, 0),
                    'epi':  epi_counts.get(alm.id, 0),
                    'itens': n_itens,
                    'pct': pct,
                }

        return dict(sidebar_alms=alms, usuario_atual=u, sidebar_contadores=sidebar_contadores)

    @app.before_request
    def enforce_https_in_production():
        if app.config['SESSION_COOKIE_SECURE']:
            proto = request.headers.get('X-Forwarded-Proto')
            if proto and proto != 'https':
                return redirect(request.url.replace('http://', 'https://', 1), code=301)
        if 'usuario_id' in session:
            session.modified = True

    @app.after_request
    def set_security_headers(response):
        response.headers['X-Content-Type-Options'] = 'nosniff'
        response.headers['X-Frame-Options'] = 'SAMEORIGIN'
        response.headers['X-XSS-Protection'] = '1; mode=block'
        response.headers['Referrer-Policy'] = 'strict-origin-when-cross-origin'
        response.headers['Strict-Transport-Security'] = 'max-age=63072000; includeSubDomains; preload'
        response.headers['Permissions-Policy'] = (
            'geolocation=(), microphone=(), camera=(), payment=(), usb=()'
        )
        response.headers['Content-Security-Policy'] = (
            "default-src 'self'; "
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
            "font-src 'self' https://cdn.jsdelivr.net data:; "
            "img-src 'self' data:; "
            "connect-src 'self';"
        )
        if request.path == '/static/css/app.css':
            response.headers['Cache-Control'] = 'no-cache, no-store, must-revalidate'
            response.headers['Pragma'] = 'no-cache'
            response.headers['Expires'] = '0'
        return response

    @app.errorhandler(CSRFError)
    def handle_csrf_error(e):
        from flask import flash, url_for
        flash('Sessao expirada ou requisicao invalida. Tente novamente.', 'warning')
        return redirect(request.referrer or url_for('main_bp.index'))

    return app


app = create_app()

_banco_inicializado = False


@app.before_request
def init_on_first_request():
    # Healthcheck responde imediatamente sem esperar inicializacao do banco
    if request.path == '/healthz':
        return
    global _banco_inicializado
    if not _banco_inicializado:
        _banco_inicializado = True
        try:
            inicializar_banco()
        except Exception as e:
            logger.error(f'Erro na inicializacao: {e}')


if __name__ == '__main__':
    port = int(os.environ.get('PORT', 5000))
    app.run(debug=False, host='0.0.0.0', port=port)
