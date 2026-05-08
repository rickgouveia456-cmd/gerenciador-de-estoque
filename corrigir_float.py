"""
Corrige valores de quantidade com erro de ponto flutuante no banco.
Arredonda todos os valores de quantidade para 4 casas decimais.
Execute uma vez: python corrigir_float.py
"""
import os
from sqlalchemy import create_engine, text

DATABASE_URL = os.environ.get('DATABASE_URL')
if not DATABASE_URL:
    # Tenta banco local
    DATABASE_URL = 'sqlite:///instance/estoque.db'

if DATABASE_URL.startswith('postgres://'):
    DATABASE_URL = DATABASE_URL.replace('postgres://', 'postgresql://', 1)

engine = create_engine(DATABASE_URL)

with engine.connect() as conn:
    # Busca todos os itens com quantidade que tem mais de 4 casas decimais
    itens = conn.execute(text("""
        SELECT id, nome, codigo, quantidade
        FROM item
        WHERE quantidade != ROUND(quantidade, 4)
    """)).fetchall()

    if not itens:
        print("✅ Nenhum item com erro de ponto flutuante encontrado.")
    else:
        print(f"🔧 {len(itens)} item(ns) com erro de ponto flutuante:\n")
        for item in itens:
            item_id, nome, codigo, qtd = item
            qtd_corrigida = round(float(qtd), 4)
            print(f"  [{codigo}] {nome}: {qtd} → {qtd_corrigida}")
            conn.execute(text(
                "UPDATE item SET quantidade = :qtd WHERE id = :id"
            ), {"qtd": qtd_corrigida, "id": item_id})

        conn.commit()
        print(f"\n✅ {len(itens)} item(ns) corrigido(s) com sucesso!")

    # Mostra todos os itens com quantidade anormal (> 10x a média)
    print("\n📊 Verificando quantidades anormais...")
    suspeitos = conn.execute(text("""
        SELECT id, nome, codigo, quantidade, unidade
        FROM item
        WHERE quantidade > 50000
        ORDER BY quantidade DESC
    """)).fetchall()

    if suspeitos:
        print(f"\n⚠️  {len(suspeitos)} item(ns) com quantidade acima de 50.000:\n")
        for s in suspeitos:
            print(f"  [{s[2]}] {s[1]}: {s[3]} {s[4]}")
        print("\nVerifique se esses valores estão corretos.")
    else:
        print("✅ Nenhuma quantidade suspeita encontrada.")
