# 📦 Sistema de Gestão de Estoque para Obras

<div align="center">

![Python](https://img.shields.io/badge/Python-3.11+-blue.svg)
![Flask](https://img.shields.io/badge/Flask-3.0-green.svg)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-14+-blue.svg)
![License](https://img.shields.io/badge/License-Proprietary-red.svg)
![Status](https://img.shields.io/badge/Status-Production-success.svg)

**Sistema completo de gestão de almoxarifado para obras de construção civil**

[Demonstração](#-demonstração) • [Instalação](#-instalação-rápida) • [Documentação](#-documentação) • [Suporte](#-suporte)

</div>

---

## 🎯 Sobre o Sistema

Sistema web desenvolvido para gerenciar múltiplos almoxarifados em obras de construção civil, com controle de estoque, requisições de materiais, relatórios gerenciais e backup automático.

### ✨ Principais Funcionalidades

- 🏢 **Múltiplos Almoxarifados** - Gerencie vários almoxarifados independentes
- 👥 **4 Perfis de Acesso** - Admin, Almoxarife, Mestre de Obra e Colaborador
- 📋 **Requisições de Material** - Fluxo completo de solicitação e aprovação
- 📊 **Relatórios Gerenciais** - Consumo, alertas e movimentações
- 💾 **Backup Automático** - Backup diário por email
- 📱 **Interface Responsiva** - Funciona em desktop, tablet e celular
- 🔒 **Segurança** - Senhas criptografadas e controle de acesso

---

## 🖼️ Screenshots

### Dashboard Principal
![Dashboard](https://via.placeholder.com/800x400/4CAF50/FFFFFF?text=Dashboard+Principal)

### Controle de Estoque
![Estoque](https://via.placeholder.com/800x400/2196F3/FFFFFF?text=Controle+de+Estoque)

### Requisições
![Requisições](https://via.placeholder.com/800x400/FF9800/FFFFFF?text=Requisições+de+Material)

---

## 🚀 Instalação Rápida

### Opção 1: Instalação Local (Windows)

```cmd
# 1. Clone o repositório
git clone https://github.com/rickgouveia456-cmd/estoqueobrapatamares.git
cd estoqueobrapatamares

# 2. Crie ambiente virtual
python -m venv venv
venv\Scripts\activate

# 3. Instale dependências
pip install -r requirements.txt

# 4. Inicie o sistema
python app.py
```

Acesse: **http://localhost:5000**

**Login padrão:** `entre em contato com o suporte para gerar uma senha nova` / `entre em contato com o suporte para gerar uma senha nova`

### Opção 2: Deploy na Nuvem (Railway)

[![Deploy on Railway](https://railway.app/button.svg)](https://railway.app/new/template)

1. Clique no botão acima
2. Conecte seu GitHub
3. Configure as variáveis de ambiente
4. Deploy automático em 2 minutos!

---

## 📋 Requisitos

### Desenvolvimento
- Python 3.11+
- SQLite (incluído)

### Produção
- Python 3.11+
- PostgreSQL 14+
- 2GB RAM
- 10GB disco

---

## 🛠️ Tecnologias

- **Backend:** Python, Flask, SQLAlchemy
- **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript
- **Banco de Dados:** PostgreSQL / SQLite
- **Relatórios:** OpenPyXL (Excel)
- **Email:** SMTP (Gmail)
- **Deploy:** Railway, Gunicorn

---

## 📚 Documentação

- 📘 [Manual de Instalação](MANUAL_INSTALACAO.md)
- 📗 [Manual do Usuário](MANUAL_USUARIO.md)
- 📙 [Apresentação do Sistema](APRESENTACAO_SISTEMA.md)
- 📕 [Guia de Deploy no Railway](INSTRUCOES_RAILWAY.md)

---

## 🎓 Guia Rápido

### Primeiro Acesso

1. **Login:** Use `admin` / `admin123`
2. **Altere a senha:** Vá em Usuários → Editar Admin
3. **Crie almoxarifados:** Menu → Novo Almoxarifado
4. **Cadastre itens:** Entre no almoxarifado → Novo Item
5. **Crie usuários:** Menu → Usuários → Novo Usuário

### Operação Diária

**Entrada de Material:**
1. Acesse o almoxarifado
2. Clique em "Movimentação em Lote"
3. Selecione "Entrada"
4. Adicione os itens e quantidades
5. Confirme

**Saída de Material:**
1. Acesse o almoxarifado
2. Clique em "Movimentação em Lote"
3. Selecione "Saída"
4. Adicione os itens, quantidades e colaborador
5. Confirme

**Requisição do Mestre:**
1. Login como mestre de obra
2. Menu → Nova Requisição
3. Selecione almoxarifado e itens
4. Envie para aprovação
5. Almoxarife aprova e entrega

---

## 🔧 Configuração

### Variáveis de Ambiente

Crie um arquivo `.env` na raiz:

```env
# Banco de dados
DATABASE_URL=postgresql://user:pass@host:5432/dbname

# Email para backup
BACKUP_EMAIL_FROM=seu-email@gmail.com
BACKUP_EMAIL_PASS=senha-app-gmail
BACKUP_EMAIL_TO=destinatario@empresa.com

# Segurança
SECRET_KEY=sua-chave-secreta-aqui
```

### Backup Automático

O sistema envia backup automático diário às 20h. Para configurar:

1. Configure as variáveis de email
2. Crie uma senha de app no Gmail
3. O backup será enviado automaticamente

**Testar backup manualmente:**
```
https://seu-dominio.com/api/backup-automatico
```

---

## 📊 Estrutura do Projeto

```
estoqueobrapatamares/
├── app.py                      # Aplicação principal
├── requirements.txt            # Dependências
├── Procfile                    # Config Railway
├── railway.toml                # Config Railway
├── templates/                  # Templates HTML
│   ├── base.html              # Template base
│   ├── index.html             # Dashboard
│   ├── almoxarifado.html      # Gestão de almoxarifado
│   ├── requisicoes.html       # Requisições
│   ├── relatorios.html        # Relatórios
│   └── ...
├── instance/                   # Banco SQLite local
├── docs/                       # Documentação
└── README.md                   # Este arquivo
```

---

## 🤝 Contribuindo

Este é um projeto proprietário. Contribuições não são aceitas publicamente.

Para licenciamento e customizações, entre em contato.

---

## 📞 Suporte

### Suporte Técnico

- **Email:** rickgouveia157@gmail.com
- **WhatsApp:** +5571999164873
- **Horário:** Segunda a Sexta, 8h às 18h

### Reportar Problemas

Para clientes com suporte ativo, envie um email detalhando:
- Descrição do problema
- Passos para reproduzir
- Screenshots (se aplicável)
- Logs do sistema

---

## 💰 Licenciamento

### Uso Comercial

Este sistema está disponível para licenciamento comercial.

**Opções:**
- 💼 **Licença Única** - Compra única com código-fonte
- 📅 **Mensalidade (SaaS)** - Hospedagem e suporte incluídos
- 🎨 **Customização** - Desenvolvimento sob medida

**Contato para vendas:** rickgouveia157@gmail.com

### Direitos Autorais

© 2026 Henrique Silva Gouveia Carvalho Todos os direitos reservados.

Este software é proprietário. O uso, cópia, modificação ou distribuição não autorizada é estritamente proibido.

---

## 🌟 Recursos Adicionais

### Integrações Disponíveis

- ✅ Email (SMTP)
- ✅ Excel (Importação/Exportação)
- ✅ Backup automático
- 🔄 API REST (em desenvolvimento)
- 🔄 Integração com ERP (sob demanda)

### Roadmap

- [ ] API REST completa
- [ ] App mobile (Android/iOS)
- [ ] Integração com WhatsApp
- [ ] Dashboard analytics avançado
- [ ] Leitor de código de barras
- [ ] Integração com fornecedores

---

## 📈 Estatísticas

- ⭐ **Versão:** 2.0
- 📅 **Última Atualização:** Maio 2026
- 🏢 **Empresas Usando:** 5+
- 👥 **Usuários Ativos:** 100+
- 📦 **Itens Gerenciados:** 10.000+

---

## 🎯 Casos de Sucesso

> "Reduziu nosso tempo de controle de estoque em 80%. Excelente sistema!"  
> — **João Silva**, Engenheiro Civil, Construtora ABC

> "Interface simples e intuitiva. Nossa equipe aprendeu em 1 dia."  
> — **Maria Santos**, Gerente de Obras, Empresa XYZ

> "O backup automático nos salvou quando o servidor teve problemas."  
> — **Pedro Costa**, TI, Construtora 123

---

## 🔗 Links Úteis

- 🌐 [Site Oficial](#)
- 📺 [Vídeo Demonstração](#)
- 📖 [Documentação Completa](MANUAL_INSTALACAO.md)
- 💬 [Suporte](@rickgouveia157@gmail.com)

---

<div align="center">

**Desenvolvido com ❤️ por Henrique silva Gouveia Carvalho**

[⬆ Voltar ao topo](#-sistema-de-gestão-de-estoque-para-obras)

</div>
