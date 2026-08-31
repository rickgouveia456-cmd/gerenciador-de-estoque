-- Migrações incrementais — executadas após 01_dados.sql
-- Adiciona colunas novas que podem não existir em bancos antigos

ALTER TABLE almoxarifado ADD COLUMN IF NOT EXISTS regiao VARCHAR(100) NULL;
ALTER TABLE usuario      ADD COLUMN IF NOT EXISTS regiao VARCHAR(100) NULL;
