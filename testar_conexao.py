"""
Script para testar a conexão com o Supabase
"""
import psycopg2

# Diferentes formatos de URL para testar
urls = [
    "postgresql://postgres.jyllxxkyhcjnrbunsswi:fS4Cfb+JZ&385_X@aws-0-sa-east-1.pooler.supabase.com:6543/postgres",
    "postgresql://postgres:fS4Cfb+JZ&385_X@db.jyllxxkyhcjnrbunsswi.supabase.co:5432/postgres",
    "postgresql://postgres.jyllxxkyhcjnrbunsswi:fS4Cfb%2BJZ%26385_X@aws-0-sa-east-1.pooler.supabase.com:6543/postgres"
]

for i, url in enumerate(urls, 1):
    print(f"\nTestando URL {i}...")
    try:
        conn = psycopg2.connect(url)
        cursor = conn.cursor()
        cursor.execute("SELECT version();")
        result = cursor.fetchone()
        print(f"✅ Conexão OK! PostgreSQL: {result[0][:50]}...")
        conn.close()
        print(f"URL correta: {url}")
        break
    except Exception as e:
        print(f"❌ Erro: {e}")
else:
    print("\n❌ Nenhuma URL funcionou. Verifique as credenciais no Supabase.")