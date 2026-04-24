import sqlite3

conn = sqlite3.connect("../instance/estoque.db")
cursor = conn.cursor()

# Ver todas as tabelas
cursor.execute("SELECT name FROM sqlite_master WHERE type='table';")
tabelas = cursor.fetchall()
print("Tabelas no banco:")
for tabela in tabelas:
    print(f"- {tabela[0]}")

# Ver dados de cada tabela
for tabela in tabelas:
    nome = tabela[0]
    cursor.execute(f"SELECT COUNT(*) FROM {nome}")
    count = cursor.fetchone()[0]
    print(f"\nTabela {nome}: {count} registros")
    
    if count > 0 and count < 10:  # Mostrar dados se tiver poucos registros
        cursor.execute(f"SELECT * FROM {nome} LIMIT 3")
        rows = cursor.fetchall()
        for row in rows:
            print(f"  {row}")

conn.close()