#!/usr/bin/env python3
"""Script de diagnóstico para verificar variáveis de ambiente no Railway."""
import os
import sys

print('=' * 80)
print('DIAGNÓSTICO DE VARIÁVEIS DE AMBIENTE - Railway')
print('=' * 80)

# Variáveis esperadas
variaveis_esperadas = {
    'BACKUP_EMAIL_FROM': 'Email remetente para backups',
    'BACKUP_EMAIL_PASS': 'Senha de app do Gmail',
    'BACKUP_EMAIL_TO': 'Email destinatário fixo',
    'DATABASE_URL': 'URL do banco de dados PostgreSQL',
    'SECRET_KEY': 'Chave secreta do Flask',
    'PORT': 'Porta do servidor'
}

problemas = []

for var, descricao in variaveis_esperadas.items():
    valor = os.environ.get(var, '')
    if valor:
        if 'PASS' in var or 'SECRET' in var:
            # Não exibe senhas completas
            preview = valor[:3] + '***' + valor[-3:] if len(valor) > 6 else '***'
            print(f'✅ {var:20} = {preview:30} ({descricao})')
        else:
            print(f'✅ {var:20} = {valor:30} ({descricao})')
    else:
        print(f'❌ {var:20} = NÃO DEFINIDO              ({descricao})')
        problemas.append(var)

print('=' * 80)

if problemas:
    print(f'\n⚠️  ATENÇÃO: {len(problemas)} variável(is) não configurada(s):')
    for var in problemas:
        print(f'   - {var}')
    print('\nConfigure estas variáveis no Railway Dashboard:')
    print('   Settings → Variables → Add Variable')
    sys.exit(1)
else:
    print('\n✅ Todas as variáveis de ambiente estão configuradas corretamente!')
    sys.exit(0)
