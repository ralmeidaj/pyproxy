# Plano de Implantação — Payproxy

**Versão:** 1.0  
**Data:** 2026-06-18  
**Status:** Rascunho

---

## 1. Visão Geral

A plataforma Payproxy é implantada em dois ambientes isolados: **staging** e **produção**. Cada ambiente é um servidor VPS independente rodando Ubuntu 22.04 LTS com Docker Compose. O deploy é realizado manualmente via SSH.

**Requisito legal:** os servidores devem estar em território nacional (LGPD + exigência contratual SEFAZ).

**Providers recomendados (data centers no Brasil):**
| Provider | Região | Observação |
|---|---|---|
| AWS | sa-east-1 (São Paulo) | Maior SLA, custo mais alto |
| DigitalOcean | NYC3 ou SFO3 + CDN BR | Boa ergonomia, preço médio |
| Contabo | São Paulo | Custo baixo, boa para MVP |
| KingHost | RS/SP | Nacional, suporte em PT-BR |
| Vultr | São Paulo | Custo competitivo |

---

## 2. Requisitos de Infraestrutura

### 2.1 Staging

| Recurso | Mínimo |
|---|---|
| vCPUs | 2 |
| RAM | 4 GB |
| Disco | 40 GB SSD |
| IP fixo | 1 |
| Largura de banda | 1 Gbps |

> Staging não precisa de alta disponibilidade — pode ser desligado fora do horário de testes.

### 2.2 Produção

| Recurso | Mínimo | Recomendado |
|---|---|---|
| vCPUs | 4 | 8 |
| RAM | 8 GB | 16 GB |
| Disco OS | 50 GB SSD | 80 GB SSD |
| Disco dados | 100 GB SSD separado | 200 GB SSD |
| IP fixo | 1 | 1 |
| Largura de banda | 1 Gbps | 1 Gbps |

> O disco de dados (PostgreSQL + MinIO) deve ser separado do disco de OS para facilitar backup e expansão.

### 2.3 Portas abertas no firewall

| Porta | Protocolo | Serviço |
|---|---|---|
| 22 | TCP | SSH (restrita ao IP da equipe) |
| 80 | TCP | HTTP (redirect para HTTPS) |
| 443 | TCP | HTTPS |
| 8080 | TCP | Laravel Reverb (WebSocket) |

> Redis (6379), PostgreSQL (5432) e MinIO (9000/9001) **não devem ser expostos** ao público — acesso apenas interno via rede Docker.

---

## 3. Software Obrigatório no Servidor

Executar em cada servidor (staging e produção):

```bash
# Atualizar sistema
apt update && apt upgrade -y

# Dependências básicas
apt install -y curl git unzip nginx certbot python3-certbot-nginx ufw fail2ban

# Docker Engine
curl -fsSL https://get.docker.com | sh
usermod -aG docker $USER

# Docker Compose plugin (v2)
apt install -y docker-compose-plugin

# Verificar instalação
docker --version
docker compose version
```

---

## 4. Estrutura de Diretórios no Servidor

```
/srv/payproxy/
├── .env                        # Variáveis de ambiente (nunca versionar)
├── docker-compose.yml          # Compose de produção
├── docker-compose.override.yml # Overrides por ambiente (opcional)
├── releases/                   # Histórico de deploys
│   ├── 20260618_143000/        # release timestamp
│   └── 20260701_090000/
├── current -> releases/20260618_143000/   # symlink para release ativa
├── shared/                     # Arquivos persistentes entre releases
│   ├── .env
│   └── storage/                # Laravel storage (logs, uploads)
└── backups/
    ├── postgres/
    └── minio/
```

> A estrutura de `releases/` + `current` (symlink) é o padrão Capistrano/Deployer — permite rollback instantâneo trocando o symlink.

---

## 5. Configuração do Nginx (host)

Crie o arquivo de configuração para cada domínio:

**`/etc/nginx/sites-available/payproxy`**

```nginx
# Redirect HTTP → HTTPS
server {
    listen 80;
    server_name api.payproxy.com.br;
    return 301 https://$host$request_uri;
}

# HTTPS principal (API + Backoffice)
server {
    listen 443 ssl http2;
    server_name api.payproxy.com.br;

    ssl_certificate     /etc/letsencrypt/live/api.payproxy.com.br/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.payproxy.com.br/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;

    client_max_body_size 50M;

    location / {
        proxy_pass         http://127.0.0.1:8000;
        proxy_http_version 1.1;
        proxy_set_header   Host              $host;
        proxy_set_header   X-Real-IP         $remote_addr;
        proxy_set_header   X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto $scheme;
    }
}

# WebSocket — Laravel Reverb
server {
    listen 8080 ssl http2;
    server_name api.payproxy.com.br;

    ssl_certificate     /etc/letsencrypt/live/api.payproxy.com.br/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.payproxy.com.br/privkey.pem;

    location / {
        proxy_pass         http://127.0.0.1:9001;   # porta interna do Reverb
        proxy_http_version 1.1;
        proxy_set_header   Upgrade    $http_upgrade;
        proxy_set_header   Connection "upgrade";
        proxy_set_header   Host       $host;
    }
}
```

```bash
ln -s /etc/nginx/sites-available/payproxy /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

---

## 6. SSL com Let's Encrypt

```bash
# Emitir certificado (antes de configurar o bloco HTTPS acima)
certbot --nginx -d api.payproxy.com.br

# Verificar renovação automática
certbot renew --dry-run
```

O Certbot adiciona automaticamente um cron para renovação. Certificados expiram em 90 dias e são renovados antes dos 30 dias finais.

---

## 7. Variáveis de Ambiente — Produção

Arquivo `/srv/payproxy/.env` — **nunca versionar este arquivo**.

```env
APP_NAME="Payproxy"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.payproxy.com.br
APP_KEY=base64:GERAR_COM_php_artisan_key_generate

# Banco de dados
DB_CONNECTION=pgsql
DB_HOST=postgres        # nome do serviço Docker
DB_PORT=5432
DB_DATABASE=payproxy
DB_USERNAME=payproxy
DB_PASSWORD=SENHA_FORTE_AQUI

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=SENHA_FORTE_AQUI

# MinIO (storage)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=MINIO_ACCESS_KEY
AWS_SECRET_ACCESS_KEY=MINIO_SECRET_KEY
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=payproxy
AWS_ENDPOINT=http://minio:9000
AWS_USE_PATH_STYLE_ENDPOINT=true

# E-mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.dominio.com.br
MAIL_PORT=587
MAIL_USERNAME=noreply@payproxy.com.br
MAIL_PASSWORD=SENHA_SMTP
MAIL_FROM_ADDRESS=noreply@payproxy.com.br
MAIL_FROM_NAME="Payproxy"

# WhatsApp (Evolution API)
WHATSAPP_ENABLED=true
EVOLUTION_API_URL=http://evolution-api:8080
EVOLUTION_API_KEY=CHAVE_AQUI

# Criptografia (credenciais bancárias, TOTP, webhooks)
CRYPTO_KEY=CHAVE_AES_256_64_CHARS_HEX

# PJBank
PJBANK_CREDENCIAL=CREDENCIAL_PJBANK
PJBANK_CHAVE=CHAVE_PJBANK
PJBANK_BASE_URL=https://api.pjbank.com.br

# Filas
QUEUE_CONNECTION=redis
HORIZON_MEMORY_LIMIT=512

# Reverb (WebSocket)
REVERB_APP_ID=payproxy
REVERB_APP_KEY=CHAVE_REVERB
REVERB_APP_SECRET=SECRET_REVERB
REVERB_HOST=0.0.0.0
REVERB_PORT=9001
REVERB_SCHEME=https

# Sessão / Cache
SESSION_DRIVER=redis
CACHE_STORE=redis

# Logs
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=error
```

---

## 8. docker-compose.yml — Produção

Arquivo `/srv/payproxy/docker-compose.yml`:

```yaml
services:

  app:
    image: ghcr.io/ciberian/payproxy:${APP_VERSION:-latest}
    restart: unless-stopped
    env_file: .env
    volumes:
      - ./shared/storage:/var/www/html/storage
    ports:
      - "8000:8000"
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_healthy
    command: php artisan serve --host=0.0.0.0 --port=8000

  worker:
    image: ghcr.io/ciberian/payproxy:${APP_VERSION:-latest}
    restart: unless-stopped
    env_file: .env
    volumes:
      - ./shared/storage:/var/www/html/storage
    depends_on:
      - app
    command: php artisan horizon

  scheduler:
    image: ghcr.io/ciberian/payproxy:${APP_VERSION:-latest}
    restart: unless-stopped
    env_file: .env
    volumes:
      - ./shared/storage:/var/www/html/storage
    depends_on:
      - app
    command: php artisan schedule:work

  reverb:
    image: ghcr.io/ciberian/payproxy:${APP_VERSION:-latest}
    restart: unless-stopped
    env_file: .env
    ports:
      - "9001:9001"
    depends_on:
      - app
    command: php artisan reverb:start

  postgres:
    image: postgres:16-alpine
    restart: unless-stopped
    environment:
      POSTGRES_DB: ${DB_DATABASE}
      POSTGRES_USER: ${DB_USERNAME}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - /data/postgres:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME} -d ${DB_DATABASE}"]
      interval: 5s
      timeout: 5s
      retries: 10

  redis:
    image: redis:7-alpine
    restart: unless-stopped
    command: redis-server --requirepass ${REDIS_PASSWORD} --save 60 1
    volumes:
      - /data/redis:/data
    healthcheck:
      test: ["CMD", "redis-cli", "--no-auth-warning", "-a", "${REDIS_PASSWORD}", "ping"]
      interval: 5s
      timeout: 5s
      retries: 10

  minio:
    image: minio/minio
    restart: unless-stopped
    command: server /data --console-address ":9002"
    environment:
      MINIO_ROOT_USER: ${AWS_ACCESS_KEY_ID}
      MINIO_ROOT_PASSWORD: ${AWS_SECRET_ACCESS_KEY}
    volumes:
      - /data/minio:/data
    # Console MinIO (9002) não exposto externamente — acessar via SSH tunnel

  evolution-api:
    image: atendai/evolution-api:latest
    restart: unless-stopped
    environment:
      SERVER_TYPE: http
      SERVER_PORT: 8080
      AUTHENTICATION_API_KEY: ${EVOLUTION_API_KEY}
    volumes:
      - /data/evolution:/evolution/instances
```

> **Nota:** A imagem `ghcr.io/ciberian/payproxy` será construída e publicada no GitHub Container Registry durante o processo de release. Ver seção 10.

---

## 9. Procedimento — Primeiro Deploy

Execute os passos abaixo na ordem exata, tanto para staging quanto para produção.

### 9.1 Preparar servidor

```bash
# 1. Criar estrutura de diretórios
mkdir -p /srv/payproxy/{releases,shared/storage,backups/postgres,backups/minio}
mkdir -p /data/{postgres,redis,minio,evolution}

# 2. Clonar repositório
git clone git@github.com:ciberian/payproxy.git /srv/payproxy/repo

# 3. Copiar docker-compose.yml e .env
cp /srv/payproxy/repo/docker-compose.prod.yml /srv/payproxy/docker-compose.yml
# Editar .env com as variáveis de produção:
nano /srv/payproxy/.env
```

### 9.2 Iniciar serviços de infraestrutura

```bash
cd /srv/payproxy

# Subir apenas infraestrutura primeiro
docker compose up -d postgres redis minio

# Aguardar healthchecks
docker compose ps
```

### 9.3 Criar bucket MinIO

```bash
docker compose run --rm minio-setup   # ou via mc manualmente:
docker compose exec minio mc alias set local http://localhost:9000 $MINIO_USER $MINIO_PASS
docker compose exec minio mc mb --ignore-existing local/payproxy
```

### 9.4 Gerar APP_KEY

```bash
# Em uma instância temporária do container app:
docker run --rm ghcr.io/ciberian/payproxy:latest php artisan key:generate --show
# → Copiar o resultado e colocar em APP_KEY no .env
```

### 9.5 Executar migrations e seeders

```bash
docker compose run --rm app php artisan migrate --force
docker compose run --rm app php artisan db:seed --class=BankPartnerSeeder --force
docker compose run --rm app php artisan db:seed --class=BackofficeAdminSeeder --force
```

### 9.6 Subir todos os serviços

```bash
docker compose up -d
docker compose ps   # todos devem estar "healthy" ou "running"
```

### 9.7 Verificar saúde da aplicação

```bash
curl https://api.payproxy.com.br/up
# esperado: HTTP 200 {"status":"ok"}
```

---

## 10. Build da Imagem Docker

Cada release exige a construção e publicação da imagem. Execute a partir do repositório local ou de um servidor de build:

```bash
# No repositório do projeto
export VERSION=1.0.0

docker build \
  --tag ghcr.io/ciberian/payproxy:${VERSION} \
  --tag ghcr.io/ciberian/payproxy:latest \
  --build-arg APP_ENV=production \
  -f docker/php/Dockerfile .

docker push ghcr.io/ciberian/payproxy:${VERSION}
docker push ghcr.io/ciberian/payproxy:latest
```

O `Dockerfile` de produção fica em `docker/php/Dockerfile` e deve:
- Usar PHP 8.3-FPM Alpine como base
- Copiar o código, rodar `composer install --no-dev --optimize-autoloader`
- Rodar `npm run build` para compilar os assets Vite
- Definir `WORKDIR /var/www/html`

---

## 11. Procedimento — Deploy de Atualização

```bash
# No servidor de produção
cd /srv/payproxy

# 1. Definir versão a implantar
export VERSION=1.1.0

# 2. Baixar nova imagem
docker compose pull app worker scheduler reverb
# ou especificamente:
docker pull ghcr.io/ciberian/payproxy:${VERSION}

# 3. Rodar migrations (antes de trocar os containers)
docker compose run --rm \
  -e APP_VERSION=${VERSION} \
  app php artisan migrate --force

# 4. Subir novos containers (zero-downtime: Compose substitui um a um)
APP_VERSION=${VERSION} docker compose up -d --no-deps app worker scheduler reverb

# 5. Verificar saúde
curl https://api.payproxy.com.br/up
docker compose ps
docker compose logs --tail=50 app
```

---

## 12. Rollback

```bash
cd /srv/payproxy

# Definir versão estável anterior
export STABLE_VERSION=1.0.0

# Reverter containers para imagem anterior
APP_VERSION=${STABLE_VERSION} docker compose up -d --no-deps app worker scheduler reverb

# Reverter migration (se necessário — apenas se a migration for reversível)
docker compose run --rm app php artisan migrate:rollback --force

# Verificar
curl https://api.payproxy.com.br/up
```

> **Atenção:** migrations destrutivas (drop column, drop table) não têm rollback automático seguro. Sempre criar migrations aditivas e remover colunas/tabelas em releases futuras, nunca na mesma release.

---

## 13. Backup Automatizado

### 13.1 Script de backup

`/srv/payproxy/backup.sh`:

```bash
#!/bin/bash
set -e

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR=/srv/payproxy/backups

# PostgreSQL
docker compose -f /srv/payproxy/docker-compose.yml exec -T postgres \
  pg_dump -U payproxy payproxy | gzip \
  > ${BACKUP_DIR}/postgres/payproxy_${DATE}.sql.gz

# Remover backups PostgreSQL com mais de 30 dias
find ${BACKUP_DIR}/postgres -name "*.sql.gz" -mtime +30 -delete

echo "[${DATE}] Backup concluído: postgres OK"
```

```bash
chmod +x /srv/payproxy/backup.sh
```

### 13.2 Cron de backup

```bash
crontab -e
```

```cron
# Backup diário às 02:00
0 2 * * * /srv/payproxy/backup.sh >> /var/log/payproxy-backup.log 2>&1
```

> Recomenda-se copiar os backups para storage externo (S3 de outro provider ou bucket MinIO de outro servidor) para garantir recuperação em caso de falha total do servidor.

---

## 14. Monitoramento Básico

### 14.1 Health check endpoint

```bash
# Verificar a cada 5 minutos via cron
*/5 * * * * curl -sf https://api.payproxy.com.br/up || \
  echo "Payproxy DOWN $(date)" | mail -s "ALERTA: Payproxy fora do ar" ops@ciberian.com.br
```

### 14.2 Verificar filas (Horizon)

```bash
docker compose exec app php artisan horizon:status
# Esperado: "running"
```

### 14.3 Logs em tempo real

```bash
# Todos os serviços
docker compose logs -f

# Apenas app
docker compose logs -f app

# Laravel logs
docker compose exec app tail -f storage/logs/laravel.log
```

### 14.4 Acesso ao MinIO Console (via SSH tunnel)

O console MinIO (porta 9002) não é exposto publicamente. Para acessar:

```bash
# Na máquina local:
ssh -L 9002:localhost:9002 user@ip-do-servidor
# Acessar: http://localhost:9002
```

---

## 15. Diferenças Staging × Produção

| Item | Staging | Produção |
|---|---|---|
| Domínio | staging.payproxy.com.br | api.payproxy.com.br |
| PJBank | Ambiente de sandbox | Ambiente de produção |
| Evolution API | Desativado (`WHATSAPP_ENABLED=false`) | Ativo |
| MAIL_MAILER | `log` (e-mail só no log) | `smtp` real |
| APP_DEBUG | `false` | `false` |
| Backup | Opcional | Obrigatório diário |
| Specs VPS | 2 vCPU / 4 GB | 4+ vCPU / 8+ GB |

---

## 16. Checklist de Go-Live (Produção)

Executar antes de liberar acesso ao cliente (SEFAZ):

- [ ] Domínio DNS apontando para o IP do servidor de produção
- [ ] Certificado SSL emitido e válido (`certbot`)
- [ ] `GET /up` retorna HTTP 200
- [ ] Login no backoffice com 2FA funcional
- [ ] Criação de tenant e API key funcional
- [ ] Emissão de boleto via API retorna código de barras, linha digitável e QR Code
- [ ] Webhook do PJBank (sandbox) recebido e processado corretamente
- [ ] E-mail de notificação entregue ao contribuinte
- [ ] Job de conciliação noturna executado com sucesso (`php artisan schedule:run`)
- [ ] Backup do PostgreSQL executado e arquivo gerado em `/srv/payproxy/backups/postgres/`
- [ ] Horizon em status `running` (`php artisan horizon:status`)
- [ ] Porta 22 SSH restrita ao IP da equipe Ciberian
- [ ] Redis e PostgreSQL **não** acessíveis externamente (testar com `nmap`)
- [ ] `.env` com credenciais reais do PJBank produção (não sandbox)
- [ ] `APP_DEBUG=false` confirmado
- [ ] Logs de erro configurados para notificar a equipe

---

## 17. Contatos e Acessos

| Recurso | Responsável |
|---|---|
| Servidor de produção | Ciberian — ops |
| Credenciais PJBank produção | Ciberian — financeiro |
| DNS | Responsável pelo domínio payproxy.com.br |
| Credenciais SEFAZ (tenant) | SEFAZ Salvador — TI |
