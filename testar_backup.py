"""Simula o envio de backup sem enviar email — mostra quem receberia o quê."""
import os, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from app import app, db, Usuario, Almoxarifado

emails_fixos = [
    'simao.reis@stanza.com.br', 'bianca.melo@stanza.com.br',
    'deyvid.lopes@stanza.com.br', 'henrique.silva@stanza.com.br',
    'ariel.apolonio@stanza.com.br', 'alisson.guimaraes@stanza.com.br',
    'laura.santos@stanza.com.br', 'alanderson.santos@stanza.com.br',
    'rickgouveia157@gmail.com',
]

with app.app_context():
    print('\n' + '='*60)
    print('SIMULAÇÃO DE ENVIO DE BACKUP')
    print('='*60)

    # 1. Admins + emails fixos → backup completo
    emails_admin = [u.email for u in Usuario.query.filter_by(perfil='admin', ativo=True).all() if u.email]
    dest_completo = list(set(emails_admin + emails_fixos))
    print(f'\n📦 BACKUP COMPLETO (todos os almoxarifados):')
    for e in sorted(dest_completo):
        print(f'   → {e}')

    # 2. Almoxarifes → só o seu
    print(f'\n🏗️  BACKUP POR ALMOXARIFADO (almoxarifes):')
    for alm in Almoxarifado.query.all():
        dest_alm = [u.email for u in alm.usuarios
                    if u.perfil == 'almoxarife' and u.ativo and u.email
                    and u.email not in dest_completo]
        if dest_alm:
            print(f'   {alm.nome}:')
            for e in dest_alm:
                print(f'      → {e}')
        else:
            print(f'   {alm.nome}: (nenhum almoxarife com email fora da lista completa)')

    # 3. Engenheiros → almoxarifado vinculado
    print(f'\n👔 BACKUP POR ALMOXARIFADO (engenheiros):')
    for u in Usuario.query.filter(
        Usuario.perfil == 'colaborador',
        Usuario.ativo == True,
        Usuario.email != None,
        Usuario.almoxarifado_id != None
    ).all():
        if u.email not in dest_completo:
            print(f'   {u.nome} ({u.email}) → {u.almoxarifado.nome if u.almoxarifado else "sem almoxarifado"}')

    print('\n' + '='*60)
    print('FIM DA SIMULAÇÃO')
    print('='*60 + '\n')
