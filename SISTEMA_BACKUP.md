# 📦 Sistema de Backup Automático - Documentação Completa

## 🎯 Funcionalidades Implementadas

### 1. Campo de Email para Usuários
- ✅ Todos os usuários podem ter email cadastrado
- ✅ Campo opcional no formulário de cadastro/edição
- ✅ Usado para envio automático de backups

### 2. Backup Segmentado por Almoxarifado
- ✅ Cada almoxarife recebe apenas o backup do seu almoxarifado
- ✅ Administradores recebem backup completo de todos os almoxarifados
- ✅ Email fixo sempre recebe backup completo

### 3. Backup Automático Diário
- ✅ Executa automaticamente às **20:00** (horário de Brasília)
- ✅ Envia emails para todos os usuários com email cadastrado
- ✅ Logs detalhados para diagnóstico

### 4. Backup Manual
- ✅ Botão para download local do Excel
- ✅ Botão para envio imediato por email

---

## 📊 Fluxo de Funcionamento

```
┌─────────────────────────────────────────────────────────────┐
│                    SISTEMA DE BACKUP                        │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
                    ┌─────────────────┐
                    │  Trigger Backup │
                    │  (20:00 ou      │
                    │   Manual)       │
                    └─────────────────┘
                              │
                              ▼
        ┌─────────────────────┴─────────────────────┐
        │                                            │
        ▼                                            ▼
┌───────────────────┐                    ┌───────────────────┐
│ Para cada         │                    │ Backup Completo   │
│ Almoxarifado:     │                    │ (Todos os         │
│                   │                    │  Almoxarifados)   │
│ 1. Gera Excel     │                    │                   │
│    com itens      │                    │ 1. Gera Excel     │
│                   │                    │    completo       │
│ 2. Envia para     │                    │                   │
│    almoxarifes    │                    │ 2. Envia para:    │
│    daquele        │                    │    - Admins       │
│    almoxarifado   │                    │    - Email fixo   │
└───────────────────┘                    └───────────────────┘
```

---

## 👥 Quem Recebe o Quê

### Almoxarife (perfil: almoxarife)
- **Recebe:** Backup individual do seu almoxarifado
- **Condições:**
  - Usuário ativo
  - Email cadastrado
  - Associado a um almoxarifado
- **Arquivo:** `backup_[NOME_ALMOXARIFADO]_[DATA].xlsx`
- **Conteúdo:** Apenas itens do almoxarifado dele

### Administrador (perfil: admin)
- **Recebe:** Backup completo de todos os almoxarifados
- **Condições:**
  - Usuário ativo
  - Email cadastrado
- **Arquivo:** `backup_completo_[DATA].xlsx`
- **Conteúdo:** Todos os itens de todos os almoxarifados

### Email Fixo
- **Email:** `rickgouveia157@gmail.com` (configurado em `BACKUP_EMAIL_TO`)
- **Recebe:** Backup completo sempre
- **Arquivo:** `backup_completo_[DATA].xlsx`
- **Conteúdo:** Todos os itens de todos os almoxarifados

---

## 📧 Formato dos Emails

### Email para Almoxarife
```
De: rickgouveia157@gmail.com
Para: [email do almoxarife]
Assunto: Backup [Nome do Almoxarifado] — 12/05/2026

Backup automático do almoxarifado: [Nome do Almoxarifado]
Data: 12/05/2026 20:00

Este arquivo contém o estoque atual do seu almoxarifado.
Guarde em local seguro.

Anexo: backup_[Nome_Almoxarifado]_2026-05-12.xlsx
```

### Email para Administrador
```
De: rickgouveia157@gmail.com
Para: [emails dos admins], rickgouveia157@gmail.com
Assunto: Backup Completo Estoque — 12/05/2026

Backup automático completo do sistema de estoque.
Data: 12/05/2026 20:00

Este arquivo contém todos os almoxarifados.
Guarde em local seguro.

Anexo: backup_completo_2026-05-12.xlsx
```

---

## 🔧 Configuração Técnica

### Variáveis de Ambiente (Railway)
```bash
BACKUP_EMAIL_FROM=rickgouveia157@gmail.com
BACKUP_EMAIL_PASS=bzesuxmiqaupvnly
BACKUP_EMAIL_TO=rickgouveia157@gmail.com
```

### Agendamento (APScheduler)
```python
# Executa todo dia às 20:00 (horário de Brasília)
scheduler.add_job(
    job_backup_diario,
    CronTrigger(hour=20, minute=0, timezone='America/Sao_Paulo'),
    id='backup_diario',
    replace_existing=True
)
```

### Servidor SMTP
- **Servidor:** smtp.gmail.com
- **Porta:** 465 (SSL)
- **Autenticação:** Senha de App do Gmail

---

## 📝 Estrutura do Excel

### Backup Individual (Almoxarife)
```
┌─────────────────────────────────────────────────────────┐
│ BACKUP DO ALMOXARIFADO: [Nome]                          │
│ Data: 12/05/2026 20:00                                  │
├─────────────────────────────────────────────────────────┤
│ Código │ Descrição │ Unidade │ Qtd │ Mín │ Máx │ Local │
├────────┼───────────┼─────────┼─────┼─────┼─────┼───────┤
│ 001    │ Item 1    │ UN      │ 100 │ 50  │ 200 │ A1    │
│ 002    │ Item 2    │ KG      │ 50  │ 20  │ 100 │ B2    │
└────────┴───────────┴─────────┴─────┴─────┴─────┴───────┘
```

### Backup Completo (Administrador)
```
┌─────────────────────────────────────────────────────────┐
│ BACKUP COMPLETO DO ESTOQUE                              │
│ Data: 12/05/2026 20:00                                  │
├─────────────────────────────────────────────────────────┤
│ Almoxarifado │ Código │ Descrição │ Unidade │ Qtd │... │
├──────────────┼────────┼───────────┼─────────┼─────┼────┤
│ Almox 1      │ 001    │ Item 1    │ UN      │ 100 │... │
│ Almox 1      │ 002    │ Item 2    │ KG      │ 50  │... │
│ Almox 2      │ 003    │ Item 3    │ M       │ 200 │... │
└──────────────┴────────┴───────────┴─────────┴─────┴────┘
```

---

## 🧪 Como Testar

### Teste 1: Cadastrar Email de Usuário
1. Login como admin
2. Vá em **Usuários**
3. Edite um usuário almoxarife
4. Adicione um email válido
5. Salve

### Teste 2: Backup Manual
1. Login como admin
2. Vá em **Backup**
3. Clique em **"Enviar para E-mail"**
4. Aguarde a mensagem de sucesso
5. Verifique os emails

### Teste 3: Backup Automático
1. Aguarde até 20:00 (horário de Brasília)
2. Verifique os emails às 20:01
3. Verifique os logs do Railway

### Teste 4: Verificar Logs
1. Acesse Railway Dashboard
2. Vá em **Deployments** → **View Logs**
3. Procure por:
   - `BACKUP AUTOMÁTICO: iniciando às`
   - `BACKUP: enviado para almoxarife(s) de`
   - `BACKUP: backup completo enviado para admins`

---

## 🐛 Troubleshooting

### Problema: "Variáveis não configuradas"
**Solução:**
1. Verifique se as variáveis estão no Railway
2. Delete e recrie as variáveis
3. Force um redeploy

### Problema: "Erro ao enviar email"
**Solução:**
1. Verifique se a senha de app está correta
2. Verifique se a verificação em duas etapas está ativa
3. Gere uma nova senha de app

### Problema: "Não recebi o backup às 20:00"
**Solução:**
1. Verifique os logs do Railway às 20:00
2. Verifique se o usuário tem email cadastrado
3. Verifique se o usuário está ativo

### Problema: "Almoxarife recebeu backup completo"
**Solução:**
- Isso não deve acontecer. Verifique o código da função `enviar_backup_por_almoxarifado()`

### Problema: "Admin não recebeu backup"
**Solução:**
1. Verifique se o admin tem email cadastrado
2. Verifique se o admin está ativo
3. Verifique os logs para ver se houve erro no envio

---

## 📚 Arquivos Relacionados

### Backend
- `app.py` (linhas 1980-2230): Funções de backup e agendamento
- `check_env.py`: Script de diagnóstico de variáveis

### Frontend
- `templates/backup.html`: Página de backup manual
- `templates/form_usuario.html`: Formulário com campo de email

### Configuração
- `railway.toml`: Configuração do Railway
- `requirements.txt`: Dependências (APScheduler, pytz)

### Documentação
- `INSTRUCOES_RAILWAY.md`: Instruções para resolver problemas
- `SISTEMA_BACKUP.md`: Este arquivo

---

## 📊 Estatísticas de Uso

### Horário de Execução
- **Backup Automático:** 20:00 (horário de Brasília)
- **Fuso Horário:** America/Sao_Paulo (UTC-3)

### Frequência
- **Automático:** 1x por dia (20:00)
- **Manual:** Ilimitado (via botão na página de backup)

### Destinatários
- **Almoxarifes:** Recebem backup individual
- **Administradores:** Recebem backup completo
- **Email Fixo:** Sempre recebe backup completo

---

**Sistema desenvolvido em:** Maio 2026  
**Última atualização:** 12/05/2026  
**Versão:** 1.0.0
