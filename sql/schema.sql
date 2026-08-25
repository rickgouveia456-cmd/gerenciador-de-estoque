-- ============================================================
-- Logi-Prime — Schema MySQL 8.0
-- Migrado do SQLAlchemy/SQLite (Python/Flask)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- almoxarifado
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS almoxarifado (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(100) NOT NULL,
    descricao   VARCHAR(200),
    obra        VARCHAR(100),
    cidade      VARCHAR(100),
    regiao      VARCHAR(100),
    criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- usuario
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuario (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome              VARCHAR(100) NOT NULL,
    login             VARCHAR(50)  NOT NULL UNIQUE,
    senha_hash        VARCHAR(256) NOT NULL,
    perfil            ENUM('admin','almoxarife','analista','mestre','tecnico_seguranca','colaborador') DEFAULT 'colaborador',
    almoxarifado_id   INT UNSIGNED,
    escopo            VARCHAR(50),
    email             VARCHAR(120),
    ativo             TINYINT(1) DEFAULT 1,
    totp_secret       VARCHAR(32),
    pode_requisitar   TINYINT(1) DEFAULT 0,
    pode_ver_alertas  TINYINT(1) DEFAULT 0,
    regiao            VARCHAR(100),
    criado_em         DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (almoxarifado_id) REFERENCES almoxarifado(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- acesso_extra
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS acesso_extra (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id       INT UNSIGNED NOT NULL,
    almoxarifado_id  INT UNSIGNED NOT NULL,
    motivo           VARCHAR(200),
    data_inicio      DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_fim         DATETIME,
    concedido_por    VARCHAR(100),
    FOREIGN KEY (usuario_id)      REFERENCES usuario(id)      ON DELETE CASCADE,
    FOREIGN KEY (almoxarifado_id) REFERENCES almoxarifado(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- permissao_extra
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS permissao_extra (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id     INT UNSIGNED NOT NULL,
    permissao      VARCHAR(50)  NOT NULL,
    concedido_por  VARCHAR(100),
    data_concessao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- item
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS item (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome             VARCHAR(300) NOT NULL,
    codigo           VARCHAR(50)  NOT NULL,
    unidade          VARCHAR(20)  NOT NULL,
    quantidade       DECIMAL(12,4) DEFAULT 0,
    estoque_minimo   DECIMAL(12,4) DEFAULT 0,
    almoxarifado_id  INT UNSIGNED  NOT NULL,
    status_compra    VARCHAR(30)   DEFAULT 'pendente',
    fixado           TINYINT(1)    DEFAULT 0,
    ativo            TINYINT(1)    DEFAULT 1,
    categoria        VARCHAR(30)   DEFAULT 'geral',
    ca               VARCHAR(20),
    valor_unitario   DECIMAL(12,4),
    criado_em        DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (almoxarifado_id) REFERENCES almoxarifado(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- movimentacao
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS movimentacao (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo         ENUM('entrada','saida') NOT NULL,
    quantidade   DECIMAL(12,4) NOT NULL,
    responsavel  VARCHAR(100),
    observacao   VARCHAR(200),
    data         DATETIME DEFAULT CURRENT_TIMESTAMP,
    item_id      INT UNSIGNED NOT NULL,
    devolvido    TINYINT(1),
    foto_url     TEXT,
    FOREIGN KEY (item_id) REFERENCES item(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- requisicao (simples — item individual)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS requisicao (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colaborador     VARCHAR(100) NOT NULL,
    observacao      VARCHAR(200),
    quantidade      DECIMAL(12,4) NOT NULL,
    status          ENUM('aberta','devolvida','cancelada') DEFAULT 'aberta',
    data_retirada   DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_devolucao  DATETIME,
    item_id         INT UNSIGNED NOT NULL,
    FOREIGN KEY (item_id) REFERENCES item(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- requisicao_mestre (requisicao de obra / mestre)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS requisicao_mestre (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    protocolo        VARCHAR(30) UNIQUE,
    mestre_id        INT UNSIGNED NOT NULL,
    colaborador      VARCHAR(100) NOT NULL,
    almoxarifado_id  INT UNSIGNED NOT NULL,
    observacao       VARCHAR(300),
    status           ENUM('pendente','aprovada','recusada','entregue') DEFAULT 'pendente',
    data_criacao     DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_entrega     DATETIME,
    entregue_por_id  INT UNSIGNED,
    foto_url         TEXT,
    FOREIGN KEY (mestre_id)       REFERENCES usuario(id)      ON DELETE RESTRICT,
    FOREIGN KEY (almoxarifado_id) REFERENCES almoxarifado(id) ON DELETE CASCADE,
    FOREIGN KEY (entregue_por_id) REFERENCES usuario(id)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- requisicao_mestre_item
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS requisicao_mestre_item (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requisicao_id   INT UNSIGNED NOT NULL,
    item_id         INT UNSIGNED NOT NULL,
    quantidade      DECIMAL(12,4) NOT NULL,
    observacao      VARCHAR(200),
    status_item     ENUM('pendente','aprovado','recusado') DEFAULT 'pendente',
    motivo_recusa   VARCHAR(200),
    FOREIGN KEY (requisicao_id) REFERENCES requisicao_mestre(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id)       REFERENCES item(id)               ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- colaborador
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS colaborador (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome           VARCHAR(100) NOT NULL,
    funcao         VARCHAR(50),
    escopo         VARCHAR(50),
    obra           VARCHAR(100),
    cidade         VARCHAR(100),
    tipo           VARCHAR(30)  DEFAULT 'peao',
    ativo          TINYINT(1)   DEFAULT 1,
    data_cadastro  DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- ferramenta
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ferramenta (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identificacao       VARCHAR(50)  NOT NULL,
    nome                VARCHAR(200) NOT NULL,
    empresa             VARCHAR(100),
    almoxarifado_id     INT UNSIGNED NOT NULL,
    status              ENUM('disponivel','em_uso','manutencao','perdida') DEFAULT 'disponivel',
    responsavel_atual   VARCHAR(100),
    data_saida          DATETIME,
    observacao          VARCHAR(200),
    local               VARCHAR(100),
    ativo               TINYINT(1) DEFAULT 1,
    data_cadastro       DATETIME   DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (almoxarifado_id) REFERENCES almoxarifado(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- historico_ferramenta
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS historico_ferramenta (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ferramenta_id    INT UNSIGNED NOT NULL,
    colaborador      VARCHAR(100) NOT NULL,
    data_saida       DATETIME     NOT NULL,
    data_devolucao   DATETIME,
    registrado_por   VARCHAR(100),
    tipo_evento      ENUM('uso','manutencao') DEFAULT 'uso',
    motivo_manutencao VARCHAR(300),
    foto_url         TEXT,
    FOREIGN KEY (ferramenta_id) REFERENCES ferramenta(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- item_epi
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS item_epi (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identificacao       VARCHAR(50)  NOT NULL,
    nome                VARCHAR(200) NOT NULL,
    tamanho             VARCHAR(30),
    almoxarifado_id     INT UNSIGNED NOT NULL,
    status              ENUM('disponivel','em_uso','manutencao','perdido') DEFAULT 'disponivel',
    responsavel_atual   VARCHAR(100),
    quantidade          INT DEFAULT 1,
    local               VARCHAR(100),
    observacao          VARCHAR(200),
    ativo               TINYINT(1) DEFAULT 1,
    data_cadastro       DATETIME   DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (almoxarifado_id) REFERENCES almoxarifado(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- historico_epi
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS historico_epi (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_epi_id      INT UNSIGNED NOT NULL,
    colaborador      VARCHAR(100) NOT NULL,
    data_saida       DATETIME     NOT NULL,
    data_devolucao   DATETIME,
    registrado_por   VARCHAR(100),
    tipo_evento      ENUM('uso','manutencao') DEFAULT 'uso',
    motivo_manutencao VARCHAR(300),
    foto_url         TEXT,
    FOREIGN KEY (item_epi_id) REFERENCES item_epi(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- catalogo_insumo
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS catalogo_insumo (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome           VARCHAR(300) NOT NULL,
    codigo_ref     VARCHAR(50),
    unidade        VARCHAR(20)  NOT NULL,
    categoria      VARCHAR(30)  DEFAULT 'geral',
    ca             VARCHAR(20),
    descricao      VARCHAR(500),
    ativo          TINYINT(1)   DEFAULT 1,
    data_cadastro  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    valor_unitario DECIMAL(12,4),
    criado_por     VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- kit (conjunto de itens predefinidos para requisicao rapida)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS kit (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(100) NOT NULL,
    descricao  VARCHAR(300),
    ativo      TINYINT(1) DEFAULT 1,
    criado_em  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kit_item (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kit_id     INT UNSIGNED NOT NULL,
    item_id    INT UNSIGNED NOT NULL,
    quantidade DECIMAL(12,4) NOT NULL DEFAULT 1,
    FOREIGN KEY (kit_id)  REFERENCES kit(id)  ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES item(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Modulo EPI: fichas, itens ficha, matriz, habilitacoes
CREATE TABLE IF NOT EXISTS ficha_epi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colaborador VARCHAR(100) NOT NULL,
    funcao VARCHAR(80),
    obra VARCHAR(100),
    almoxarifado_id INT UNSIGNED,
    status ENUM('ativa','encerrada') DEFAULT 'ativa',
    data_abertura DATE DEFAULT (CURDATE()),
    data_encerramento DATE,
    criado_por VARCHAR(100),
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (almoxarifado_id) REFERENCES almoxarifado(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS item_ficha_epi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ficha_id INT UNSIGNED NOT NULL,
    descricao VARCHAR(200) NOT NULL,
    ca VARCHAR(30),
    quantidade DECIMAL(8,2) DEFAULT 1,
    tamanho VARCHAR(30),
    data_entrega DATE,
    data_devolucao DATE,
    FOREIGN KEY (ficha_id) REFERENCES ficha_epi(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS matriz_epi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    funcao VARCHAR(80) NOT NULL,
    obra VARCHAR(100),
    norma VARCHAR(30),
    epis_json TEXT,
    criado_por VARCHAR(100),
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS habilitacao_funcionario (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colaborador VARCHAR(100) NOT NULL,
    tipo VARCHAR(100) NOT NULL,
    descricao VARCHAR(200),
    validade DATE,
    almoxarifado_id INT UNSIGNED,
    criado_por VARCHAR(100),
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (almoxarifado_id) REFERENCES almoxarifado(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;