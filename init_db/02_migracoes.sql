-- Migrações incrementais — executadas após 01_dados.sql
-- Adiciona colunas novas que podem não existir em bancos antigos

ALTER TABLE almoxarifado ADD COLUMN IF NOT EXISTS regiao VARCHAR(100) NULL;
ALTER TABLE usuario      ADD COLUMN IF NOT EXISTS regiao VARCHAR(100) NULL;

-- Corrige status parcial em requisicao_mestre (bug silencioso)
ALTER TABLE requisicao_mestre MODIFY COLUMN status ENUM('pendente','aprovada','parcial','recusada','entregue','cancelada') DEFAULT 'pendente';

-- Controle de validade de EPI (prazo nov/2026)
ALTER TABLE item_epi ADD COLUMN IF NOT EXISTS data_fabricacao DATE NULL;
ALTER TABLE item_epi ADD COLUMN IF NOT EXISTS data_validade DATE NULL;
ALTER TABLE item_epi ADD COLUMN IF NOT EXISTS vida_util_meses INT NULL;

-- Tabela de log de integracao com sistemas externos
CREATE TABLE IF NOT EXISTS `integracao_log` (
  `id`           INT NOT NULL AUTO_INCREMENT,
  `sistema`      VARCHAR(50) NOT NULL,
  `operacao`     VARCHAR(100) NOT NULL,
  `status`       ENUM('sucesso','erro','pendente') NOT NULL DEFAULT 'pendente',
  `payload`      LONGTEXT NULL,
  `resposta`     LONGTEXT NULL,
  `erro_msg`     VARCHAR(500) NULL,
  `criado_em`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `concluido_em` DATETIME NULL,
  `usuario_id`   INT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sistema_status` (`sistema`,`status`),
  KEY `idx_criado_em` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de configuração da integração Sienge
CREATE TABLE IF NOT EXISTS `integracao_config` (
  `id`         INT NOT NULL AUTO_INCREMENT,
  `sistema`    VARCHAR(50) NOT NULL UNIQUE,
  `ativo`      TINYINT(1) NOT NULL DEFAULT 0,
  `config_json` LONGTEXT NULL,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: registro inicial do Sienge (desativado por padrão)
INSERT IGNORE INTO `integracao_config` (`sistema`,`ativo`,`config_json`) 
VALUES ('sienge', 0, '{"base_url":"","token":"","empresa_id":"","sync_materiais":false,"sync_requisicoes":false,"sync_estoques":false}');
