
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
    _check_rate_limit, _register_attempt, _clear_attempts, _check_api_rate)

logger = logging.getLogger(__name__)
auth_bp = Blueprint('auth_bp', __name__)

@auth_bp.route('/static/sw.js')
def service_worker():
    """Serve o Service Worker com headers corretos para PWA."""
    from flask import send_from_directory
    response = send_from_directory('static', 'sw.js', mimetype='application/javascript')
    response.headers['Service-Worker-Allowed'] = '/'
    response.headers['Cache-Control'] = 'no-cache'
    return response

@auth_bp.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        ip = request.remote_addr or '0.0.0.0'
        if _check_rate_limit(ip):
            flash('Muitas tentativas. Aguarde 5 minutos.', 'danger')
            return render_template('login.html'), 429
        login_val = request.form.get('login', '').strip()
        senha_val = request.form.get('senha', '')
        totp_val  = request.form.get('totp_code', '').strip().replace(' ', '')
        if not login_val or not senha_val:
            flash('Preencha login e senha.', 'warning')
            return render_template('login.html')
        u = Usuario.query.filter_by(login=login_val, ativo=True).first()
        if u and u.check_senha(senha_val):
            # Verificar 2FA se estiver ativado
            if u.totp_secret:
                try:
                    import pyotp
                    totp = pyotp.TOTP(u.totp_secret)
                    if not totp_val or not totp.verify(totp_val, valid_window=1):
                        _register_attempt(ip)
                        flash('Código 2FA inválido ou expirado.', 'danger')
                        return render_template('login.html', requer_2fa=True,
                                               login_val=login_val)
                except Exception:
                    flash('Erro ao verificar 2FA. Tente novamente.', 'danger')
                    return render_template('login.html', requer_2fa=True,
                                           login_val=login_val)
            _clear_attempts(ip)
            session.clear()
            session.permanent = True
            session['usuario_id'] = u.id
            flash(f'Bem-vindo, {u.nome}!', 'success')
            return redirect(url_for('main_bp.index'))
        _register_attempt(ip)
        import time as _time
        _time.sleep(0.3)  # timing uniforme — dificulta enumeration de usuários
        flash('Login ou senha incorretos.', 'danger')
    return render_template('login.html')

@auth_bp.route('/logout', methods=['POST'])
@login_required
def logout():
    session.clear()
    return redirect(url_for('auth_bp.login'))

# ── 2FA — AUTENTICAÇÃO DE DOIS FATORES ───────────────────────────────────────
@auth_bp.route('/perfil/2fa/ativar', methods=['GET', 'POST'])
@login_required
def ativar_2fa():
    """Gera QR Code para o usuário configurar o 2FA no Google Authenticator."""
    import pyotp, io as _io, base64
    u = usuario_atual()
    if request.method == 'POST':
        codigo = request.form.get('codigo', '').strip().replace(' ', '')
        secret = session.get('totp_secret_pendente')
        if not secret:
            flash('Sessão expirada. Tente novamente.', 'danger')
            return redirect(url_for('auth_bp.ativar_2fa'))
        totp = pyotp.TOTP(secret)
        if totp.verify(codigo, valid_window=1):
            u.totp_secret = secret
            session.pop('totp_secret_pendente', None)
            db.session.commit()
            flash('✅ 2FA ativado com sucesso! Seu login agora exige o código do app.', 'success')
            return redirect(url_for('main_bp.index'))
        flash('Código inválido. Tente novamente.', 'danger')
        return redirect(url_for('auth_bp.ativar_2fa'))

    # Gerar novo secret
    secret = pyotp.random_base32()
    session['totp_secret_pendente'] = secret
    uri = pyotp.totp.TOTP(secret).provisioning_uri(
        name=u.login, issuer_name='Logi-Prime Obra Patamares'
    )

    # Gerar QR Code como SVG (sem dependência de Pillow/PIL)
    try:
        import qrcode, qrcode.image.svg
        factory = qrcode.image.svg.SvgPathImage
        img = qrcode.make(uri, image_factory=factory, box_size=10)
        buf = _io.BytesIO()
        img.save(buf)
        qr_svg = buf.getvalue().decode('utf-8')
        qr_b64 = None
    except Exception:
        qr_svg = None
        qr_b64 = None

    return render_template('2fa_ativar.html', qr_svg=qr_svg, qr_b64=qr_b64,
                           secret=secret, usuario=u, uri=uri)

@auth_bp.route('/perfil/2fa/desativar', methods=['POST'])
@login_required
def desativar_2fa():
    u = usuario_atual()
    u.totp_secret = None
    db.session.commit()
    flash('2FA desativado.', 'warning')
    return redirect(url_for('main_bp.index'))

@auth_bp.route('/admin/2fa/desativar/<int:uid>', methods=['POST'])
@admin_required
def admin_desativar_2fa(uid):
    """Admin pode desativar 2FA de qualquer usuário (ex: perdeu o celular)."""
    u = Usuario.query.get_or_404(uid)
    u.totp_secret = None
    db.session.commit()
    flash(f'2FA de {u.nome} desativado pelo admin.', 'warning')
    return redirect(url_for('usuarios_bp.usuarios'))

@auth_bp.route('/healthz')
def healthz():
    """Healthcheck — responde imediatamente sem tocar no banco."""
    return 'ok', 200

# ── ROTAS PRINCIPAIS ─────────────────────────────────────────────────────────
