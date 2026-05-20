import os
from flask import Flask
from flask_sqlalchemy import SQLAlchemy
from flask_wtf.csrf import CSRFProtect

from .utils import (
    configure_app,
    fmt_qtd,
    inicializar_banco,
    enforce_https_in_production,
    set_security_headers,
    inject_sidebar,
)
from . import routes
from .backup import schedule_backup


db = SQLAlchemy()
csrf = CSRFProtect()


def create_app():
    app = Flask(__name__, static_folder='static', static_url_path='/static')
    configure_app(app)
    db.init_app(app)
    csrf.init_app(app)

    app.template_filter('fmt_qtd')(fmt_qtd)
    app.before_request(enforce_https_in_production)
    app.after_request(set_security_headers)
    app.context_processor(inject_sidebar)
    routes.init_routes(app)

    with app.app_context():
        inicializar_banco()

    schedule_backup(app)
    return app
