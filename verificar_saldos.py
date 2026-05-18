"""
Verifica se o saldo atual de cada item bate com o histórico de movimentações.
Usa uma única query SQL para ser rápido.
"""
import os
from sqlalchemy import create_engine, text

DATABASE_URL = os.environ.get('DATABASE_URL')
if not DATABASE_URL:
    print("Configure DATABASE_URL")
    exit(1)
if DATABASE_URL.startswith('postgres://'):
    DATABASE_URL = DATABASE_URL.replace('postgres://', 'postgresql://', 1)

engine = create_engine(DATABASE_URL)

with engine.connect() as conn:
    # Uma única query que calcula o saldo pelo histórico e compara com o atual
    resultado = conn.execute(text("""
        SELECT
            i.id,
            i.nome,
            i.codigo,
            i.quantidade AS saldo_atual,
            i.unidade,
            a.nome AS almoxarifado,
            COALESCE(SUM(CASE WHEN m.tipo = 'entrada' THEN m.quantidade
                              WHEN m.tipo = 'saida'   THEN -m.quantidade
                              ELSE 0 END), 0) AS saldo_calculado,
            i.quantidade - COALESCE(SUM(CASE WHEN m.tipo = 'entrada' THEN m.quantidade
                                             WHEN m.tipo = 'saida'   THEN -m.quantidade
                                             ELSE 0 END), 0) AS diferenca
        FROM item i
        JOIN almoxarifado a ON a.id = i.almoxarifado_id
        LEFT JOIN movimentacao m ON m.item_id = i.id
        GROUP BY i.id, i.nome, i.codigo, i.quantidade, i.unidade, a.nome
        HAVING ABS(i.quantidade - COALESCE(SUM(CASE WHEN m.tipo = 'entrada' THEN m.quantidade
                                                    WHEN m.tipo = 'saida'   THEN -m.quantidade
                                                    ELSE 0 END), 0)) > 0.01
        ORDER BY ABS(i.quantidade - COALESCE(SUM(CASE WHEN m.tipo = 'entrada' THEN m.quantidade
                                                      WHEN m.tipo = 'saida'   THEN -m.quantidade
                                                      ELSE 0 END), 0)) DESC
    """)).fetchall()

    total = conn.execute(text("SELECT COUNT(*) FROM item")).scalar()

    print(f"\n{'='*100}")
    print(f"VERIFICAÇÃO DE SALDOS — {total} itens analisados")
    print(f"{'='*100}\n")

    if not resultado:
        print("✅ Todos os saldos estão corretos! Nenhuma divergência encontrada.")
    else:
        print(f"❌ {len(resultado)} item(ns) com DIVERGÊNCIA:\n")
        print(f"{'Almoxarifado':<35} {'Código':<12} {'Item':<35} {'Atual':>10} {'Calculado':>10} {'Diferença':>10} Un")
        print(f"{'-'*115}")
        for r in resultado:
            item_id, nome, codigo, saldo_atual, unidade, alm, saldo_calc, diff = r
            sinal = "+" if diff > 0 else ""
            print(f"{alm:<35} {codigo:<12} {nome[:35]:<35} {saldo_atual:>10.1f} {saldo_calc:>10.1f} {sinal}{diff:>9.1f} {unidade}")

        print(f"\n{'='*100}")
        print(f"RESUMO: {total - len(resultado)} OK | {len(resultado)} com divergência")
        print(f"\nDica: Diferença positiva = saldo atual MAIOR que o histórico")
        print(f"      Diferença negativa = saldo atual MENOR que o histórico")
