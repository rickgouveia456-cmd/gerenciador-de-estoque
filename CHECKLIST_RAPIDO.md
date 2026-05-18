# ✅ Checklist Rápido - Sistema de Backup

## 🚀 FAÇA AGORA (em ordem):

### 1. Aguarde o Deploy no Railway
- [ ] Acesse https://railway.app/
- [ ] Vá no seu projeto
- [ ] Aguarde o deploy do commit `040b3b2` terminar (leva ~2-5 minutos)

### 2. Verifique os Logs
- [ ] Clique em **Deployments** → último deploy
- [ ] Clique em **View Logs**
- [ ] Procure por: `DIAGNÓSTICO DE VARIÁVEIS DE AMBIENTE - Railway`
- [ ] Verifique se TODAS as variáveis têm ✅

### 3A. Se TODAS as variáveis estão OK (✅):
- [ ] Acesse seu site
- [ ] Faça login como admin
- [ ] Vá em **Backup**
- [ ] Clique em **"Enviar para E-mail"**
- [ ] Verifique se recebeu o email
- [ ] **PRONTO! Sistema funcionando!** 🎉

### 3B. Se ALGUMA variável está faltando (❌):
- [ ] No Railway, vá em **Settings** → **Variables**
- [ ] **DELETE** as variáveis de backup (clique no ícone de lixeira)
- [ ] **RECRIE** cada uma:
  - [ ] `BACKUP_EMAIL_FROM` = `rickgouveia157@gmail.com`
  - [ ] `BACKUP_EMAIL_PASS` = `bzesuxmiqaupvnly`
  - [ ] `BACKUP_EMAIL_TO` = `rickgouveia157@gmail.com`
- [ ] Aguarde o redeploy automático
- [ ] Volte para o passo 2 (Verifique os Logs)

---

## 📧 Configuração do Gmail (se necessário)

### Se a senha de app não funcionar:
- [ ] Acesse https://myaccount.google.com/security
- [ ] Ative **Verificação em duas etapas**
- [ ] Vá em **Senhas de app**
- [ ] Gere nova senha para "Sistema de Estoque"
- [ ] Copie a senha (16 caracteres)
- [ ] Atualize `BACKUP_EMAIL_PASS` no Railway

---

## 🧪 Testes Finais

### Teste 1: Backup Manual
- [ ] Login como admin
- [ ] Vá em **Backup**
- [ ] Clique em **"Enviar para E-mail"**
- [ ] Verifique email em `rickgouveia157@gmail.com`

### Teste 2: Cadastrar Email de Almoxarife
- [ ] Vá em **Usuários**
- [ ] Edite um usuário almoxarife
- [ ] Adicione um email de teste
- [ ] Salve
- [ ] Envie backup novamente
- [ ] Verifique se o almoxarife recebeu apenas o backup do seu almoxarifado

### Teste 3: Backup Automático
- [ ] Aguarde até 20:00 (horário de Brasília)
- [ ] Verifique se recebeu o email automaticamente
- [ ] Verifique os logs do Railway às 20:00

---

## 📊 Resultado Esperado

### Quando funcionar corretamente:

#### Logs do Railway (início da aplicação):
```
================================================================================
DIAGNÓSTICO DE VARIÁVEIS DE AMBIENTE - Railway
================================================================================
✅ BACKUP_EMAIL_FROM    = rickgouveia157@gmail.com
✅ BACKUP_EMAIL_PASS    = bze***nly
✅ BACKUP_EMAIL_TO      = rickgouveia157@gmail.com
✅ DATABASE_URL         = SIM
✅ SECRET_KEY           = SIM
✅ PORT                 = SIM
================================================================================
✅ Todas as variáveis de ambiente estão configuradas corretamente!
```

#### Logs do Backup Manual:
```
BACKUP: enviado para almoxarife(s) de "Almoxarifado Central": ['almoxarife@email.com']
BACKUP: backup completo enviado para admins: ['admin@email.com', 'rickgouveia157@gmail.com']
```

#### Logs do Backup Automático (20:00):
```
BACKUP AUTOMÁTICO: iniciando às 12/05/2026 20:00
BACKUP AUTOMÁTICO: remetente configurado = SIM (rickgouveia157@gmail.com)
BACKUP AUTOMÁTICO: senha configurada = SIM
BACKUP AUTOMÁTICO: destino fixo = rickgouveia157@gmail.com
BACKUP AUTOMÁTICO: admins com email = ['admin@email.com']
BACKUP AUTOMÁTICO: almoxarifes com email = ['almoxarife@email.com']
BACKUP: enviado para almoxarife(s) de "Almoxarifado Central": ['almoxarife@email.com']
BACKUP: backup completo enviado para admins: ['admin@email.com', 'rickgouveia157@gmail.com']
BACKUP AUTOMÁTICO: ✅ enviado com sucesso!
```

---

## ❌ Problemas Comuns

| Problema | Solução Rápida |
|----------|----------------|
| Variáveis com ❌ nos logs | Delete e recrie as variáveis no Railway |
| "Erro ao enviar email" | Verifique senha de app do Gmail |
| Não recebeu email às 20:00 | Verifique logs do Railway às 20:00 |
| Almoxarife não recebeu | Verifique se tem email cadastrado e está ativo |

---

## 📞 Precisa de Ajuda?

1. Leia `INSTRUCOES_RAILWAY.md` (instruções detalhadas)
2. Leia `SISTEMA_BACKUP.md` (documentação completa)
3. Copie os logs do Railway e a mensagem de erro exata

---

**Tempo estimado:** 10-15 minutos  
**Última atualização:** 12/05/2026
