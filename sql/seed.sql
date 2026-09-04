-- ============================================================
-- Logi-Prime — Seed inicial
-- ============================================================
SET NAMES utf8mb4;

-- Admin padrao: login=admin / senha=admin123
-- Hash gerado com password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO usuario (nome, login, senha_hash, perfil, ativo)
VALUES ('Administrador', 'admin',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'admin', 1)
ON DUPLICATE KEY UPDATE id=id;

-- Almoxarifados de exemplo
INSERT INTO almoxarifado (nome, descricao, obra, cidade) VALUES
    ('Estrutura',  'Almoxarifado de estrutura e forma', 'Obra Patamares', 'Salvador'),
    ('Acabamento', 'Almoxarifado de acabamento', 'Obra Patamares', 'Salvador'),
    ('Infraestrutura', 'Almoxarifado de infraestrutura e instalacoes', 'Obra Patamares', 'Salvador')
ON DUPLICATE KEY UPDATE id=id;
