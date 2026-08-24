# Logi-Prime — Versao PHP + Docker

Migracao completa do sistema Python/Flask para **PHP puro + MySQL + Docker**.

## Stack

| Componente | Tecnologia |
|---|---|
| Backend | PHP 8.2 (puro, sem framework) |
| Banco de dados | MySQL 8.0 |
| Servidor | Apache 2.4 (php:8.2-apache) |
| PDF | FPDF via Composer |
| Frontend | Bootstrap 5.3 + Bootstrap Icons |
| Ambiente | Docker Compose |
| Admin DB | phpMyAdmin |

## Como rodar localmente

### Pre-requisitos
- Docker Desktop instalado e rodando

### Passos

```bash
# 1. Clone/acesse o repositorio e va para o branch PHP
git checkout PHP

# 2. Copie o .env de exemplo
cp .env.example .env

# 3. Suba os containers
docker compose up -d

# 4. Acesse no navegador
# App:       http://localhost:8080
# phpMyAdmin: http://localhost:8081
```

### Credenciais padrao

| | |
|---|---|
| Login | `admin` |
| Senha | `admin123` |

> **Altere a senha apos o primeiro acesso!**

## Estrutura do projeto

```
├── docker-compose.yml
├── Dockerfile
├── apache.conf
├── .env.example
├── sql/
│   ├── schema.sql      — Schema completo MySQL
│   └── seed.sql        — Dados iniciais (admin + almoxarifados)
└── src/
    ├── public/
    │   ├── index.php   — Front controller
    │   └── .htaccess
    ├── config/
    │   ├── config.php  — Configuracoes e variaveis de ambiente
    │   └── database.php — PDO Singleton
    ├── helpers/
    │   ├── functions.php — Funcoes utilitarias
    │   └── auth.php      — Autenticacao e controle de acesso
    ├── router.php        — Roteamento de URLs
    ├── controllers/      — Logica de negocio por modulo
    ├── views/            — Templates PHP por modulo
    ├── assets/
    │   ├── css/app.css   — Mesmo design system do Python
    │   └── js/app.js     — Sidebar toggle + helpers JS
    └── composer.json     — Dependencias PHP (FPDF)
```

## Modulos implementados

- [x] Autenticacao (login/logout/2FA via TOTP)
- [x] Dashboard com alertas e stats
- [x] Almoxarifados (CRUD, transferencia de itens)
- [x] Itens (CRUD, movimentacao em lote, importar/exportar Excel)
- [x] Requisicoes (simples e mestre de obra)
- [x] Ferramentas (emprestimo/devolucao/manutencao)
- [x] EPIs (modulo, ficha por colaborador)
- [x] Colaboradores
- [x] Usuarios e controle de acesso por perfil
- [x] Catalogo de insumos
- [x] Relatorios e exportacao Excel
- [x] Admin (backup, reativar itens, transferencias)
- [x] API JSON (alertas, colaboradores, itens)
- [x] PDFs (fichas de EPI, relatorios)

## Perfis de usuario

| Perfil | Acesso |
|---|---|
| admin | Tudo |
| almoxarife | Seu almoxarifado |
| analista | Visualizacao/relatorios |
| mestre | Requisicoes de obra |
| tecnico_seguranca | EPIs e requisicoes |
| colaborador | Requisicoes (se habilitado) |

## Diferencas em relacao a versao Python

- **PDF** gerado com FPDF (Python usava python-pptx para PPTX)
- **Banco** MySQL 8.0 (Python usava SQLite local / PostgreSQL no Railway)
- **Deploy** Docker puro (sem Railway)
- **Framework** PHP puro com roteamento proprio (Python usava Flask)
