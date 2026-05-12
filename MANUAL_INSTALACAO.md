# 📘 Manual de Instalação - Sistema de Gestão de Estoque

## 🎯 Objetivo

Este manual descreve como instalar e configurar o Sistema de Gestão de Estoque em diferentes ambientes.

---

## 📋 Pré-requisitos

### Para Instalação Local (Desenvolvimento/Teste)
- Python 3.11 ou superior
- Git (opcional, para clonar o repositório)
- Navegador web moderno

### Para Instalação em Servidor
- Servidor Linux (Ubuntu 20.04+) ou Windows Server 2016+
- Python 3.11+
- PostgreSQL 14+ (recomendado para produção)
- Nginx ou Apache (opcional, para proxy reverso)

### Para Deploy na Nuvem (Railway)
- Conta no GitHub
- Conta no Railway (gratuita ou paga)

---

## 🚀 Opção 1: Instalação Local (Windows)

### Passo 1: Baixar o Sistema

**Opção A: Baixar ZIP do GitHub**
1. Acesse: https://github.com/rickgouveia456-cmd/estoqueobrapatamares
2. Clique em **Code** → **Download ZIP**
3. Extraia o arquivo em `C:\estoque-obra`

**Opção B: Clonar com Git**
```cmd
cd C:\
git clone https://github.com/rickgouveia456-cmd/estoqueobrapatamares.git estoque-obra
cd estoque-obra
```

### Passo 2: Instalar Python

1. Baixe Python 3.11: https://www.python.org/downloads/
2. Durante a instalação, marque **"Add Python to PATH"**
3. Verifique a instalação:
```cmd
python --version
```

### Passo 3: Criar Ambiente Virtual

```cmd
cd C:\estoque-obra
python -m venv venv
venv\Scripts\activate
```

### Passo 4: Instalar Dependências

```cmd
pip install -r requirements.txt
```

### Passo 5: Configurar Variáveis de Ambiente (Opcional)

Crie um arquivo `.env` na raiz do projeto:

```env
# Banco de dados (deixe vazio para usar SQLite local)
DATABASE_URL=

# Email para backup automático (opcional)
BACKUP_EMAIL_FROM=seu-email@gmail.com
BACKUP_EMAIL_PASS=sua-senha-app-gmail
BACKUP_EMAIL_TO=destinatario@empresa.com

# Chave secreta (gere uma nova)
SECRET_KEY=sua-chave-secreta-aqui
```

### Passo 6: Iniciar o Sistema

```cmd
python app.py
```

O sistema estará disponível em: **http://localhost:5000**

**Login padrão:**
- **Usuário:** admin
- **Senha:** admin123

⚠️ **IMPORTANTE:** Altere a senha padrão imediatamente após o primeiro login!

---

## 🌐 Opção 2: Deploy na Nuvem (Railway)

### Passo 1: Preparar o Repositório GitHub

1. Crie uma conta no GitHub (se não tiver)
2. Crie um repositório privado
3. Faça upload dos arquivos do sistema

### Passo 2: Criar Conta no Railway

1. Acesse: https://railway.app
2. Clique em **Start a New Project**
3. Conecte sua conta do GitHub
4. Selecione o repositório do sistema

### Passo 3: Configurar Banco de Dados

1. No Railway, clique em **+ New**
2. Selecione **Database** → **PostgreSQL**
3. Aguarde a criação do banco

### Passo 4: Configurar Variáveis de Ambiente

No painel do Railway, vá em **Variables** e adicione:

```
DATABASE_URL=postgresql://... (gerado automaticamente pelo Railway)
BACKUP_EMAIL_FROM=seu-email@gmail.com
BACKUP_EMAIL_PASS=sua-senha-app-gmail
BACKUP_EMAIL_TO=destinatario@empresa.com
SECRET_KEY=d85554e30f9b5e1ff529c24c876ce55340bbe0fcc41040ab994793224a2f781a
```

### Passo 5: Deploy

1. O Railway fará o deploy automaticamente
2. Aguarde 2-3 minutos
3. Clique em **View Logs** para acompanhar
4. Quando aparecer "Deployment successful", clique em **Settings** → **Generate Domain**

### Passo 6: Acessar o Sistema

Acesse a URL gerada pelo Railway (ex: `https://seu-projeto.up.railway.app`)

**Login padrão:**
- **Usuário:** admin
- **Senha:** admin123

---

## 🐧 Opção 3: Instalação em Servidor Linux (Ubuntu)

### Passo 1: Atualizar o Sistema

```bash
sudo apt update
sudo apt upgrade -y
```

### Passo 2: Instalar Dependências

```bash
sudo apt install -y python3.11 python3.11-venv python3-pip postgresql nginx git
```

### Passo 3: Configurar PostgreSQL

```bash
sudo -u postgres psql

CREATE DATABASE estoque_obra;
CREATE USER estoque_user WITH PASSWORD 'senha_segura_aqui';
GRANT ALL PRIVILEGES ON DATABASE estoque_obra TO estoque_user;
\q
```

### Passo 4: Baixar o Sistema

```bash
cd /var/www
sudo git clone https://github.com/rickgouveia456-cmd/estoqueobrapatamares.git estoque-obra
cd estoque-obra
```

### Passo 5: Configurar Ambiente Virtual

```bash
python3.11 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
pip install gunicorn
```

### Passo 6: Configurar Variáveis de Ambiente

```bash
sudo nano /etc/systemd/system/estoque-obra.service
```

Adicione:

```ini
[Unit]
Description=Sistema de Gestão de Estoque
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/estoque-obra
Environment="DATABASE_URL=postgresql://estoque_user:senha_segura_aqui@localhost/estoque_obra"
Environment="BACKUP_EMAIL_FROM=seu-email@gmail.com"
Environment="BACKUP_EMAIL_PASS=sua-senha-app"
Environment="BACKUP_EMAIL_TO=destinatario@empresa.com"
Environment="SECRET_KEY=sua-chave-secreta-aqui"
ExecStart=/var/www/estoque-obra/venv/bin/gunicorn -w 4 -b 0.0.0.0:8000 app:app

[Install]
WantedBy=multi-user.target
```

### Passo 7: Iniciar o Serviço

```bash
sudo systemctl daemon-reload
sudo systemctl start estoque-obra
sudo systemctl enable estoque-obra
sudo systemctl status estoque-obra
```

### Passo 8: Configurar Nginx (Opcional)

```bash
sudo nano /etc/nginx/sites-available/estoque-obra
```

Adicione:

```nginx
server {
    listen 80;
    server_name seu-dominio.com;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

Ative o site:

```bash
sudo ln -s /etc/nginx/sites-available/estoque-obra /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## 📧 Configurar Backup Automático por Email

### Passo 1: Criar Senha de App no Gmail

1. Acesse: https://myaccount.google.com/security
2. Ative **Verificação em duas etapas**
3. Vá em **Senhas de app**
4. Selecione **Outro (nome personalizado)**
5. Digite: "Sistema Estoque Obra"
6. Copie a senha gerada (16 caracteres)

### Passo 2: Configurar Variáveis

Adicione nas variáveis de ambiente:

```
BACKUP_EMAIL_FROM=seu-email@gmail.com
BACKUP_EMAIL_PASS=senha-app-16-caracteres
BACKUP_EMAIL_TO=destinatario@empresa.com
```

### Passo 3: Configurar Cron Job (Servidor Linux)

```bash
crontab -e
```

Adicione (backup diário às 20h):

```cron
0 20 * * * curl https://seu-dominio.com/api/backup-automatico
```

### Passo 4: Testar Backup

Acesse no navegador:
```
https://seu-dominio.com/api/backup-automatico
```

Verifique se o email chegou!

---

## 🔧 Solução de Problemas

### Erro: "ModuleNotFoundError"
**Solução:** Instale as dependências novamente
```cmd
pip install -r requirements.txt
```

### Erro: "Port 5000 already in use"
**Solução:** Mude a porta no arquivo `app.py` (linha final):
```python
app.run(debug=False, host='0.0.0.0', port=5001)
```

### Erro: "Database connection failed"
**Solução:** Verifique se o PostgreSQL está rodando:
```bash
sudo systemctl status postgresql
```

### Backup não está sendo enviado
**Solução:** 
1. Verifique as variáveis de ambiente
2. Teste a senha de app do Gmail
3. Verifique os logs do sistema

### Sistema lento
**Solução:**
1. Aumente o número de workers do Gunicorn
2. Adicione mais RAM ao servidor
3. Otimize o banco de dados (índices)

---

## 📊 Monitoramento e Logs

### Ver logs em tempo real (Linux)
```bash
sudo journalctl -u estoque-obra -f
```

### Ver logs do Railway
1. Acesse o painel do Railway
2. Clique em **View Logs**
3. Filtre por tipo de log

### Logs do sistema
Os logs são exibidos no console. Para salvar em arquivo:

```python
# Adicione no início do app.py
import logging
logging.basicConfig(
    filename='estoque.log',
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
```

---

## 🔒 Segurança

### Checklist de Segurança

- [ ] Senha padrão do admin alterada
- [ ] SECRET_KEY única configurada
- [ ] HTTPS habilitado (produção)
- [ ] Firewall configurado
- [ ] Backup automático funcionando
- [ ] Senhas de usuários fortes
- [ ] PostgreSQL com senha forte
- [ ] Acesso SSH apenas por chave (servidor)

### Recomendações

1. **Nunca** use a senha padrão em produção
2. **Sempre** use HTTPS em produção
3. **Configure** backup automático
4. **Monitore** os logs regularmente
5. **Atualize** o sistema periodicamente

---

## 📞 Suporte

**Problemas na instalação?**

- **Email:** erick.cruz@stanza.com.br
- **WhatsApp:** [Seu Telefone]
- **Horário:** Segunda a Sexta, 8h às 18h

---

**Versão do Manual:** 2.0  
**Última Atualização:** Maio 2026
