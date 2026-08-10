from datetime import datetime, timezone, timedelta
from werkzeug.security import generate_password_hash, check_password_hash
from extensions import db

# Fuso horário de Brasília (UTC-3)
TZ_BRASILIA = timezone(timedelta(hours=-3))

def agora():
    """Retorna o datetime atual no horário de Brasília"""
    return datetime.now(TZ_BRASILIA).replace(tzinfo=None)


class Almoxarifado(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    nome = db.Column(db.String(100), nullable=False)
    descricao = db.Column(db.String(200))
    obra = db.Column(db.String(100), nullable=True)
    cidade = db.Column(db.String(100), nullable=True)
    itens = db.relationship('Item', backref='almoxarifado', lazy=True, cascade='all, delete-orphan')


class Item(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    nome = db.Column(db.String(300), nullable=False)
    codigo = db.Column(db.String(50), nullable=False)
    unidade = db.Column(db.String(20), nullable=False)
    quantidade = db.Column(db.Float, default=0)
    estoque_minimo = db.Column(db.Float, default=0)
    almoxarifado_id = db.Column(db.Integer, db.ForeignKey('almoxarifado.id'), nullable=False)
    status_compra = db.Column(db.String(30), default='pendente')
    fixado = db.Column(db.Boolean, default=False)
    ativo = db.Column(db.Boolean, default=True)
    categoria = db.Column(db.String(30), default='geral')
    ca = db.Column(db.String(20))
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
    devolvido = db.Column(db.Boolean, nullable=True)
    foto_url = db.Column(db.Text, nullable=True)


class Requisicao(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    colaborador = db.Column(db.String(100), nullable=False)
    observacao = db.Column(db.String(200))
    quantidade = db.Column(db.Float, nullable=False)
    status = db.Column(db.String(20), default='aberta')
    data_retirada = db.Column(db.DateTime, default=agora)
    data_devolucao = db.Column(db.DateTime, nullable=True)
    item_id = db.Column(db.Integer, db.ForeignKey('item.id'), nullable=False)
    item = db.relationship('Item', backref='requisicoes')


class RequisicaoMestre(db.Model):
    """Requisição feita pelo mestre de obra ao almoxarifado."""
    id = db.Column(db.Integer, primary_key=True)
    protocolo = db.Column(db.String(30), unique=True, nullable=True)
    mestre_id = db.Column(db.Integer, db.ForeignKey('usuario.id'), nullable=False)
    mestre = db.relationship('Usuario', foreign_keys=[mestre_id])
    colaborador = db.Column(db.String(100), nullable=False)
    almoxarifado_id = db.Column(db.Integer, db.ForeignKey('almoxarifado.id'), nullable=False)
    almoxarifado = db.relationship('Almoxarifado')
    observacao = db.Column(db.String(300))
    status = db.Column(db.String(20), default='pendente')
    data_criacao = db.Column(db.DateTime, default=agora)
    data_entrega = db.Column(db.DateTime, nullable=True)
    entregue_por_id = db.Column(db.Integer, db.ForeignKey('usuario.id'), nullable=True)
    entregue_por = db.relationship('Usuario', foreign_keys=[entregue_por_id])
    foto_url = db.Column(db.Text, nullable=True)
    itens = db.relationship('RequisicaoMestreItem', backref='requisicao', lazy=True, cascade='all, delete-orphan')


class RequisicaoMestreItem(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    requisicao_id = db.Column(db.Integer, db.ForeignKey('requisicao_mestre.id'), nullable=False)
    item_id = db.Column(db.Integer, db.ForeignKey('item.id'), nullable=False)
    item = db.relationship('Item')
    quantidade = db.Column(db.Float, nullable=False)
    observacao = db.Column(db.String(200))
    status_item = db.Column(db.String(20), default='pendente')
    motivo_recusa = db.Column(db.String(200))


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


class Usuario(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    nome = db.Column(db.String(100), nullable=False)
    login = db.Column(db.String(50), unique=True, nullable=False)
    senha_hash = db.Column(db.String(256), nullable=False)
    perfil = db.Column(db.String(20), default='colaborador')
    almoxarifado_id = db.Column(db.Integer, db.ForeignKey('almoxarifado.id'), nullable=True)
    escopo = db.Column(db.String(50), nullable=True)
    email = db.Column(db.String(120), nullable=True)
    ativo = db.Column(db.Boolean, default=True)
    totp_secret = db.Column(db.String(32), nullable=True)
    almoxarifado = db.relationship('Almoxarifado', backref='usuarios')
    acessos_extras = db.relationship('AcessoExtra', backref='usuario', lazy=True, cascade='all, delete-orphan')
    pode_requisitar = db.Column(db.Boolean, default=False)
    pode_ver_alertas = db.Column(db.Boolean, default=False)

    def set_senha(self, senha):
        self.senha_hash = generate_password_hash(senha)

    def check_senha(self, senha):
        if len(self.senha_hash) == 64 and not self.senha_hash.startswith('pbkdf2:'):
            import hashlib
            if self.senha_hash == hashlib.sha256(senha.encode()).hexdigest():
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
    id = db.Column(db.Integer, primary_key=True)
    nome = db.Column(db.String(100), nullable=False)
    funcao = db.Column(db.String(50))
    escopo = db.Column(db.String(50))
    obra = db.Column(db.String(100), nullable=True)
    cidade = db.Column(db.String(100), nullable=True)
    tipo = db.Column(db.String(30), default='peao')
    ativo = db.Column(db.Boolean, default=True)
    data_cadastro = db.Column(db.DateTime, default=agora)


class Ferramenta(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    identificacao = db.Column(db.String(50), nullable=False)
    nome = db.Column(db.String(200), nullable=False)
    empresa = db.Column(db.String(100))
    almoxarifado_id = db.Column(db.Integer, db.ForeignKey('almoxarifado.id'), nullable=False)
    almoxarifado = db.relationship('Almoxarifado', backref='ferramentas')
    status = db.Column(db.String(20), default='disponivel')
    responsavel_atual = db.Column(db.String(100))
    data_saida = db.Column(db.DateTime, nullable=True)
    observacao = db.Column(db.String(200))
    local = db.Column(db.String(100))
    ativo = db.Column(db.Boolean, default=True)
    data_cadastro = db.Column(db.DateTime, default=agora)
    historico = db.relationship('HistoricoFerramenta', backref='ferramenta', lazy=True,
                                order_by='HistoricoFerramenta.data_saida.desc()')


class HistoricoFerramenta(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    ferramenta_id = db.Column(db.Integer, db.ForeignKey('ferramenta.id'), nullable=False)
    colaborador = db.Column(db.String(100), nullable=False)
    data_saida = db.Column(db.DateTime, nullable=False)
    data_devolucao = db.Column(db.DateTime, nullable=True)
    registrado_por = db.Column(db.String(100))
    tipo_evento = db.Column(db.String(20), default='uso')
    motivo_manutencao = db.Column(db.String(300), nullable=True)
    foto_url = db.Column(db.Text, nullable=True)


class ItemEPI(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    identificacao = db.Column(db.String(50), nullable=False)
    nome = db.Column(db.String(200), nullable=False)
    tamanho = db.Column(db.String(30))
    almoxarifado_id = db.Column(db.Integer, db.ForeignKey('almoxarifado.id'), nullable=False)
    almoxarifado = db.relationship('Almoxarifado', backref='epis')
    status = db.Column(db.String(20), default='disponivel')
    responsavel_atual = db.Column(db.String(100))
    quantidade = db.Column(db.Integer, default=1)
    local = db.Column(db.String(100))
    observacao = db.Column(db.String(200))
    ativo = db.Column(db.Boolean, default=True)
    data_cadastro = db.Column(db.DateTime, default=agora)
    historico = db.relationship('HistoricoEPI', backref='item_epi', lazy=True,
                                order_by='HistoricoEPI.data_saida.desc()')


class HistoricoEPI(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    item_epi_id = db.Column(db.Integer, db.ForeignKey('item_epi.id'), nullable=False)
    colaborador = db.Column(db.String(100), nullable=False)
    data_saida = db.Column(db.DateTime, nullable=False)
    data_devolucao = db.Column(db.DateTime, nullable=True)
    registrado_por = db.Column(db.String(100))
    tipo_evento = db.Column(db.String(20), default='uso')
    motivo_manutencao = db.Column(db.String(300), nullable=True)
    foto_url = db.Column(db.Text, nullable=True)


class PermissaoExtra(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    usuario_id = db.Column(db.Integer, db.ForeignKey('usuario.id'), nullable=False)
    permissao = db.Column(db.String(50), nullable=False)
    concedido_por = db.Column(db.String(100))
    data_concessao = db.Column(db.DateTime, default=agora)
    usuario = db.relationship('Usuario', backref='permissoes_extras')


class CatalogoInsumo(db.Model):
    __tablename__ = 'catalogo_insumo'
    id = db.Column(db.Integer, primary_key=True)
    nome = db.Column(db.String(300), nullable=False)
    codigo_ref = db.Column(db.String(50), nullable=True)  # código de referência/fabricante
    unidade = db.Column(db.String(20), nullable=False)
    categoria = db.Column(db.String(30), default='geral')
    ca = db.Column(db.String(20), nullable=True)  # Certificado de Aprovação (EPIs)
    descricao = db.Column(db.String(500), nullable=True)
    ativo = db.Column(db.Boolean, default=True)
    data_cadastro = db.Column(db.DateTime, default=agora)
    criado_por = db.Column(db.String(100), nullable=True)
