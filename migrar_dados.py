"""
Script para migrar dados do SQLite local para o Supabase (PostgreSQL).
Execute: python migrar_dados.py
"""
import sqlite3
import psycopg2
import sys

# URL do Supabase — conexão direta funcionando
SUPABASE_URL = "postgresql://postgres:fS4Cfb+JZ&385_X@db.jyllxxkyhcjnrbunsswi.supabase.co:5432/postgres"

SQLITE_PATH = "../instance/estoque.db"

def migrar():
    print("Conectando ao SQLite local...")
    sqlite = sqlite3.connect(SQLITE_PATH)
    sqlite.row_factory = sqlite3.Row
    sc = sqlite.cursor()

    print("Conectando ao Supabase...")
    pg = psycopg2.connect(SUPABASE_URL)
    pc = pg.cursor()

    # Criar tabelas no PostgreSQL
    print("Criando tabelas...")
    pc.execute("""
        CREATE TABLE IF NOT EXISTS almoxarifado (
            id SERIAL PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            descricao VARCHAR(200)
        )
    """)
    pc.execute("""
        CREATE TABLE IF NOT EXISTS usuario (
            id SERIAL PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            login VARCHAR(50) UNIQUE NOT NULL,
            senha_hash VARCHAR(256) NOT NULL,
            perfil VARCHAR(20) DEFAULT 'colaborador',
            almoxarifado_id INTEGER REFERENCES almoxarifado(id),
            ativo BOOLEAN DEFAULT TRUE
        )
    """)
    pc.execute("""
        CREATE TABLE IF NOT EXISTS item (
            id SERIAL PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            codigo VARCHAR(50) UNIQUE NOT NULL,
            unidade VARCHAR(20) NOT NULL,
            quantidade FLOAT DEFAULT 0,
            estoque_minimo FLOAT DEFAULT 0,
            almoxarifado_id INTEGER NOT NULL REFERENCES almoxarifado(id),
            status_compra VARCHAR(30) DEFAULT 'pendente',
            fixado BOOLEAN DEFAULT FALSE
        )
    """)
    pc.execute("""
        CREATE TABLE IF NOT EXISTS movimentacao (
            id SERIAL PRIMARY KEY,
            tipo VARCHAR(10) NOT NULL,
            quantidade FLOAT NOT NULL,
            responsavel VARCHAR(100),
            observacao VARCHAR(200),
            data TIMESTAMP DEFAULT NOW(),
            item_id INTEGER NOT NULL REFERENCES item(id)
        )
    """)
    pc.execute("""
        CREATE TABLE IF NOT EXISTS requisicao (
            id SERIAL PRIMARY KEY,
            colaborador VARCHAR(100) NOT NULL,
            observacao VARCHAR(200),
            quantidade FLOAT NOT NULL,
            status VARCHAR(20) DEFAULT 'aberta',
            data_retirada TIMESTAMP DEFAULT NOW(),
            data_devolucao TIMESTAMP,
            item_id INTEGER NOT NULL REFERENCES item(id)
        )
    """)
    pc.execute("""
        CREATE TABLE IF NOT EXISTS acesso_extra (
            id SERIAL PRIMARY KEY,
            usuario_id INTEGER NOT NULL REFERENCES usuario(id),
            almoxarifado_id INTEGER NOT NULL REFERENCES almoxarifado(id),
            motivo VARCHAR(200),
            data_inicio TIMESTAMP DEFAULT NOW(),
            data_fim TIMESTAMP,
            concedido_por VARCHAR(100)
        )
    """)
    pg.commit()

    # Migrar almoxarifados
    sc.execute("SELECT * FROM almoxarifado")
    rows = sc.fetchall()
    print(f"Migrando {len(rows)} almoxarifados...")
    for r in rows:
        pc.execute("INSERT INTO almoxarifado (id, nome, descricao) VALUES (%s, %s, %s) ON CONFLICT (id) DO NOTHING",
                   (r['id'], r['nome'], r['descricao']))
    if rows:
        pc.execute(f"SELECT setval('almoxarifado_id_seq', {max(r['id'] for r in rows)})")
    pg.commit()

    # Migrar usuários
    sc.execute("SELECT * FROM usuario")
    rows = sc.fetchall()
    print(f"Migrando {len(rows)} usuários...")
    for r in rows:
        pc.execute("""INSERT INTO usuario (id, nome, login, senha_hash, perfil, almoxarifado_id, ativo)
                      VALUES (%s, %s, %s, %s, %s, %s, %s) ON CONFLICT (id) DO NOTHING""",
                   (r['id'], r['nome'], r['login'], r['senha_hash'], r['perfil'],
                    r['almoxarifado_id'], bool(r['ativo'])))
    if rows:
        pc.execute(f"SELECT setval('usuario_id_seq', {max(r['id'] for r in rows)})")
    pg.commit()

    # Migrar itens
    sc.execute("SELECT * FROM item")
    rows = sc.fetchall()
    print(f"Migrando {len(rows)} itens...")
    for r in rows:
        fixado = bool(r['fixado']) if 'fixado' in r.keys() else False
        status_compra = r['status_compra'] if 'status_compra' in r.keys() else 'pendente'
        pc.execute("""INSERT INTO item (id, nome, codigo, unidade, quantidade, estoque_minimo,
                      almoxarifado_id, status_compra, fixado)
                      VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s) ON CONFLICT (id) DO NOTHING""",
                   (r['id'], r['nome'], r['codigo'], r['unidade'], r['quantidade'],
                    r['estoque_minimo'], r['almoxarifado_id'], status_compra, fixado))
    if rows:
        pc.execute(f"SELECT setval('item_id_seq', {max(r['id'] for r in rows)})")
    pg.commit()

    # Migrar movimentações
    sc.execute("SELECT * FROM movimentacao")
    rows = sc.fetchall()
    print(f"Migrando {len(rows)} movimentações...")
    for r in rows:
        pc.execute("""INSERT INTO movimentacao (id, tipo, quantidade, responsavel, observacao, data, item_id)
                      VALUES (%s, %s, %s, %s, %s, %s, %s) ON CONFLICT (id) DO NOTHING""",
                   (r['id'], r['tipo'], r['quantidade'], r['responsavel'],
                    r['observacao'], r['data'], r['item_id']))
    if rows:
        pc.execute(f"SELECT setval('movimentacao_id_seq', {max(r['id'] for r in rows)})")
    pg.commit()

    # Migrar requisições
    sc.execute("SELECT * FROM requisicao")
    rows = sc.fetchall()
    print(f"Migrando {len(rows)} requisições...")
    for r in rows:
        pc.execute("""INSERT INTO requisicao (id, colaborador, observacao, quantidade, status,
                      data_retirada, data_devolucao, item_id)
                      VALUES (%s, %s, %s, %s, %s, %s, %s, %s) ON CONFLICT (id) DO NOTHING""",
                   (r['id'], r['colaborador'], r['observacao'], r['quantidade'],
                    r['status'], r['data_retirada'], r['data_devolucao'], r['item_id']))
    if rows:
        pc.execute(f"SELECT setval('requisicao_id_seq', {max(r['id'] for r in rows)})")
    pg.commit()

    sqlite.close()
    pg.close()
    print("\n✅ Migração concluída com sucesso!")

if __name__ == '__main__':
    migrar()
