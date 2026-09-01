"""Funcoes compartilhadas: decorators, helpers, rate limiting."""
from flask import session, redirect, url_for, flash
from functools import wraps
from markupsafe import Markup
from datetime import datetime
from extensions import db
from models import Usuario

_login_attempts: dict = {}
_MAX_ATTEMPTS = 10
_LOCKOUT_SECONDS = 300

def _check_rate_limit(ip: str) -> bool:
    now = datetime.now().timestamp()
    tentativas = [t for t in _login_attempts.get(ip, []) if now - t < _LOCKOUT_SECONDS]
    _login_attempts[ip] = tentativas
    return len(tentativas) >= _MAX_ATTEMPTS

def _register_attempt(ip: str):
    _login_attempts.setdefault(ip, []).append(datetime.now().timestamp())

def _clear_attempts(ip: str):
    _login_attempts.pop(ip, None)

_api_calls: dict = {}
_API_MAX = 120
_API_WINDOW = 60

def _check_api_rate(ip: str) -> bool:
    now = datetime.now().timestamp()
    calls = [t for t in _api_calls.get(ip, []) if now - t < _API_WINDOW]
    _api_calls[ip] = calls
    if len(calls) >= _API_MAX:
        return True
    _api_calls[ip].append(now)
    return False

def flash_html(message, category='info'):
    flash(Markup(message), category)

def usuario_tem_acesso_almoxarifado(u, alm_id):
    return u.perfil == 'admin' or (alm_id in u.almoxarifados_permitidos())

def usuario_tem_acesso_item(u, it):
    return u.perfil == 'admin' or (it and it.almoxarifado_id in u.almoxarifados_permitidos())

def ggo_cidade(u):
    """Retorna a cidade do GGO (armazenada no campo escopo do usuario)."""
    if u and u.perfil == 'ggo':
        return (u.escopo or '').strip().lower() or None
    return None

def almoxarifados_do_ggo(u):
    """Retorna lista de Almoxarifado da cidade do GGO."""
    from models import Almoxarifado
    cidade = ggo_cidade(u)
    if not cidade:
        return []
    return Almoxarifado.query.filter(
        db.func.lower(Almoxarifado.cidade) == cidade
    ).all()

def usuario_atual():
    if 'usuario_id' in session:
        return db.session.get(Usuario, session['usuario_id'])
    return None

def is_fundador():
    u = usuario_atual()
    return u is not None and u.login == 'rick'

def login_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if 'usuario_id' not in session:
            flash('Faca login para continuar.', 'warning')
            return redirect(url_for('auth_bp.login'))
        u = db.session.get(Usuario, session['usuario_id'])
        if not u or not u.ativo:
            session.clear()
            flash('Sessao expirada. Faca login novamente.', 'warning')
            return redirect(url_for('auth_bp.login'))
        return f(*args, **kwargs)
    return decorated

def admin_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if 'usuario_id' not in session:
            return redirect(url_for('auth_bp.login'))
        u = db.session.get(Usuario, session['usuario_id'])
        if not u or u.perfil != 'admin':
            flash('Acesso restrito ao administrador.', 'danger')
            return redirect(url_for('main_bp.index'))
        return f(*args, **kwargs)
    return decorated

def almoxarife_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if 'usuario_id' not in session:
            return redirect(url_for('auth_bp.login'))
        u = db.session.get(Usuario, session['usuario_id'])
        if not u or u.perfil not in ('admin', 'almoxarife', 'assistente'):
            flash('Acesso restrito ao almoxarife.', 'danger')
            return redirect(url_for('main_bp.index'))
        return f(*args, **kwargs)
    return decorated

PERMISSOES_DISPONIVEIS = {
    'fazer_requisicao': 'Fazer Requisicoes ao Almoxarifado',
    'ver_relatorios':   'Ver Relatorios (Consumo, Ficha EPI)',
    'ver_alertas':      'Ver Alertas de Estoque',
}
