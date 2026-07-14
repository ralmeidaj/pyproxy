# Payproxy

Plataforma SaaS intermediária para geração de boletos bancários com split de pagamento, registro DDA (Débito Direto Autorizado) e AR Digital (Aviso de Recebimento com validade jurídica ICP-Brasil).

Contratada pela **SEFAZ Salvador** (Secretaria Municipal da Fazenda de Salvador/BA).

---

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 11, PHP 8.3+ |
| Banco de dados | PostgreSQL 16 |
| Cache / Filas / Rate limiting | Redis 7 |
| Storage (PDFs, laudos) | MinIO (S3-compatible) |
| Frontend | Inertia.js + Vue 3 + Tailwind CSS 4 |
| Real-time | Laravel Reverb (WebSocket) |
| WhatsApp | OVC360 API via ouvimosvc.com.br (BSP Meta credenciado) |
| Build | Vite 6 |

---

## Módulos implementados

### Core — Emissão de Boletos
- API REST pública autenticada por API Key com escopos granulares
- Suporte a split de pagamento com múltiplos favorecidos
- Adapter pattern para parceiros bancários (v1: PJBank)
- Registro DDA via FEBRABAN/BACEN
- Portal do tenant (Inertia.js) para gestão de boletos e usuários

### AR Digital
Módulo nativo de Aviso de Recebimento Digital com validade jurídica:
- Pixel de rastreamento de e-mail (evento `leitura_pixel`)
- DSN SMTP para confirmação de entrega pelo servidor do destinatário
- Landing page pública com confirmação de recebimento por CPF
- Carimbos de tempo RFC 3161 via ACT ICP-Brasil (Serpro, BRy, Soluti, Certisign)
- Envio de notificação via WhatsApp (template Meta aprovado)
- Geração de laudo PDF com cadeia de evidências, QR code e informações LGPD

### Backoffice
Interface administrativa para a equipe Ciberian:
- Gestão de tenants com controle de status e histórico
- Configuração de boleto (split, parceiro bancário)
- Gestão de API Keys
- Configuração de AR Digital por tenant
- Visualização de boletos e timeline de notificações AR
- Relatórios e logs de auditoria

---

## Pré-requisitos

- Docker Desktop
- Node.js 20+
- PHP 8.3+ (para `composer` local, opcional)

---

## Setup de desenvolvimento

```bash
# 1. Copiar variáveis de ambiente
cp .env.example .env

# 2. Subir os serviços Docker
docker compose up -d

# 3. Instalar dependências PHP
docker exec payproxy-app composer install

# 4. Gerar chave da aplicação
docker exec payproxy-app php artisan key:generate

# 5. Executar migrations e seeds
docker exec payproxy-app php artisan migrate --seed

# 6. Instalar dependências JS e buildar
npm install
npm run build
```

Acesse em: **http://localhost:8000**

---

## Comandos frequentes

```bash
# Rebuild do frontend (obrigatório após qualquer mudança em .vue)
npm run build

# Restart do container PHP (obrigatório após mudanças em .php — OPcache)
docker restart payproxy-app

# Rodar migrations
docker exec payproxy-app php artisan migrate

# Fila de jobs (modo local)
docker exec payproxy-app php artisan queue:work

# Gerar documentação Swagger
docker exec payproxy-app php artisan l5-swagger:generate

# Rodar testes
docker exec payproxy-app php artisan test
```

---

## Variáveis de ambiente críticas

| Variável | Descrição |
|---|---|
| `OVC360_ENABLED` | Ativa envio de WhatsApp via OVC360 / Ouvimos BSP |
| `OVC360_INTEGRATION_KEY` | Chave de integração fornecida pela Ouvimos (`X-Integration-Key`) |
| `OVC360_ENDPOINT` | URL do webhook OVC360 (padrão: endpoint Ciberian em ouvimosvc.com.br) |
| `META_WA_ENABLED` | Ativa recebimento de webhooks de entrega WhatsApp (AR Digital) |
| `META_WA_WEBHOOK_VERIFY_TOKEN` | Token de verificação do webhook Meta (necessário mesmo sem envio direto) |
| `AWS_*` | Credenciais MinIO para storage de PDFs e laudos AR |
| `PJBANK_CEDENTE` | Credencial PJBank para emissão de boletos |
| `ACT_ENABLED` | Ativa carimbos RFC 3161 reais (ICP-Brasil ou FreeTSA) |
| `ACT_SERPRO_URL` / `ACT_SERPRO_USER` / `ACT_SERPRO_PASSWORD` | Credenciais Serpro (produção) |
| `ACT_BRY_URL` / `ACT_SOLUTI_URL` / `ACT_CERTISIGN_URL` | URLs dos demais provedores ACT |

---

## Estrutura de pastas relevante

```
app/
├── Contracts/          # Interfaces (BankPartnerInterface)
├── DTOs/               # Data Transfer Objects (imutáveis, readonly)
├── Http/
│   ├── Controllers/
│   │   ├── Api/V1/     # API pública REST (API Key auth)
│   │   ├── Backoffice/ # Inertia — admin Ciberian
│   │   └── Portal/     # Inertia — tenant
│   └── Requests/       # Form Requests (validação PT-BR)
├── Jobs/               # Laravel Jobs (filas Redis)
├── Models/             # Eloquent (soft-delete padrão)
└── Services/
    ├── ArDigitalService.php         # Orquestra o fluxo AR Digital
    ├── ArEvidencePdfService.php     # Gera laudo PDF (DomPDF + QR code)
    ├── ArTrackingService.php        # Pixel, token, hash, confirmação CPF
    ├── Rfc3161TimestampService.php  # Carimbos de tempo ICP-Brasil
    ├── BankPartners/
    │   ├── PJBankService.php        # Adapter PJBank (único parceiro v1)
    │   └── BankPartnerFactory.php
    ├── BoletoService.php
    └── SplitService.php
```

---

## Rotas públicas AR Digital

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/ar/pixel/{token}` | Pixel de rastreamento 1×1 |
| `GET` | `/ar/boleto/{token}` | Landing page do destinatário |
| `POST` | `/ar/boleto/{token}/confirmar` | Confirmação de recebimento (CPF) |
| `POST` | `/api/webhooks/smtp-dsn` | Webhook de DSN SMTP (entrega/bounce) |
| `GET` | `/api/webhooks/meta-whatsapp` | Verificação do webhook Meta |
| `POST` | `/api/webhooks/meta-whatsapp` | Eventos de entrega WhatsApp |

---

## Documentação

- **Especificação de Requisitos:** `Especificacao-Requisitos-Payproxy.md`
- **Swagger/OpenAPI:** http://localhost:8000/api/documentation
- **Instruções para IA:** `CLAUDE.md`
