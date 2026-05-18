# 🚀 Backup Automático via GitHub Actions

## ✅ O que foi implementado:

1. **Script de backup** (`backup_automatico.py`)
   - Conecta no banco PostgreSQL do Railway
   - Gera Excel com os dados
   - Envia emails para almoxarifes e admins

2. **Workflow do GitHub Actions** (`.github/workflows/backup-diario.yml`)
   - Executa automaticamente todo dia às **20:00** (horário de Brasília)
   - Pode ser executado manualmente para testes
   - Logs completos no GitHub

---

## 🔧 CONFIGURAÇÃO NECESSÁRIA (FAÇA AGORA):

### 1️⃣ Adicionar Secrets no GitHub

Você precisa adicionar as variáveis de ambiente como **Secrets** no GitHub:

1. Acesse: https://github.com/rickgouveia456-cmd/estoqueobrapatamares
2. Clique em **Settings** (configurações do repositório)
3. No menu lateral, clique em **Secrets and variables** → **Actions**
4. Clique em **New repository secret**

Adicione os seguintes secrets (um por um):

#### Secret 1: DATABASE_URL
- **Name:** `DATABASE_URL`
- **Value:** (copie do Railway - Settings → Variables → DATABASE_URL)
- Exemplo: `postgresql://postgres:senha@host.railway.app:5432/railway`

#### Secret 2: BACKUP_EMAIL_FROM
- **Name:** `BACKUP_EMAIL_FROM`
- **Value:** `rickgouveia157@gmail.com`

#### Secret 3: BACKUP_EMAIL_PASS
- **Name:** `BACKUP_EMAIL_PASS`
- **Value:** `bzesuxmiqaupvnly`

#### Secret 4: BACKUP_EMAIL_TO
- **Name:** `BACKUP_EMAIL_TO`
- **Value:** `rickgouveia157@gmail.com`

---

## 🧪 TESTAR AGORA (antes de esperar até 20:00):

### Opção 1: Executar Manualmente no GitHub

1. Acesse: https://github.com/rickgouveia456-cmd/estoqueobrapatamares/actions
2. Clique em **"Backup Automático Diário"** (no menu lateral)
3. Clique em **"Run workflow"** (botão azul)
4. Clique em **"Run workflow"** novamente (confirmar)
5. Aguarde 1-2 minutos
6. Verifique se recebeu o email!

### Opção 2: Executar Localmente (para debug)

```bash
# No terminal, na pasta do projeto:
python backup_automatico.py
```

---

## 📊 Como Funciona:

### Agendamento Automático:
```
20:00 (Brasília) = 23:00 (UTC)
↓
GitHub Actions dispara o workflow
↓
Instala Python e dependências
↓
Executa backup_automatico.py
↓
Conecta no PostgreSQL do Railway
↓
Gera Excel para cada almoxarifado
↓
Envia emails
```

### Quem Recebe:
- **Almoxarifes:** Backup individual do seu almoxarifado
- **Admins:** Backup completo de todos os almoxarifados
- **Email fixo:** `rickgouveia157@gmail.com` (sempre recebe backup completo)

---

## 📝 Logs e Monitoramento:

### Ver Logs de Execução:
1. Acesse: https://github.com/rickgouveia456-cmd/estoqueobrapatamares/actions
2. Clique na execução mais recente
3. Clique em **"backup"** para ver os logs detalhados

### O que os logs mostram:
```
✅ Conectado ao banco PostgreSQL
📊 Buscando dados do banco...
✅ 3 almoxarifados encontrados
✅ 5 usuários com email encontrados
📧 Enviando backup de "Almoxarifado Central" para almoxarife@email.com...
  ✅ Enviado para almoxarife@email.com
📧 Enviando backup completo para admin@email.com...
  ✅ Enviado para admin@email.com
✅ Backup concluído!
   Enviados: 5
   Erros: 0
```

---

## ⚠️ Troubleshooting:

### Erro: "DATABASE_URL não configurada"
- Verifique se adicionou o secret `DATABASE_URL` no GitHub
- Copie o valor exato do Railway (Settings → Variables)

### Erro: "Credenciais de email não configuradas"
- Verifique se adicionou os secrets `BACKUP_EMAIL_FROM` e `BACKUP_EMAIL_PASS`
- Verifique se a senha de app do Gmail está correta

### Erro: "Erro ao conectar no banco"
- Verifique se o `DATABASE_URL` está correto
- Verifique se o banco PostgreSQL do Railway está ativo

### Não recebeu email às 20:00
- Verifique os logs no GitHub Actions
- Verifique se o workflow está habilitado
- Execute manualmente para testar

---

## 🎯 Vantagens desta Solução:

✅ **Gratuito** - GitHub Actions é gratuito para repositórios públicos e privados (2000 minutos/mês)
✅ **Confiável** - Não depende do Railway estar rodando
✅ **Logs completos** - Veja exatamente o que aconteceu em cada execução
✅ **Fácil de testar** - Execute manualmente quando quiser
✅ **Sem impacto na aplicação** - Não consome recursos do Railway
✅ **Backup independente** - Mesmo se o Railway cair, o backup funciona

---

## 📅 Próximas Execuções:

O backup será executado automaticamente:
- **Hoje às 20:00** (se configurar os secrets agora)
- **Todo dia às 20:00** (horário de Brasília)

Para ver as próximas execuções agendadas:
1. Acesse: https://github.com/rickgouveia456-cmd/estoqueobrapatamares/actions
2. Clique em "Backup Automático Diário"
3. Veja o histórico de execuções

---

**Última atualização:** 12/05/2026  
**Versão:** 1.0.0
