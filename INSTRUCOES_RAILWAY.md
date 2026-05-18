# 🚀 Instruções para Resolver o Problema de Variáveis no Railway

## ✅ O que foi feito agora:

1. **Criado script de diagnóstico** (`check_env.py`)
   - Verifica todas as variáveis de ambiente necessárias
   - Mostra quais estão faltando
   - Executa ANTES da aplicação iniciar

2. **Modificado `railway.toml`**
   - Agora executa: `python check_env.py && python app.py`
   - Se as variáveis não estiverem configuradas, o deploy FALHA com mensagem clara

3. **Push realizado com sucesso**
   - Commit: `040b3b2`
   - Railway vai fazer deploy automaticamente

---

## 📋 Próximos Passos (FAÇA AGORA):

### 1️⃣ Aguarde o Deploy Terminar
- Acesse: https://railway.app/
- Vá no seu projeto
- Aguarde o deploy do commit `040b3b2` terminar

### 2️⃣ Verifique os Logs do Deploy
- Clique na aba **"Deployments"**
- Clique no deploy mais recente (commit `040b3b2`)
- Clique em **"View Logs"**
- Procure pela seção:
  ```
  ================================================================================
  DIAGNÓSTICO DE VARIÁVEIS DE AMBIENTE - Railway
  ================================================================================
  ```

### 3️⃣ Analise o Resultado:

#### ✅ **SE TODAS AS VARIÁVEIS APARECEREM COM ✅:**
- O problema está resolvido!
- Acesse seu site e teste o backup manual
- Aguarde até 20:00 para testar o backup automático

#### ❌ **SE ALGUMA VARIÁVEL APARECER COM ❌:**
- As variáveis NÃO estão sendo injetadas pelo Railway
- Siga os passos da seção "Como Reconfigurar Variáveis" abaixo

---

## 🔧 Como Reconfigurar Variáveis no Railway (se necessário):

### Passo 1: Acesse as Variáveis
1. No Railway, vá em **Settings** → **Variables**
2. Você deve ver estas variáveis:
   - `BACKUP_EMAIL_FROM`
   - `BACKUP_EMAIL_PASS`
   - `BACKUP_EMAIL_TO`
   - `DATABASE_URL` (criada automaticamente pelo Railway)

### Passo 2: Recrie as Variáveis de Backup
**DELETE e RECRIE cada uma:**

1. **BACKUP_EMAIL_FROM**
   - Clique no ícone de lixeira para deletar
   - Clique em **"New Variable"**
   - Name: `BACKUP_EMAIL_FROM`
   - Value: `rickgouveia157@gmail.com`
   - Clique em **"Add"**

2. **BACKUP_EMAIL_PASS**
   - Clique no ícone de lixeira para deletar
   - Clique em **"New Variable"**
   - Name: `BACKUP_EMAIL_PASS`
   - Value: `bzesuxmiqaupvnly`
   - Clique em **"Add"**

3. **BACKUP_EMAIL_TO**
   - Clique no ícone de lixeira para deletar
   - Clique em **"New Variable"**
   - Name: `BACKUP_EMAIL_TO`
   - Value: `rickgouveia157@gmail.com`
   - Clique em **"Add"**

### Passo 3: Force um Redeploy
- Após recriar as variáveis, o Railway faz redeploy automaticamente
- OU clique em **"Redeploy"** manualmente

### Passo 4: Verifique os Logs Novamente
- Aguarde o novo deploy terminar
- Verifique os logs novamente
- Agora TODAS as variáveis devem aparecer com ✅

---

## 🧪 Como Testar Após Resolver:

### Teste 1: Backup Manual
1. Acesse seu site
2. Faça login como admin
3. Vá em **Backup**
4. Clique em **"Enviar para E-mail"**
5. Verifique se recebeu o email em `rickgouveia157@gmail.com`

### Teste 2: Backup Automático
1. Aguarde até **20:00** (horário de Brasília)
2. Verifique se recebeu o email automaticamente
3. Verifique os logs do Railway para confirmar execução

### Teste 3: Backup por Almoxarifado
1. Cadastre um usuário almoxarife com email
2. Associe ele a um almoxarifado
3. Clique em "Enviar para E-mail"
4. O almoxarife deve receber apenas o backup do seu almoxarifado
5. Admins devem receber o backup completo

---

## 📧 Configuração do Gmail (se ainda não fez):

A senha `bzesuxmiqaupvnly` é uma **Senha de App do Gmail**.

### Se precisar gerar uma nova:
1. Acesse: https://myaccount.google.com/security
2. Ative a **Verificação em duas etapas** (se não estiver ativa)
3. Vá em **Senhas de app**
4. Selecione **"Outro (nome personalizado)"**
5. Digite: `Sistema de Estoque`
6. Clique em **"Gerar"**
7. Copie a senha gerada (16 caracteres)
8. Use essa senha no `BACKUP_EMAIL_PASS`

---

## 🐛 Problemas Comuns:

### "Erro ao enviar email" mesmo com variáveis configuradas:
- Verifique se a senha de app está correta (sem espaços)
- Verifique se a verificação em duas etapas está ativa no Gmail
- Tente gerar uma nova senha de app

### "Não recebi o backup às 20:00":
- Verifique os logs do Railway às 20:00
- Procure por: `BACKUP AUTOMÁTICO: iniciando às`
- Verifique se há erros nos logs

### "Almoxarife não recebeu o backup":
- Verifique se o usuário tem email cadastrado
- Verifique se o usuário está ativo
- Verifique se o usuário está associado a um almoxarifado

---

## 📞 Suporte:

Se após seguir todos os passos o problema persistir:
1. Copie os logs completos do deploy
2. Copie a mensagem de erro exata
3. Verifique se as variáveis estão EXATAMENTE como mostrado acima (sem espaços extras)

---

**Última atualização:** 12/05/2026
**Commit:** 040b3b2
