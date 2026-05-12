# 📦 Sistema de Gestão de Estoque para Obras

## 🎯 Visão Geral

Sistema completo de gestão de almoxarifado desenvolvido especificamente para obras de construção civil. Controla múltiplos almoxarifados, requisições de materiais, movimentações de estoque e gera relatórios automáticos.

---

## ✨ Funcionalidades Principais

### 🏢 Gestão de Almoxarifados
- ✅ Múltiplos almoxarifados independentes
- ✅ Controle de estoque por almoxarifado
- ✅ Alertas automáticos de estoque mínimo
- ✅ Itens críticos destacados

### 👥 Controle de Usuários e Permissões
- ✅ **4 perfis de acesso:**
  - **Admin:** Acesso total ao sistema
  - **Almoxarife:** Gerencia seu almoxarifado
  - **Mestre de Obra:** Faz requisições de material
  - **Colaborador:** Consulta básica

### 📋 Requisições de Material
- ✅ Requisição individual (colaborador retira material)
- ✅ Requisição do mestre (solicita ao almoxarife)
- ✅ Fluxo de aprovação: pendente → aprovada → entregue
- ✅ Histórico completo de movimentações

### 📊 Relatórios e Controles
- ✅ Relatório de consumo por período
- ✅ Relatório de consumo por pessoa
- ✅ Alertas de estoque baixo
- ✅ Exportação para Excel
- ✅ **Backup automático diário por email**

### 💾 Backup Automático
- ✅ Backup diário às 20h (horário configurável)
- ✅ Envio por email automático
- ✅ Cada almoxarife recebe backup do seu almoxarifado
- ✅ Admins recebem backup completo
- ✅ Backup manual disponível a qualquer momento

### 📱 Interface Responsiva
- ✅ Funciona em desktop, tablet e celular
- ✅ Design moderno e intuitivo
- ✅ Cores e alertas visuais

---

## 🛠️ Tecnologias Utilizadas

- **Backend:** Python 3.11 + Flask
- **Banco de Dados:** PostgreSQL (produção) / SQLite (desenvolvimento)
- **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript
- **Relatórios:** OpenPyXL (Excel)
- **Deploy:** Railway (cloud) ou servidor próprio

---

## 📦 O Que Está Incluído

```
📁 Sistema Completo
├── 📄 app.py                    # Aplicação principal
├── 📄 requirements.txt          # Dependências Python
├── 📄 Procfile                  # Configuração para deploy
├── 📄 railway.toml              # Configuração Railway
├── 📁 templates/                # Interface HTML
│   ├── base.html
│   ├── index.html
│   ├── almoxarifado.html
│   ├── requisicoes.html
│   ├── relatorios.html
│   ├── backup.html
│   └── ... (25+ templates)
├── 📁 instance/                 # Banco de dados local
├── 📄 MANUAL_INSTALACAO.md      # Guia de instalação
├── 📄 MANUAL_USUARIO.md         # Manual do usuário
└── 📄 APRESENTACAO_SISTEMA.md   # Este arquivo
```

---

## 💰 Modelo de Comercialização

### Opção 1: Licença Única
- Instalação completa no servidor da empresa
- Código-fonte incluído
- Suporte técnico por 6 meses
- Treinamento da equipe (2h)

### Opção 2: Mensalidade (SaaS)
- Hospedagem em nuvem (Railway)
- Backup automático diário
- Suporte técnico contínuo
- Atualizações incluídas
- Sem necessidade de servidor próprio

### Opção 3: Customização
- Adaptação às necessidades específicas
- Integração com outros sistemas
- Desenvolvimento de novas funcionalidades
- Relatórios personalizados

---

## 🚀 Formas de Deploy

### 1️⃣ Cloud (Railway) - Recomendado
- ✅ Sem necessidade de servidor próprio
- ✅ Backup automático
- ✅ SSL/HTTPS incluído
- ✅ Escalável
- ✅ Custo: ~$5-10/mês

### 2️⃣ Servidor Próprio (Linux)
- ✅ Controle total
- ✅ Sem custos mensais de hospedagem
- ✅ Dados na empresa
- ⚠️ Requer conhecimento técnico

### 3️⃣ Servidor Local (Windows)
- ✅ Acesso apenas na rede local
- ✅ Sem custos de hospedagem
- ✅ Ideal para obras sem internet estável
- ⚠️ Sem acesso remoto

---

## 📋 Requisitos Mínimos

### Para Servidor Próprio:
- **Sistema:** Windows Server 2016+ ou Linux (Ubuntu 20.04+)
- **Processador:** 2 cores
- **RAM:** 2GB
- **Disco:** 10GB
- **Python:** 3.11+
- **PostgreSQL:** 14+

### Para Cloud (Railway):
- Apenas uma conta no Railway (gratuita ou paga)
- Configuração em 10 minutos

---

## 🎓 Treinamento e Suporte

### Treinamento Inicial (2 horas)
1. **Módulo 1:** Cadastros básicos (30min)
   - Criar almoxarifados
   - Cadastrar itens
   - Criar usuários

2. **Módulo 2:** Operação diária (45min)
   - Entrada de materiais
   - Saída de materiais
   - Requisições

3. **Módulo 3:** Relatórios e backup (30min)
   - Gerar relatórios
   - Configurar backup
   - Exportar dados

4. **Módulo 4:** Administração (15min)
   - Gerenciar usuários
   - Configurar alertas
   - Manutenção básica

### Suporte Técnico
- **Email:** [seu-email@empresa.com]
- **WhatsApp:** [seu-telefone]
- **Horário:** Segunda a Sexta, 8h às 18h
- **Tempo de resposta:** Até 24h úteis

---

## 📞 Contato para Vendas

**Desenvolvedor:** Erick Silva Cruz  
**Email:** erick.cruz@stanza.com.br  
**Empresa:** [Sua Empresa]  
**Telefone:** [Seu Telefone]  

---

## 🔒 Segurança e Privacidade

- ✅ Senhas criptografadas (PBKDF2)
- ✅ Sessões seguras
- ✅ HTTPS obrigatório em produção
- ✅ Backup automático criptografado
- ✅ Logs de auditoria
- ✅ Controle de acesso por perfil

---

## 📈 Benefícios para a Empresa

### Economia de Tempo
- ⏱️ Reduz tempo de controle manual em 80%
- ⏱️ Requisições em 2 minutos (vs 15min manual)
- ⏱️ Relatórios instantâneos

### Redução de Perdas
- 💰 Evita compras desnecessárias
- 💰 Controle preciso de estoque
- 💰 Alertas de estoque mínimo

### Organização
- 📊 Histórico completo de movimentações
- 📊 Rastreabilidade total
- 📊 Relatórios gerenciais

### Mobilidade
- 📱 Acesso de qualquer lugar
- 📱 Funciona em celular/tablet
- 📱 Backup automático na nuvem

---

## 🎯 Casos de Uso

### Obra de Pequeno Porte
- 1-2 almoxarifados
- 5-10 usuários
- Controle básico de estoque

### Obra de Médio Porte
- 3-5 almoxarifados
- 20-50 usuários
- Múltiplos mestres de obra
- Relatórios gerenciais

### Obra de Grande Porte
- 5+ almoxarifados
- 50+ usuários
- Controle rigoroso
- Integração com ERP

---

## 📝 Próximos Passos

1. **Demonstração:** Agende uma demo online (30min)
2. **Proposta:** Receba proposta personalizada
3. **Instalação:** Deploy em 1 dia útil
4. **Treinamento:** 2 horas com sua equipe
5. **Go Live:** Sistema em produção

---

## ⚖️ Licença e Propriedade

Este sistema foi desenvolvido por **Erick Silva Cruz** e todos os direitos são reservados.

**Licenciamento:**
- ✅ Código-fonte incluído na compra
- ✅ Direito de uso perpétuo
- ✅ Direito de modificação para uso próprio
- ❌ Revenda do código não autorizada sem acordo
- ❌ Redistribuição não autorizada

---

## 🌟 Diferenciais Competitivos

✨ **Desenvolvido especificamente para obras de construção civil**  
✨ **Interface simples e intuitiva (não requer treinamento extenso)**  
✨ **Backup automático (segurança dos dados)**  
✨ **Funciona offline (modo local)**  
✨ **Suporte em português**  
✨ **Preço acessível**  
✨ **Sem mensalidades (opção de compra única)**  

---

**Versão:** 2.0  
**Última Atualização:** Maio 2026  
**Status:** Produção ✅
