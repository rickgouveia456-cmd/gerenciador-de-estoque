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
    valor_unitario = db.Column(db.Float, nullable=True)  # preco unitario (sincronizado do catalogo)
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


class FichaEPI(db.Model):
    """Ficha formal de controle de EPI por colaborador."""
    __tablename__ = 'ficha_epi'
    id              = db.Column(db.Integer, primary_key=True)
    colaborador     = db.Column(db.String(100), nullable=False)
    funcao          = db.Column(db.String(100), nullable=True)
    obra            = db.Column(db.String(100), nullable=True)
    almoxarifado_id = db.Column(db.Integer, db.ForeignKey('almoxarifado.id'), nullable=False)
    almoxarifado    = db.relationship('Almoxarifado', backref='fichas_epi')
    status          = db.Column(db.String(20), default='ativa')   # ativa | encerrada
    data_abertura   = db.Column(db.DateTime, default=agora)
    data_encerramento = db.Column(db.DateTime, nullable=True)
    criado_por      = db.Column(db.String(100), nullable=True)
    itens           = db.relationship('ItemFichaEPI', backref='ficha', lazy=True,
                                      cascade='all, delete-orphan')


class ItemFichaEPI(db.Model):
    """Linha de uma ficha de EPI: um EPI entregue a um colaborador."""
    __tablename__ = 'item_ficha_epi'
    id              = db.Column(db.Integer, primary_key=True)
    ficha_id        = db.Column(db.Integer, db.ForeignKey('ficha_epi.id'), nullable=False)
    descricao       = db.Column(db.String(200), nullable=False)
    ca              = db.Column(db.String(30), nullable=True)
    quantidade      = db.Column(db.Float, default=1)
    tamanho         = db.Column(db.String(20), nullable=True)
    data_entrega    = db.Column(db.DateTime, default=agora)
    data_devolucao  = db.Column(db.DateTime, nullable=True)
    motivo_devolucao = db.Column(db.String(200), nullable=True)
    registrado_por  = db.Column(db.String(100), nullable=True)


class CertificadoCA(db.Model):
    """Certificado de Aprovação (CA) do Ministério do Trabalho para EPIs."""
    __tablename__ = 'certificado_ca'
    id              = db.Column(db.Integer, primary_key=True)
    numero_ca       = db.Column(db.String(30), nullable=False)
    nome_epi        = db.Column(db.String(200), nullable=False)
    fabricante      = db.Column(db.String(150), nullable=True)
    tipo            = db.Column(db.String(100), nullable=True)   # Capacete, Luva, Bota...
    data_validade   = db.Column(db.DateTime, nullable=True)
    data_emissao    = db.Column(db.DateTime, nullable=True)
    ativo           = db.Column(db.Boolean, default=True)
    almoxarifado_id = db.Column(db.Integer, db.ForeignKey('almoxarifado.id'), nullable=True)
    almoxarifado    = db.relationship('Almoxarifado', backref='certificados_ca')
    criado_por      = db.Column(db.String(100), nullable=True)
    data_cadastro   = db.Column(db.DateTime, default=agora)

    @property
    def status(self):
        from datetime import datetime
        if not self.data_validade:
            return 'sem_validade'
        dias = (self.data_validade - datetime.now()).days
        if dias < 0:
            return 'vencido'
        elif dias <= 90:
            return 'a_vencer'
        return 'valido'


class MatrizEPI(db.Model):
    """Matriz que relaciona função/cargo com EPIs obrigatórios."""
    __tablename__ = 'matriz_epi'
    id             = db.Column(db.Integer, primary_key=True)
    funcao         = db.Column(db.String(100), nullable=False)
    obra           = db.Column(db.String(100), nullable=True)
    almoxarifado_id = db.Column(db.Integer, db.ForeignKey('almoxarifado.id'), nullable=True)
    almoxarifado   = db.relationship('Almoxarifado', backref='matrizes_epi')
    epis_obrigatorios = db.Column(db.Text, nullable=True)  # JSON: lista de nomes
    norma          = db.Column(db.String(50), nullable=True)  # NR-6, NR-35...
    criado_por     = db.Column(db.String(100), nullable=True)
    data_cadastro  = db.Column(db.DateTime, default=agora)


class Treinamento(db.Model):
    """Treinamento de segurança registrado para colaboradores."""
    __tablename__ = 'treinamento'
    id              = db.Column(db.Integer, primary_key=True)
    tipo            = db.Column(db.String(100), nullable=False)   # NR-10, NR-35, Brigada...
    descricao       = db.Column(db.String(300), nullable=True)
    data_realizacao = db.Column(db.DateTime, nullable=False)
    validade_meses  = db.Column(db.Integer, nullable=True)
    responsavel     = db.Column(db.String(100), nullable=True)    # instrutor
    cargo_responsavel = db.Column(db.String(100), nullable=True)  # ex: Técnico em Segurança
    registro_mte    = db.Column(db.String(30), nullable=True)     # MTE: 0115464
    carga_horaria   = db.Column(db.Integer, nullable=True)        # horas
    local           = db.Column(db.String(150), nullable=True)    # ex: Aracaju/SE
    portaria        = db.Column(db.String(80), nullable=True)     # ex: Portaria nº 915 de 30/07/19
    nr_referencia   = db.Column(db.String(30), nullable=True)     # ex: NR 35
    almoxarifado_id = db.Column(db.Integer, db.ForeignKey('almoxarifado.id'), nullable=True)
    almoxarifado    = db.relationship('Almoxarifado', backref='treinamentos')
    criado_por      = db.Column(db.String(100), nullable=True)
    data_cadastro   = db.Column(db.DateTime, default=agora)
    participantes   = db.relationship('TreinamentoParticipante', backref='treinamento',
                                      lazy=True, cascade='all, delete-orphan')

    @property
    def data_vencimento(self):
        if not self.validade_meses:
            return None
        from datetime import timedelta
        return self.data_realizacao + timedelta(days=self.validade_meses * 30)

    @property
    def status(self):
        venc = self.data_vencimento
        if not venc:
            return 'sem_vencimento'
        dias = (venc - datetime.now()).days
        if dias < 0:
            return 'vencido'
        elif dias <= 90:
            return 'a_vencer'
        return 'valido'


class TreinamentoParticipante(db.Model):
    """Colaborador que participou de um treinamento."""
    __tablename__ = 'treinamento_participante'
    id              = db.Column(db.Integer, primary_key=True)
    treinamento_id  = db.Column(db.Integer, db.ForeignKey('treinamento.id'), nullable=False)
    colaborador     = db.Column(db.String(100), nullable=False)
    cpf             = db.Column(db.String(20), nullable=True)
    funcao          = db.Column(db.String(100), nullable=True)
    concluiu        = db.Column(db.Boolean, default=True)
    observacao      = db.Column(db.String(200), nullable=True)


class ConfiguracaoSistema(db.Model):
    """Configurações gerais do sistema — chave/valor com suporte a binário."""
    __tablename__ = 'configuracao_sistema'
    id      = db.Column(db.Integer, primary_key=True)
    chave   = db.Column(db.String(100), unique=True, nullable=False)
    valor   = db.Column(db.Text, nullable=True)        # para textos
    binario = db.Column(db.LargeBinary, nullable=True) # para arquivos (PPTX, etc.)


class HabilitacaoFuncionario(db.Model):
    """Certificado/habilitação que habilita um funcionário para exercer uma função."""
    __tablename__ = 'habilitacao_funcionario'
    id              = db.Column(db.Integer, primary_key=True)
    colaborador     = db.Column(db.String(100), nullable=False)
    tipo            = db.Column(db.String(100), nullable=False)   # CNH, NR-10, NR-35, CREA...
    numero          = db.Column(db.String(60), nullable=True)     # nº do certificado
    emissor         = db.Column(db.String(150), nullable=True)    # SENAI, SENAC, MTE...
    funcao_habilitada = db.Column(db.String(150), nullable=True)  # Ex: Trabalho em Altura
    data_emissao    = db.Column(db.DateTime, nullable=True)
    data_validade   = db.Column(db.DateTime, nullable=True)
    almoxarifado_id = db.Column(db.Integer, db.ForeignKey('almoxarifado.id'), nullable=True)
    almoxarifado    = db.relationship('Almoxarifado', backref='habilitacoes')
    criado_por      = db.Column(db.String(100), nullable=True)
    data_cadastro   = db.Column(db.DateTime, default=agora)
    ativo           = db.Column(db.Boolean, default=True)

    @property
    def status(self):
        if not self.data_validade:
            return 'sem_vencimento'
        dias = (self.data_validade - datetime.now()).days
        if dias < 0:
            return 'vencido'
        elif dias <= 90:
            return 'a_vencer'
        return 'valido'


class Kit(db.Model):
    """Kit de itens pré-definidos para retirada rápida."""
    id = db.Column(db.Integer, primary_key=True)
    nome = db.Column(db.String(200), nullable=False)
    descricao = db.Column(db.String(500), nullable=True)
    almoxarifado_id = db.Column(db.Integer, db.ForeignKey('almoxarifado.id'), nullable=False)
    almoxarifado = db.relationship('Almoxarifado', backref='kits')
    ativo = db.Column(db.Boolean, default=True)
    criado_por = db.Column(db.String(100), nullable=True)
    data_criacao = db.Column(db.DateTime, default=agora)
    itens = db.relationship('KitItem', backref='kit', lazy=True, cascade='all, delete-orphan')


class KitItem(db.Model):
    """Item pertencente a um Kit."""
    id = db.Column(db.Integer, primary_key=True)
    kit_id = db.Column(db.Integer, db.ForeignKey('kit.id'), nullable=False)
    item_id = db.Column(db.Integer, db.ForeignKey('item.id'), nullable=False)
    item = db.relationship('Item')
    quantidade = db.Column(db.Float, nullable=False)


class CatalogoInsumo(db.Model):
    __tablename__ = 'catalogo_insumo'
    id = db.Column(db.Integer, primary_key=True)
    nome = db.Column(db.String(300), nullable=False)
    codigo_ref = db.Column(db.String(100), nullable=True)  # código de referência/fabricante
    unidade = db.Column(db.String(20), nullable=False)
    categoria = db.Column(db.String(30), default='geral')
    ca = db.Column(db.String(20), nullable=True)  # Certificado de Aprovação (EPIs)
    descricao = db.Column(db.String(500), nullable=True)
    ativo = db.Column(db.Boolean, default=True)
    data_cadastro = db.Column(db.DateTime, default=agora)
    valor_unitario = db.Column(db.Float, nullable=True)  # preco unitario de referencia
    criado_por = db.Column(db.String(100), nullable=True)
