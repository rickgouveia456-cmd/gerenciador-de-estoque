"""Script para cadastrar colaboradores em massa no banco de dados."""
import os, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from app import app, db, Colaborador

colaboradores = [
    ("ARILSON DE JESUS SOUZA",          "Profissional", "estrutura"),
    ("ADSON MUNIZ",                      "Ajudante",     "estrutura"),
    ("MARCEL OLIVEIRA DA CONCEIÇÃO",     "Profissional", "estrutura"),
    ("ROBERT WILLIAM DA HORA DE JESUS",  "Profissional", "estrutura"),
    ("MATEUS SANTOS DE JESUS",           "Profissional", "estrutura"),
    ("ANIBAL SANTOS DANTAS",             "Profissional", "estrutura"),
    ("ROBERTO FELIX GONÇALVES",          "Profissional", "estrutura"),
    ("EDNALDO DOS SANTOS",               "Profissional", "estrutura"),
    ("ALEXSANDRO TELES DOS SANTOS",      "Profissional", "estrutura"),
    ("RUAN UITALO",                      "Ajudante",     "estrutura"),
    ("FELIPE MESSIAS",                   "Ajudante",     "estrutura"),
    ("ROQUE DOS SANTOS",                 "Profissional", "estrutura"),
    ("VALDOMIRO GOMES DE JESUS FILHO",   "Profissional", "estrutura"),
    ("VALMIR GOMES DE JESUS",            "Profissional", "estrutura"),
    ("TIAGO GOMES DOS SANTOS",           "Ajudante",     "estrutura"),
    ("ADRIANO SOUZA DOS SANTOS",         "Profissional", "estrutura"),
    ("RONALDO DA CUNHA SANTOS",          "Profissional", "estrutura"),
    ("EMERSON DE SANTANA ARAUJO",        "Profissional", "estrutura"),
    ("LUAN DOS SANTOS CARDOSO",          "Profissional", "estrutura"),
    ("FRANCISCO CARLOS DOS SANTOS FILHO","Profissional", "estrutura"),
    ("NILSON MATIAS DOS SANTOS",         "Profissional", "estrutura"),
    ("VINICIUS DANTAS DA SILVA",         "Profissional", "estrutura"),
    ("MAURICIO RAMON PINHEIRO MATOS",    "Ajudante",     "estrutura"),
    ("CARLOS ALBERTO BISPO DOS SANTOS",  "Ajudante",     "estrutura"),
    ("DIEGO LIMA SANTOS",                "Ajudante",     "estrutura"),
    ("AILTON DA SILVA",                  "Profissional", "estrutura"),
    ("EIDSON SILVA ROCHA",               "Ajudante",     "estrutura"),
    ("MARLEI ASSIS DE SOUZA",            "Profissional", "estrutura"),
    ("RICARDO VASQUES LEMOS LEONI",      "Profissional", "estrutura"),
    ("ROBISON SANTOS DA CONCEIÇÃO",      "Profissional", "estrutura"),
    ("LUIS SILVAN LOPES DOS SANTOS",     "Profissional", "estrutura"),
    ("JAIR CESAR BRITO RODRIGUES JUNIOR","Ajudante",     "estrutura"),
    ("ROBSON BISPO DOS SANTOS",          "Profissional", "estrutura"),
    ("EDVAN MACHADO SANTOS",             "Profissional", "estrutura"),
    ("LUIS ALBERTO MOREIRA DA SILVA",    "Ajudante",     "estrutura"),
    ("ISAAC GONÇALVES DA SILVA",         "Ajudante",     "estrutura"),
    ("ANTONIO MARCOS DA SILVA COSTA",    "Ajudante",     "estrutura"),
    ("DENAILTON LEITE DOS SANTOS",       "Ajudante",     "estrutura"),
    ("MARCIO DE JESUS DOS SANTOS",       "Profissional", "estrutura"),
    ("WEBER OLIVEIRA DA LUZ",            "Ajudante",     "estrutura"),
    ("RAFAEL DA SILVA BOMFIM",           "Ajudante",     "estrutura"),
    ("ANDERSON RODRIGUES DOS SANTOS",    "Profissional", "estrutura"),
    ("JAILTON RIBEIRO TOSTA",            "Profissional", "estrutura"),
    ("JOAO LUIS OLIVEIRA DA SILVA",      "Profissional", "estrutura"),
    ("ANDERSON SOUZA DE FRIAS",          "Profissional", "estrutura"),
    ("JOILSON DOS SANTOS",               "Profissional", "estrutura"),
    ("JOANDERSON ALMEIDA BISPO",         "Profissional", "estrutura"),
    ("ANTONIO CARLOS SANTOS SILVA",      "Profissional", "estrutura"),
    ("NAILTON CONCEIÇÃO DE SOUZA",       "Ajudante",     "estrutura"),
    ("LUCAS SILVA DOS REIS",             "Ajudante",     "estrutura"),
    ("ROBSON LIMA MACIEL",               "Profissional", "estrutura"),
    ("ATILA ALMEIDA SILVA SANTOS",       "Ajudante",     "estrutura"),
    ("JONAS DE SENA BARRETO",            "Ajudante",     "estrutura"),
    ("SAMUEL BISPO DOS SANTOS",          "Profissional", "estrutura"),
    ("GUILHERME SANTOS SAMPAIO",         "Ajudante",     "estrutura"),
    ("DANIEL SÃO PEDRO DOS SANTOS",      "Ajudante",     "estrutura"),
    ("DIVINO CARDOSO DOS SANTOS",        "Ajudante",     "estrutura"),
    ("ANDERSON CONCEIÇÃO DE JESUS",      "Profissional", "estrutura"),
    ("ALEX DE JESUS DA SILVA",           "Profissional", "estrutura"),
    ("ALEX VITÓRIO SILVA",               "Ajudante",     "estrutura"),
    ("CARLOS DANIEL DA SILVA MARQUES",   "Ajudante",     "estrutura"),
    ("THIEGO DE OLIVEIRA REIS",          "Profissional", "estrutura"),
    ("UBIRATTAN SNATOS SOUZA",           "Ajudante",     "estrutura"),
    ("WALISSON SILVA COSTA",             "Ajudante",     "estrutura"),
    ("VALMIR GONÇALVES DE OLIVEIRA",     "Profissional", "estrutura"),
    ("JUDICAEL LEITE DOS SANTOS",        "Profissional", "estrutura"),
    ("JOÃO PEDRO SILVA DOS SANTOS",      "Profissional", "estrutura"),
    ("JORGE DOS SANTOS",                 "Profissional", "estrutura"),
    ("JEAN AUGUSTO DOS SANTOS TAVARES",  "Profissional", "estrutura"),
]

with app.app_context():
    inseridos = 0
    ja_existem = 0
    for nome, funcao, escopo in colaboradores:
        existe = Colaborador.query.filter(Colaborador.nome.ilike(nome)).first()
        if existe:
            ja_existem += 1
            continue
        db.session.add(Colaborador(nome=nome, funcao=funcao, escopo=escopo, ativo=True))
        inseridos += 1
    db.session.commit()
    print(f"✅ {inseridos} colaboradores cadastrados. {ja_existem} já existiam.")
