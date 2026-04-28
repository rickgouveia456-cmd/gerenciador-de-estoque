#!/usr/bin/env python3
"""
Script para reativar todos os itens desativados no sistema de estoque.
Execute este script para resolver o problema dos itens que apareceram como "desativado".
"""

import os
import sys
from flask import Flask
from flask_sqlalchemy import SQLAlchemy

# Configurar o app Flask
app = Flask(__name__)
app.config['SQLALCHEMY_DATABASE_URI'] = (
    os.environ.get('DATABASE_URL') or
    os.environ.get('URI_DO_BANCO_DE_DADOS') or
    'sqlite:////app/instance/estoque.db'
)
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False

db = SQLAlchemy(app)

# Modelo Item simplificado
class Item(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    nome = db.Column(db.String(100), nullable=False)
    codigo = db.Column(db.String(50), unique=True, nullable=False)
    ativo = db.Column(db.Boolean, default=True)

def reativar_todos_itens():
    """Reativa todos os itens desativados no sistema."""
    with app.app_context():
        try:
            # Primeiro, adicionar a coluna ativo se não existir
            from sqlalchemy import text
            with db.engine.connect() as conn:
                try:
                    conn.execute(text("ALTER TABLE item ADD COLUMN ativo BOOLEAN DEFAULT 1"))
                    conn.commit()
                    print("✅ Coluna 'ativo' adicionada à tabela item")
                except Exception as e:
                    if "duplicate column name" in str(e).lower() or "already exists" in str(e).lower():
                        print("ℹ️  Coluna 'ativo' já existe")
                    else:
                        print(f"⚠️  Erro ao adicionar coluna: {e}")
                
                # Reativar todos os itens (definir ativo = 1 para todos)
                result = conn.execute(text("UPDATE item SET ativo = 1 WHERE ativo IS NULL OR ativo = 0"))
                conn.commit()
                
                # Contar quantos itens foram reativados
                count_result = conn.execute(text("SELECT COUNT(*) FROM item WHERE ativo = 1"))
                total_ativos = count_result.fetchone()[0]
                
                print(f"✅ Todos os itens foram reativados!")
                print(f"📊 Total de itens ativos: {total_ativos}")
                
                return True
                
        except Exception as e:
            print(f"❌ Erro ao reativar itens: {e}")
            return False

if __name__ == '__main__':
    print("🔧 Reativando todos os itens desativados...")
    print("=" * 50)
    
    sucesso = reativar_todos_itens()
    
    print("=" * 50)
    if sucesso:
        print("✅ CONCLUÍDO! Todos os itens foram reativados.")
        print("🌐 Acesse o sistema no Railway para verificar.")
        print("💡 Dica: Altere a senha padrão do admin (admin/admin123) por segurança.")
    else:
        print("❌ ERRO! Não foi possível reativar os itens.")
        print("🔍 Verifique os logs acima para mais detalhes.")