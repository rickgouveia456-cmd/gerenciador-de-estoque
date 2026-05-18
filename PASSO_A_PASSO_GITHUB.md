# 📋 PASSO A PASSO: Configurar Backup Automático no GitHub

## ✅ O QUE JÁ FOI FEITO:

1. ✅ Script de backup criado (`backup_automatico.py`)
2. ✅ Documentação completa (`BACKUP_GITHUB_ACTIONS.md`)
3. ✅ Arquivos enviados para o GitHub

---

## 🚀 O QUE VOCÊ PRECISA FAZER AGORA:

### **ETAPA 1: Adicionar o Workflow no GitHub** (5 minutos)

1. **Acesse o GitHub:**
   - Vá em: https://github.com/rickgouveia456-cmd/estoqueobrapatamares

2. **Crie a pasta de workflows:**
   - Clique em **"Add file"** → **"Create new file"**
   - No campo de nome do arquivo, digite: `.github/workflows/backup-diario.yml`
   - (O GitHub vai criar as pastas automaticamente)

3. **Cole o conteúdo do workflow:**
   ```yaml
   name: Backup Automático Diário

   on:
     schedule:
       # Executa todo dia às 20:00 (horário de Brasília = UTC-3)
       # 20:00 BRT = 23:00 UTC
       - cron: '0 23 * * *'
     
     # Permite executar manualmente para testes
     workflow_dispatch:

   jobs:
     backup:
       runs-on: ubuntu-latest
       
       steps:
         - name: Checkout código
           uses: actions/checkout@v4
         
         - name: Configurar Python
           uses: actions/setup-python@v5
           with:
             python-version: '3.11'
         
         - name: Instalar dependências
           run: |
             pip install psycopg2-binary openpyxl
         
         - name: Executar backup
           env:
             DATABASE_URL: ${{ secrets.DATABASE_URL }}
             BACKUP_EMAIL_FROM: ${{ secrets.BACKUP_EMAIL_FROM }}
             BACKUP_EMAIL_PASS: ${{ secrets.BACKUP_EMAIL_PASS }}
             BACKUP_EMAIL_TO: ${{ secrets.BACKUP_EMAIL_TO }}
           run: |
             python backup_automatico.py
         
         - name: Notificar sucesso
           if: success()
           run: |
             echo "✅ Backup executado com sucesso!"
             echo "Data: $(date)"
         
         - name: Notificar falha
           if: failure()
           run: |
             echo "❌ Backup falhou!"
             echo "Verifique os logs acima para detalhes."
   ```

4. **Salvar o arquivo:**
   - Role até o final da página
   - Clique em **"Commit new file"**

---

### **ETAPA 2: Adicionar Secrets no GitHub** (5 minutos)

Agora você precisa adicionar as variáveis de ambiente como **Secrets**:

1. **Acesse as configurações:**
   - No repositório, clique em **"Settings"**
   - No menu lateral esquerdo, clique em **"Secrets and variables"** → **"Actions"**

2. **Adicione cada secret (clique em "New repository secret"):**

   #### Secret 1: DATABASE_URL
   - Clique em **"New repository secret"**
   - **Name:** `DATABASE_URL`
   - **Value:** (copie do Railway)
     1. Vá no Railway → seu projeto → **Settings** → **Variables**
     2. Copie o valor de `DATABASE_URL`
     3. Exemplo: `postgresql://postgres:senha@host.railway.app:5432/railway`
   - Clique em **"Add secret"**

   #### Secret 2: BACKUP_EMAIL_FROM
   - Clique em **"New repository secret"**
   - **Name:** `BACKUP_EMAIL_FROM`
   - **Value:** `rickgouveia157@gmail.com`
   - Clique em **"Add secret"**

   #### Secret 3: BACKUP_EMAIL_PASS
   - Clique em **"New repository secret"**
   - **Name:** `BACKUP_EMAIL_PASS`
   - **Value:** `bzesuxmiqaupvnly`
   - Clique em **"Add secret"**

   #### Secret 4: BACKUP_EMAIL_TO
   - Clique em **"New repository secret"**
   - **Name:** `BACKUP_EMAIL_TO`
   - **Value:** `rickgouveia157@gmail.com`
   - Clique em **"Add secret"**

3. **Confirme que todos os 4 secrets foram adicionados:**
   - Você deve ver 4 secrets na lista:
     - `DATABASE_URL`
     - `BACKUP_EMAIL_FROM`
     - `BACKUP_EMAIL_PASS`
     - `BACKUP_EMAIL_TO`

---

### **ETAPA 3: Testar o Backup AGORA** (2 minutos)

Não espere até 20:00! Teste agora:

1. **Acesse GitHub Actions:**
   - Vá em: https://github.com/rickgouveia456-cmd/estoqueobrapatamares/actions

2. **Execute o workflow manualmente:**
   - No menu lateral esquerdo, clique em **"Backup Automático Diário"**
   - Clique no botão **"Run workflow"** (azul, no lado direito)
   - Clique em **"Run workflow"** novamente (confirmar)

3. **Aguarde a execução (1-2 minutos):**
   - Você verá um círculo amarelo girando (executando)
   - Quando ficar verde ✅ = sucesso!
   - Se ficar vermelho ❌ = erro (clique para ver os logs)

4. **Verifique seu email:**
   - Abra `rickgouveia157@gmail.com`
   - Você deve ter recebido o backup completo!

---

## 📊 RESULTADO ESPERADO:

### Se tudo funcionar:
- ✅ Workflow executado com sucesso (ícone verde)
- ✅ Email recebido com anexo Excel
- ✅ Logs mostram: "✅ Backup executado com sucesso!"

### Se der erro:
1. Clique no workflow que falhou
2. Clique em **"backup"** para ver os logs
3. Veja qual erro apareceu:
   - **"DATABASE_URL não configurada"** → Adicione o secret `DATABASE_URL`
   - **"Credenciais de email não configuradas"** → Adicione os secrets de email
   - **"Erro ao conectar no banco"** → Verifique se o `DATABASE_URL` está correto

---

## 🎯 DEPOIS DE CONFIGURAR:

### Backup Automático:
- ✅ Executa **todo dia às 20:00** (horário de Brasília)
- ✅ Você receberá o email automaticamente
- ✅ Não precisa fazer nada!

### Ver Histórico de Backups:
- Acesse: https://github.com/rickgouveia456-cmd/estoqueobrapatamares/actions
- Veja todas as execuções (sucesso ou falha)
- Clique em qualquer execução para ver os logs detalhados

### Executar Backup Manualmente:
- Acesse: https://github.com/rickgouveia456-cmd/estoqueobrapatamares/actions
- Clique em "Backup Automático Diário"
- Clique em "Run workflow"
- Receba o backup imediatamente!

---

## 📞 PRECISA DE AJUDA?

### Erro ao adicionar workflow:
- Certifique-se de que o nome do arquivo é exatamente: `.github/workflows/backup-diario.yml`
- Certifique-se de que copiou o conteúdo completo do YAML

### Erro ao adicionar secrets:
- Certifique-se de que os nomes estão EXATAMENTE como mostrado (maiúsculas/minúsculas importam)
- Certifique-se de que copiou os valores corretamente (sem espaços extras)

### Não recebeu email:
- Verifique os logs do GitHub Actions
- Verifique se a senha de app do Gmail está correta
- Verifique se o `DATABASE_URL` está correto

---

## ⏱️ TEMPO TOTAL: ~12 minutos

- Etapa 1 (Workflow): 5 minutos
- Etapa 2 (Secrets): 5 minutos
- Etapa 3 (Teste): 2 minutos

---

**COMECE AGORA! Siga as etapas acima e em 12 minutos você terá backup automático funcionando!** 🚀

**Última atualização:** 12/05/2026
