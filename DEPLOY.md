# Logi-Prime — Deploy em Servidor Dedicado

## Requisitos mínimos do servidor
- Ubuntu 22.04 LTS (recomendado) ou Debian 12
- 4 GB RAM (8 GB recomendado para 40+ obras)
- 50 GB SSD
- Docker + Docker Compose instalados

## Instalação rápida

### 1. Instalar Docker
```bash
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER
```

### 2. Clonar e configurar
```bash
git clone https://github.com/Stanza-Construtora/Logi-Prime.git /opt/logiprime
cd /opt/logiprime
git checkout PHP
cp .env.production .env
nano .env   # Preencher senhas e URL
```

### 3. Gerar APP_SECRET seguro
```bash
openssl rand -base64 32
# Copie o resultado para APP_SECRET no .env
```

### 4. Subir o sistema
```bash
docker compose up -d
```

### 5. Verificar
```bash
docker compose ps
curl http://localhost/healthz
```

## Portas
| Serviço | Porta | Acesso |
|---------|-------|--------|
| Sistema | 80 | Externo |
| MySQL | 3306 | Apenas localhost |
| phpMyAdmin | 8081 | Apenas localhost (use SSH tunnel) |

## Backup do banco
```bash
# Backup manual
docker exec logiprime_mysql mysqldump \
  -u logiprime -p$DB_PASS logiprime \
  > backup_$(date +%Y%m%d_%H%M).sql

# Backup automático (crontab)
# Adicionar ao crontab: crontab -e
# 0 2 * * * docker exec logiprime_mysql mysqldump -u logiprime -p$DB_PASS logiprime > /opt/backups/logiprime_$(date +\%Y\%m\%d).sql
```

## phpMyAdmin (acesso via SSH tunnel)
```bash
# No seu computador local:
ssh -L 8081:localhost:8081 usuario@ip-do-servidor
# Depois acesse: http://localhost:8081
```

## Atualizar o sistema
```bash
cd /opt/logiprime
git pull origin PHP
docker compose restart app
```

## Logs
```bash
docker logs logiprime_php --tail 50
docker logs logiprime_mysql --tail 20
```

## Login padrão
- URL: http://SEU-IP ou https://seu-dominio.com.br
- Usuário: admin
- Senha: admin123 (TROQUE NO PRIMEIRO ACESSO)