"""Inicializacao do banco: migracoes DDL, seed e classificacao automatica."""
import logging
import secrets
from sqlalchemy import text
from extensions import db
from models import (agora, Almoxarifado, Item, Movimentacao, Colaborador,
                    Ferramenta, ItemEPI, Usuario)

logger = logging.getLogger(__name__)
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
            safe_exec(conn, "ALTER TABLE item ADD COLUMN ca VARCHAR(20)")
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
            # ── Coluna protocolo em requisicao_mestre ─────────────────────────
            safe_exec(conn, "ALTER TABLE requisicao_mestre ADD COLUMN protocolo VARCHAR(30)")

            # ── Valores padrão para colunas que podem estar NULL ─────────────
            safe_exec(conn, "UPDATE requisicao_mestre_item SET status_item = 'pendente' WHERE status_item IS NULL")
            safe_exec(conn, "UPDATE requisicao_mestre SET notificado = FALSE WHERE notificado IS NULL")

            # ── Campo escopo em colaborador ──────────────────────────────────
            safe_exec(conn, "ALTER TABLE colaborador ADD COLUMN escopo VARCHAR(50)")
            # ── Campos obra e cidade em colaborador ───────────────────────────
            safe_exec(conn, "ALTER TABLE colaborador ADD COLUMN obra VARCHAR(100)")
            safe_exec(conn, "ALTER TABLE colaborador ADD COLUMN cidade VARCHAR(100)")

            # ── Campo devolvido em movimentacao ──────────────────────────────
            safe_exec(conn, "ALTER TABLE movimentacao ADD COLUMN devolvido BOOLEAN")
            # ── Campo foto_url em movimentacao ────────────────────────────────
            safe_exec(conn, "ALTER TABLE movimentacao ADD COLUMN foto_url TEXT")

            # ── Campo foto_url em requisicao_mestre ───────────────────────────
            safe_exec(conn, "ALTER TABLE requisicao_mestre ADD COLUMN foto_url TEXT")

            # ── Campo escopo em usuario (para analista) ───────────────────────
            safe_exec(conn, "ALTER TABLE usuario ADD COLUMN escopo VARCHAR(50)")

            # ── Campo local em ferramenta ─────────────────────────────────────
            safe_exec(conn, "ALTER TABLE ferramenta ADD COLUMN local VARCHAR(100)")
            # ── Colunas adicionais em item_epi (para bancos criados sem elas) ─
            safe_exec(conn, "ALTER TABLE item_epi ADD COLUMN quantidade INTEGER DEFAULT 1")
            safe_exec(conn, "ALTER TABLE item_epi ADD COLUMN local VARCHAR(100)")

            # ── Campo obra em almoxarifado ────────────────────────────────────
            safe_exec(conn, "ALTER TABLE almoxarifado ADD COLUMN obra VARCHAR(100)")
            # Setar obra padrão para almoxarifados existentes sem obra definida
            safe_exec(conn, "UPDATE almoxarifado SET obra = 'Salvador' WHERE obra IS NULL OR obra = ''")
            # ── Campo cidade em almoxarifado ──────────────────────────────────
            safe_exec(conn, "ALTER TABLE almoxarifado ADD COLUMN cidade VARCHAR(100)")
            # Setar cidade padrão para almoxarifados existentes sem cidade definida
            safe_exec(conn, "UPDATE almoxarifado SET cidade = 'Salvador' WHERE cidade IS NULL OR cidade = ''")
            # Se obra contém nome de cidade (Aracaju/Salvador), mover para cidade e limpar obra
            safe_exec(conn, "UPDATE almoxarifado SET cidade = obra, obra = NULL WHERE obra IN ('Aracaju', 'Salvador', 'aracaju', 'salvador') AND (cidade IS NULL OR cidade = '')")
            # ── Campos obra e cidade em colaborador ───────────────────────────
            safe_exec(conn, "ALTER TABLE colaborador ADD COLUMN obra VARCHAR(100)")
            safe_exec(conn, "ALTER TABLE colaborador ADD COLUMN cidade VARCHAR(100)")

            # ── Tabela permissao_extra ────────────────────────────────────────
            if is_pg:
                safe_exec(conn, """
                    CREATE TABLE IF NOT EXISTS permissao_extra (
                        id SERIAL PRIMARY KEY,
                        usuario_id INTEGER NOT NULL REFERENCES usuario(id),
                        permissao VARCHAR(50) NOT NULL,
                        concedido_por VARCHAR(100),
                        data_concessao TIMESTAMP
                    )
                """)
            else:
                safe_exec(conn, """
                    CREATE TABLE IF NOT EXISTS permissao_extra (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        usuario_id INTEGER NOT NULL REFERENCES usuario(id),
                        permissao VARCHAR(50) NOT NULL,
                        concedido_por VARCHAR(100),
                        data_concessao DATETIME
                    )
                """)

            # ── Tabela item_epi ───────────────────────────────────────────────
            if is_pg:
                safe_exec(conn, """
                    CREATE TABLE IF NOT EXISTS item_epi (
                        id SERIAL PRIMARY KEY,
                        identificacao VARCHAR(50) NOT NULL,
                        nome VARCHAR(200) NOT NULL,
                        tamanho VARCHAR(30),
                        almoxarifado_id INTEGER NOT NULL REFERENCES almoxarifado(id),
                        status VARCHAR(20) DEFAULT 'disponivel',
                        responsavel_atual VARCHAR(100),
                        quantidade INTEGER DEFAULT 1,
                        local VARCHAR(100),
                        observacao VARCHAR(200),
                        ativo BOOLEAN DEFAULT TRUE,
                        data_cadastro TIMESTAMP
                    )
                """)
            else:
                safe_exec(conn, """
                    CREATE TABLE IF NOT EXISTS item_epi (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        identificacao VARCHAR(50) NOT NULL,
                        nome VARCHAR(200) NOT NULL,
                        tamanho VARCHAR(30),
                        almoxarifado_id INTEGER NOT NULL REFERENCES almoxarifado(id),
                        status VARCHAR(20) DEFAULT 'disponivel',
                        responsavel_atual VARCHAR(100),
                        quantidade INTEGER DEFAULT 1,
                        local VARCHAR(100),
                        observacao VARCHAR(200),
                        ativo BOOLEAN DEFAULT TRUE,
                        data_cadastro DATETIME
                    )
                """)

            # ── Tabela historico_epi ──────────────────────────────────────────
            if is_pg:
                safe_exec(conn, """
                    CREATE TABLE IF NOT EXISTS historico_epi (
                        id SERIAL PRIMARY KEY,
                        item_epi_id INTEGER NOT NULL REFERENCES item_epi(id),
                        colaborador VARCHAR(100) NOT NULL,
                        data_saida TIMESTAMP NOT NULL,
                        data_devolucao TIMESTAMP,
                        registrado_por VARCHAR(100),
                        tipo_evento VARCHAR(20) DEFAULT 'uso',
                        motivo_manutencao VARCHAR(300),
                        foto_url TEXT
                    )
                """)
            else:
                safe_exec(conn, """
                    CREATE TABLE IF NOT EXISTS historico_epi (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        item_epi_id INTEGER NOT NULL REFERENCES item_epi(id),
                        colaborador VARCHAR(100) NOT NULL,
                        data_saida DATETIME NOT NULL,
                        data_devolucao DATETIME,
                        registrado_por VARCHAR(100),
                        tipo_evento VARCHAR(20) DEFAULT 'uso',
                        motivo_manutencao VARCHAR(300),
                        foto_url TEXT
                    )
                """)

            # ── Tabela ferramentas ────────────────────────────────────────────
            if is_pg:
                safe_exec(conn, """
                    CREATE TABLE IF NOT EXISTS ferramenta (
                        id SERIAL PRIMARY KEY,
                        identificacao VARCHAR(50) NOT NULL,
                        nome VARCHAR(200) NOT NULL,
                        almoxarifado_id INTEGER NOT NULL REFERENCES almoxarifado(id),
                        status VARCHAR(20) DEFAULT 'disponivel',
                        responsavel_atual VARCHAR(100),
                        observacao VARCHAR(200),
                        ativo BOOLEAN DEFAULT TRUE,
                        data_cadastro TIMESTAMP
                    )
                """)
            else:
                safe_exec(conn, """
                    CREATE TABLE IF NOT EXISTS ferramenta (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        identificacao VARCHAR(50) NOT NULL,
                        nome VARCHAR(200) NOT NULL,
                        almoxarifado_id INTEGER NOT NULL REFERENCES almoxarifado(id),
                        status VARCHAR(20) DEFAULT 'disponivel',
                        responsavel_atual VARCHAR(100),
                        observacao VARCHAR(200),
                        ativo BOOLEAN DEFAULT TRUE,
                        data_cadastro DATETIME
                    )
                """)
            # ── Campo tipo em colaborador ─────────────────────────────────────
            safe_exec(conn, "ALTER TABLE colaborador ADD COLUMN tipo VARCHAR(30) DEFAULT 'peao'")
            # ── Campo data_saida em ferramenta ────────────────────────────────
            if is_pg:
                safe_exec(conn, "ALTER TABLE ferramenta ADD COLUMN data_saida TIMESTAMP")
            else:
                safe_exec(conn, "ALTER TABLE ferramenta ADD COLUMN data_saida DATETIME")
            # ── Campo empresa em ferramenta ───────────────────────────────────
            safe_exec(conn, "ALTER TABLE ferramenta ADD COLUMN empresa VARCHAR(100)")

            # ── Campo 2FA em usuario ──────────────────────────────────────────
            safe_exec(conn, "ALTER TABLE usuario ADD COLUMN totp_secret VARCHAR(32)")
            # ── Permissões de função em usuario ──────────────────────────────
            safe_exec(conn, "ALTER TABLE usuario ADD COLUMN pode_requisitar BOOLEAN DEFAULT FALSE")
            safe_exec(conn, "ALTER TABLE usuario ADD COLUMN pode_ver_alertas BOOLEAN DEFAULT FALSE")

            # ── Tabela historico_ferramenta ───────────────────────────────────
            if is_pg:
                safe_exec(conn, """
                    CREATE TABLE IF NOT EXISTS historico_ferramenta (
                        id SERIAL PRIMARY KEY,
                        ferramenta_id INTEGER NOT NULL REFERENCES ferramenta(id),
                        colaborador VARCHAR(100) NOT NULL,
                        data_saida TIMESTAMP NOT NULL,
                        data_devolucao TIMESTAMP,
                        registrado_por VARCHAR(100),
                        tipo_evento VARCHAR(20) DEFAULT 'uso',
                        motivo_manutencao VARCHAR(300)
                    )
                """)
            else:
                safe_exec(conn, """
                    CREATE TABLE IF NOT EXISTS historico_ferramenta (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        ferramenta_id INTEGER NOT NULL REFERENCES ferramenta(id),
                        colaborador VARCHAR(100) NOT NULL,
                        data_saida DATETIME NOT NULL,
                        data_devolucao DATETIME,
                        registrado_por VARCHAR(100),
                        tipo_evento VARCHAR(20) DEFAULT 'uso',
                        motivo_manutencao VARCHAR(300)
                    )
                """)
            # ── Colunas novas em historico_ferramenta (bancos existentes) ────
            safe_exec(conn, "ALTER TABLE historico_ferramenta ADD COLUMN tipo_evento VARCHAR(20) DEFAULT 'uso'")
            safe_exec(conn, "ALTER TABLE historico_ferramenta ADD COLUMN motivo_manutencao VARCHAR(300)")
            safe_exec(conn, "ALTER TABLE historico_ferramenta ADD COLUMN foto_url TEXT")
            # Converter foto_url de VARCHAR(500) para TEXT (bancos com coluna antiga)
            if is_pg:
                safe_exec(conn, "ALTER TABLE historico_ferramenta ALTER COLUMN foto_url TYPE TEXT USING foto_url::TEXT")
            # Preencher tipo_evento nos registros antigos
            safe_exec(conn, "UPDATE historico_ferramenta SET tipo_evento = 'uso' WHERE tipo_evento IS NULL")
            # Garantir que ferramenta aceita status 'manutencao' (sem restrição de CHECK no SQLite)
            safe_exec(conn, "UPDATE ferramenta SET status = 'disponivel' WHERE status NOT IN ('disponivel','em_uso','manutencao','atrasado')")

            # ── Tabela catalogo_insumo ────────────────────────────────────────────
            if is_pg:
                safe_exec(conn, """
                    CREATE TABLE IF NOT EXISTS catalogo_insumo (
                        id SERIAL PRIMARY KEY,
                        nome VARCHAR(300) NOT NULL,
                        codigo_ref VARCHAR(50),
                        unidade VARCHAR(20) NOT NULL,
                        categoria VARCHAR(30) DEFAULT 'geral',
                        ca VARCHAR(20),
                        descricao VARCHAR(500),
                        ativo BOOLEAN DEFAULT TRUE,
                        data_cadastro TIMESTAMP,
                        criado_por VARCHAR(100)
                    )
                """)
            else:
                safe_exec(conn, """
                    CREATE TABLE IF NOT EXISTS catalogo_insumo (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        nome VARCHAR(300) NOT NULL,
                        codigo_ref VARCHAR(50),
                        unidade VARCHAR(20) NOT NULL,
                        categoria VARCHAR(30) DEFAULT 'geral',
                        ca VARCHAR(20),
                        descricao VARCHAR(500),
                        ativo BOOLEAN DEFAULT TRUE,
                        data_cadastro DATETIME,
                        criado_por VARCHAR(100)
                    )
                """)

    except Exception as e:
        logger.error(f'Migração: {e}')

# ── LOGIN / LOGOUT ────────────────────────────────────────────────────────────


def seed_data():
    if Almoxarifado.query.count() == 0:
        db.session.add_all([
            Almoxarifado(nome='Almoxarifado do Acampamento', descricao='Materiais de uso geral do acampamento'),
            Almoxarifado(nome='Almoxarifado de Infraestrutura', descricao='Materiais de construcao e manutencao'),
            Almoxarifado(nome='Almoxarifado de Forma', descricao='Formas, escoramentos e materiais de forma'),
        ])
        db.session.commit()
    if Usuario.query.count() == 0:
        # Gera senha aleatória segura — nunca usa senha padrão fixa
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

    # Seed de colaboradores da estrutura (roda uma vez se não houver nenhum)
    if Colaborador.query.count() == 0:
        _colaboradores_estrutura = [
            ("ARILSON DE JESUS SOUZA","Profissional"),("ADSON MUNIZ","Ajudante"),
            ("MARCEL OLIVEIRA DA CONCEIÇÃO","Profissional"),("ROBERT WILLIAM DA HORA DE JESUS","Profissional"),
            ("MATEUS SANTOS DE JESUS","Profissional"),("ANIBAL SANTOS DANTAS","Profissional"),
            ("ROBERTO FELIX GONÇALVES","Profissional"),("EDNALDO DOS SANTOS","Profissional"),
            ("ALEXSANDRO TELES DOS SANTOS","Profissional"),("RUAN UITALO","Ajudante"),
            ("FELIPE MESSIAS","Ajudante"),("ROQUE DOS SANTOS","Profissional"),
            ("VALDOMIRO GOMES DE JESUS FILHO","Profissional"),("VALMIR GOMES DE JESUS","Profissional"),
            ("TIAGO GOMES DOS SANTOS","Ajudante"),("ADRIANO SOUZA DOS SANTOS","Profissional"),
            ("RONALDO DA CUNHA SANTOS","Profissional"),("EMERSON DE SANTANA ARAUJO","Profissional"),
            ("LUAN DOS SANTOS CARDOSO","Profissional"),("FRANCISCO CARLOS DOS SANTOS FILHO","Profissional"),
            ("NILSON MATIAS DOS SANTOS","Profissional"),("VINICIUS DANTAS DA SILVA","Profissional"),
            ("MAURICIO RAMON PINHEIRO MATOS","Ajudante"),("CARLOS ALBERTO BISPO DOS SANTOS","Ajudante"),
            ("DIEGO LIMA SANTOS","Ajudante"),("AILTON DA SILVA","Profissional"),
            ("EIDSON SILVA ROCHA","Ajudante"),("MARLEI ASSIS DE SOUZA","Profissional"),
            ("RICARDO VASQUES LEMOS LEONI","Profissional"),("ROBISON SANTOS DA CONCEIÇÃO","Profissional"),
            ("LUIS SILVAN LOPES DOS SANTOS","Profissional"),("JAIR CESAR BRITO RODRIGUES JUNIOR","Ajudante"),
            ("ROBSON BISPO DOS SANTOS","Profissional"),("EDVAN MACHADO SANTOS","Profissional"),
            ("LUIS ALBERTO MOREIRA DA SILVA","Ajudante"),("ISAAC GONÇALVES DA SILVA","Ajudante"),
            ("ANTONIO MARCOS DA SILVA COSTA","Ajudante"),("DENAILTON LEITE DOS SANTOS","Ajudante"),
            ("MARCIO DE JESUS DOS SANTOS","Profissional"),("WEBER OLIVEIRA DA LUZ","Ajudante"),
            ("RAFAEL DA SILVA BOMFIM","Ajudante"),("ANDERSON RODRIGUES DOS SANTOS","Profissional"),
            ("JAILTON RIBEIRO TOSTA","Profissional"),("JOAO LUIS OLIVEIRA DA SILVA","Profissional"),
            ("ANDERSON SOUZA DE FRIAS","Profissional"),("JOILSON DOS SANTOS","Profissional"),
            ("JOANDERSON ALMEIDA BISPO","Profissional"),("ANTONIO CARLOS SANTOS SILVA","Profissional"),
            ("NAILTON CONCEIÇÃO DE SOUZA","Ajudante"),("LUCAS SILVA DOS REIS","Ajudante"),
            ("ROBSON LIMA MACIEL","Profissional"),("ATILA ALMEIDA SILVA SANTOS","Ajudante"),
            ("JONAS DE SENA BARRETO","Ajudante"),("SAMUEL BISPO DOS SANTOS","Profissional"),
            ("GUILHERME SANTOS SAMPAIO","Ajudante"),("DANIEL SÃO PEDRO DOS SANTOS","Ajudante"),
            ("DIVINO CARDOSO DOS SANTOS","Ajudante"),("ANDERSON CONCEIÇÃO DE JESUS","Profissional"),
            ("ALEX DE JESUS DA SILVA","Profissional"),("ALEX VITÓRIO SILVA","Ajudante"),
            ("CARLOS DANIEL DA SILVA MARQUES","Ajudante"),("THIEGO DE OLIVEIRA REIS","Profissional"),
            ("UBIRATTAN SNATOS SOUZA","Ajudante"),("WALISSON SILVA COSTA","Ajudante"),
            ("VALMIR GONÇALVES DE OLIVEIRA","Profissional"),("JUDICAEL LEITE DOS SANTOS","Profissional"),
            ("JOÃO PEDRO SILVA DOS SANTOS","Profissional"),("JORGE DOS SANTOS","Profissional"),
            ("JEAN AUGUSTO DOS SANTOS TAVARES","Profissional"),
        ]
        for nome, funcao in _colaboradores_estrutura:
            db.session.add(Colaborador(nome=nome, funcao=funcao, escopo='estrutura', ativo=True))
        db.session.commit()
        logger.info(f'Seed: {len(_colaboradores_estrutura)} colaboradores da estrutura cadastrados.')

def classificar_categorias_itens():
    """Classifica automaticamente itens pelo nome: EPI, Maquinário, Elétrica, Hidráulica, Gás."""
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
    palavras_eletrica = [
        'fio', 'cabo eletrico', 'cabo pp', 'eletroduto', 'disjuntor', 'tomada',
        'interruptor', 'condulete', 'luminaria', 'lampada', 'quadro de distribuicao',
        'wago', 'conector eletrico', 'passe fio eletrico', 'cabo flexivel',
        'fita isolante', 'eletrica', 'eletrico', 'no-break', 'estabilizador',
        'rgnh', 'thhn', 'sintenax', 'cabo 1.5', 'cabo 2.5', 'cabo 4mm', 'cabo 6mm',
        'cabo 10mm', 'cabo 16mm', 'cabo 35mm', 'canaleta', 'dimer', 'sensor de presença'
    ]
    palavras_hidraulica = [
        'tubo pvc', 'joelho pvc', 'te pvc', 'registro', 'torneira', 'chuveiro',
        'vaso sanitario', 'caixa dagua', 'sifao', 'valvula', 'te soldavel',
        'te reducao', 'joelho soldavel', 'luva soldavel', 'cap pvc', 'adaptador pvc',
        'bucha de reducao', 'curva pvc', 'tê reducao', 'cola pvc', 'lixa pvc',
        'cano pvc', 'tubo soldavel', 'pvc soldavel', 'flange', 'niple',
        'boia de nivel', 'bomba dagua', 'aquecedor', 'caixa sifonada', 'ralo',
        'vedacao', 'veda rosca', 'teflon', 'mangueira', 'pressao agua'
    ]
    palavras_gas = [
        'gas', 'tubulacao gas', 'registro gas', 'botijao', 'mangueira gas',
        'cobre gas', 'tubo cobre', 'solda cobre', 'abracadeira gas',
        'valvula gas', 'regulador gas', 'flexivel gas', 'conexao gas'
    ]
    try:
        itens = Item.query.filter(Item.categoria.in_(['geral', None])).all()
        atualizados = 0
        for it in itens:
            nome_lower = it.nome.lower()
            if any(p in nome_lower for p in palavras_epi):
                it.categoria = 'epi'; atualizados += 1
            elif any(p in nome_lower for p in palavras_maq):
                it.categoria = 'maquinario'; atualizados += 1
            elif any(p in nome_lower for p in palavras_eletrica):
                it.categoria = 'eletrica'; atualizados += 1
            elif any(p in nome_lower for p in palavras_hidraulica):
                it.categoria = 'hidraulica'; atualizados += 1
            elif any(p in nome_lower for p in palavras_gas):
                it.categoria = 'gas'; atualizados += 1
        if atualizados:
            db.session.commit()
            logger.info(f'Categorias: {atualizados} itens classificados automaticamente.')
    except Exception as e:
        logger.info(f'Categorias: erro ao classificar — {e}')


def migrar_itens_para_epi():
    """Migra automaticamente itens com categoria='epi' do estoque para ItemEPI.
    Executa apenas se ItemEPI estiver vazia mas houver itens EPI no estoque.
    Usa o nome+almoxarifado como chave para evitar duplicatas.
    """
    try:
        total_epi_items = Item.query.filter_by(categoria='epi', ativo=True).count()
        if total_epi_items == 0:
            return
        total_item_epi = ItemEPI.query.count()

        itens_epi = Item.query.filter_by(categoria='epi', ativo=True).order_by(Item.nome).all()
        migrados = 0
        for it in itens_epi:
            # Verifica se já existe um ItemEPI com o mesmo nome e almoxarifado
            existe = ItemEPI.query.filter_by(
                nome=it.nome,
                almoxarifado_id=it.almoxarifado_id,
                ativo=True
            ).first()
            if existe:
                continue
            novo = ItemEPI(
                identificacao=it.ca or it.codigo or f'EPI-{it.id}',
                nome=it.nome,
                tamanho=None,
                almoxarifado_id=it.almoxarifado_id,
                quantidade=max(1, int(it.quantidade)) if it.quantidade > 0 else 1,
                status='disponivel',
                observacao=f'Migrado do estoque (cod: {it.codigo})',
                ativo=True,
                data_cadastro=agora()
            )
            db.session.add(novo)
            migrados += 1

        if migrados > 0:
            db.session.commit()
            logger.info(f'MIGRAÇÃO EPI: {migrados} item(s) migrados do estoque para ItemEPI.')
        else:
            logger.info(f'MIGRAÇÃO EPI: sem itens novos para migrar ({total_item_epi} ItemEPI já existem).')
    except Exception as e:
        logger.error(f'MIGRAÇÃO EPI: erro — {e}')
        db.session.rollback()


def seed_ferramentas_estrutura_auto():
    """Cadastra automaticamente as ferramentas da Estrutura no startup — ignora duplicatas."""
    try:
        alm = Almoxarifado.query.filter(Almoxarifado.nome.ilike('%estrutura%')).first()
        if not alm:
            return
        ferramentas_lista = [
            ("INGFH007",   "PISTOLA DE FIXACAO A BATERIA BX 3-L A22MA HILTI GF",  "HILTI"),
            ("IN450348",   "MARTELO SDS PLUS C/ PUNHO",                             ""),
            ("INMRM158",   "MARTELO ROMPEDOR MMR1700 45J 12KG MONO 220V 60HZ",     "MENEGOTTI"),
            ("IN580121",   "MISTURADOR DE ARGAMASSA MAV1600 220V",                  "MENEGOTTI"),
            ("IN580039",   "MISTURADOR DE ARGAMASSA MAV1600 220V",                  "MENEGOTTI"),
            ("INGFH003",   "PISTOLA DE FIXACAO A BATERIA BX 3-L A22MA HILTI GF",   "HILTI"),
            ("INEAG119",   'ESMERILHADEIRA ANGULAR 5" C/ ACESSORIOS',               "MAKITA"),
            ("IN270013",   "FURADEIRA 3/4 FUR3/4P",                                 ""),
            ("IN270028",   "FURADEIRA 3/4 FUR3/4P",                                 ""),
            ("IN270004",   "FURADEIRA 3/4 FUR3/4P",                                 ""),
            ("IN270005",   "FURADEIRA 3/4 FUR3/4P",                                 ""),
            ("INFPB017",   "FURADEIRA E PARAFUSADEIRA A BATERIA MFI-20 127/220V",   "MENEGOTTI"),
            ("IN340056",   "LAVA JATO HD 585 PROFISSIONAL MODELO 585",              ""),
            ("INMSV108",   "MARTELETE PERF/ROMP MPV1500 5,5J 220V",                 "VONDER"),
            ("INHT930012", "KIT ASPIRADOR UNIV. HILTI / POLIDORA DE BETAO HILTI",   "HILTI"),
            ("INMSV261",   "MARTELETE PERF/ROMP MPV1500 5,5J 220V",                 "VONDER"),
            ("INMSV269",   "MARTELETE PERF/ROMP MPV1500 5,5J 220V",                 "VONDER"),
            ("IN240093",   'ESMERILHADEIRA ANGULAR 7" 220V',                         ""),
            ("INMSV256",   "MARTELETE PERF/ROMP MPV1500 5,5J 220V",                 "VONDER"),
            ("INMSV257",   "MARTELETE PERF/ROMP MPV1500 5,5J 220V",                 "VONDER"),
            ("INMSU042",   "MISTURADOR ELETRICO MEL1600 MONO 220V 60HZ 1600W",      "MENEGOTTI"),
            ("INSER830025",'SERRA CIRCULAR 7"',                                      ""),
            ("INEAG243",   'ESMERILHADEIRA ANGULAR 5" C/ ACESSORIOS',               "MAKITA"),
            ("INEAG233",   'ESMERILHADEIRA ANGULAR 5" C/ ACESSORIOS',               "MAKITA"),
            ("INEAG236",   'ESMERILHADEIRA ANGULAR 5" C/ ACESSORIOS',               "MAKITA"),
            ("INMRM149",   "MARTELO ROMPEDOR MMR1700 45J 12KG MONO 220V 60HZ",     "MENEGOTTI"),
            ("INMSV069",   "MARTELETE PERF/ROMP MPV1500 5,5J 220V",                 "VONDER"),
            ("INMSV156",   "MARTELETE PERF/ROMP MPV1500 5,5J 220V",                 "VONDER"),
            ("IN620015",   "KIT NIVELADOR A LASER HILTI SKR200",                    "HILTI"),
            ("INMSV067",   "MARTELETE PERF/ROMP MPV1500 5,5J 220V",                 "VONDER"),
            ("INMSV001",   "MARTELETE PERF/ROMP MPV1500 5,5J 220V",                 "VONDER"),
            ("INMSV099",   "MARTELETE PERF/ROMP MPV1500 5,5J 220V",                 "VONDER"),
            ("INMSV273",   "MARTELETE PERF/ROMP MPV1500 5,5J 220V",                 "VONDER"),
            ("INHT930015", "KIT ASPIRADOR UNIV. HILTI / POLIDORA DE BETAO HILTI",   "HILTI"),
            ("INMSV024",   "MARTELETE PERF/ROMP MPV1500 5,5J 220V",                 "VONDER"),
            ("INMSV113",   "MARTELETE PERF/ROMP MPV1500 5,5J 220V",                 "VONDER"),
            ("INMSV131",   "MARTELETE PERF/ROMP MPV1500 5,5J 220V",                 "VONDER"),
            ("INMSV278",   "MARTELETE PERF/ROMP MPV1500 5,5J 220V",                 "VONDER"),
            ("INMSV148",   "MARTELETE PERF/ROMP MPV1500 5,5J 220V",                 "VONDER"),
            ("INHT930010", "KIT ASPIRADOR UNIV. HILTI / POLIDORA DE BETAO HILTI",   "HILTI"),
            ("INHT930011", "KIT ASPIRADOR UNIV. HILTI / POLIDORA DE BETAO HILTI",   "HILTI"),
            ("INMSV090",   "MARTELETE PERF/ROMP MPV1500 5,5J 220V",                 "VONDER"),
            ("INMSV281",   "MARTELETE PERF/ROMP MPV1500 5,5J 220V",                 "VONDER"),
            ("INMSV260",   "MARTELETE PERF/ROMP MPV1500 5,5J 220V",                 "VONDER"),
            ("IN850474",   "SERRA MARMORE C/ CHAVE",                                 ""),
        ]
        inseridas = 0
        for idf, nome, empresa in ferramentas_lista:
            if not Ferramenta.query.filter_by(identificacao=idf, ativo=True).first():
                db.session.add(Ferramenta(
                    identificacao=idf,
                    nome=nome,
                    empresa=empresa or None,
                    almoxarifado_id=alm.id,
                    status='disponivel'
                ))
                inseridas += 1
        if inseridas:
            db.session.commit()
            logger.info(f'SEED FERRAMENTAS ESTRUTURA: {inseridas} ferramentas cadastradas.')
    except Exception as e:
        logger.error(f'SEED FERRAMENTAS ESTRUTURA: erro — {e}')
        db.session.rollback()


def inicializar_banco():
    """Roda migrações, cria tabelas e seed — executado uma única vez."""
    try:
        db.create_all()
        run_migrations()
        seed_data()
        classificar_categorias_itens()
        migrar_itens_para_epi()
        seed_ferramentas_estrutura_auto()
    except Exception as e:
        logger.error(f'Inicialização do banco: {e}')

# Inicializa na primeira requisição para não bloquear o boot do gunicorn
_banco_inicializado = False

@app.before_request
def init_on_first_request():
    global _banco_inicializado
    if not _banco_inicializado:
        _banco_inicializado = True
        try:
            inicializar_banco()
        except Exception as e:
            logger.error(f'Erro na inicialização: {e}')

if __name__ == '__main__':
    port = int(os.environ.get('PORT', 5000))
    app.run(debug=False, host='0.0.0.0', port=port)