# LGPD Gateway

Middleware Laravel entre o **Payproxy** e os sistemas de gestão LGPD municipais (ex.: Datum). Padroniza o acesso a ROT (Registro de Operações de Tratamento) e RIPD (Relatório de Impacto à Proteção de Dados) de cada município via Strategy Pattern — adicionar um novo cliente não exige nenhuma alteração no Payproxy.

---

## Como funciona

```
Payproxy  →  LGPD Gateway  →  Datum Connector (por município)  →  Datum SQL Server
```

O Gateway recebe uma requisição do Payproxy com `tenant_id`, identifica a Strategy do município correspondente, consulta o Datum Connector instalado no servidor da prefeitura, cacheia a resposta no Redis e retorna os dados de ROT/RIPD normalizados.

---

## Stack

| Camada | Tecnologia |
|---|---|
| Framework | Laravel 11, PHP 8.3+ |
| Cache | Redis 7 |
| Containerização | Docker |
| Datum Connector | .NET 8 WebAPI (template separado) |

---

## Setup de desenvolvimento

```bash
# 1. Copiar variáveis de ambiente
cp .env.example .env

# 2. Subir os serviços Docker
docker compose up -d

# 3. Instalar dependências PHP
docker exec lgpd-gateway-app composer install

# 4. Gerar chave da aplicação
docker exec lgpd-gateway-app php artisan key:generate

# 5. Rodar migrations
docker exec lgpd-gateway-app php artisan migrate
```

---

## Endpoints

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| `GET` | `/api/rot?tenant_id={id}` | `X-Api-Key` | Retorna ROT do tenant |
| `GET` | `/api/ripd?tenant_id={id}` | `X-Api-Key` | Retorna RIPD do tenant |
| `GET` | `/api/health?tenant_id={id}` | `X-Api-Key` | Status do Connector do tenant |
| `GET` | `/api/tenants` | `X-Admin-Key` | Lista todos os tenants configurados |

### Exemplo de requisição

```bash
curl -H "X-Api-Key: sua-key" \
  "https://lgpd-gateway.ciberian.com.br/api/rot?tenant_id=1"
```

### Headers de resposta relevantes

| Header | Descrição |
|---|---|
| `X-Cache` | `HIT` ou `MISS` |
| `X-Cache-TTL` | Segundos restantes no cache |
| `X-Datum-Status` | `ok`, `cached` (Connector indisponível, usando cache) ou `unavailable` |

---

## Variáveis de ambiente críticas

| Variável | Descrição |
|---|---|
| `GATEWAY_API_KEY` | Key usada pelo Payproxy para autenticar (`X-Api-Key`) |
| `GATEWAY_ADMIN_KEY` | Key administrativa para `GET /api/tenants` |
| `ALERT_EMAIL` | E-mail de alerta de falhas consecutivas de Connector |
| `ALERT_CONSECUTIVE_FAILURES` | Número de falhas antes de alertar (padrão: 5) |
| `DATUM_{CLIENTE}_URL` | URL do Datum Connector do cliente |
| `DATUM_{CLIENTE}_KEY` | API Key do Datum Connector do cliente |
| `DATUM_{CLIENTE}_ORGAO_ID` | ID do órgão no sistema Datum do cliente |

---

## Adicionar novo município

1. Criar `app/Services/Strategies/NomeCidadeDatumStrategy.php` implementando `DatumStrategyInterface`
2. Adicionar entrada em `config/datum_clients.php` com a Strategy, URL, key e orgao_id
3. Adicionar variáveis no `.env`
4. Instalar o **Datum Connector** (.NET) no servidor da prefeitura
5. Reiniciar o container: `docker restart lgpd-gateway-app`

Nenhuma alteração no Payproxy é necessária.

---

## Datum Connector (.NET)

Template de WebAPI .NET 8 fornecido pela Ciberian para instalação no servidor de cada município. Lê ROT e RIPD diretamente do banco SQL Server do Datum e os expõe via REST.

**Repositório:** `datum-connector/` (template)

**Endpoints do Connector:**

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/rot?orgao_id={id}` | Retorna itens de ROT |
| `GET` | `/ripd?orgao_id={id}` | Retorna relatórios RIPD |
| `GET` | `/health` | Status e versão do Datum detectada |

**Instalação no cliente:**
```bash
# Via Docker (recomendado)
docker run -p 8080:8080 \
  -e ConnectionStrings__Datum="Server=...;Database=datum;..." \
  -e ApiKey="chave-gerada" \
  -e OrgaosPermitidos="SSA-001" \
  ciberian/datum-connector:latest

# Ou via IIS (Windows Server)
# Ver documentação em datum-connector/README-IIS.md
```

---

## Comandos frequentes

```bash
# Restart do container PHP (obrigatório após mudanças em .php — OPcache)
docker restart lgpd-gateway-app

# Gerar documentação Swagger
docker exec lgpd-gateway-app php artisan l5-swagger:generate

# Limpar cache Redis do Gateway
docker exec lgpd-gateway-app php artisan cache:flush

# Rodar testes
docker exec lgpd-gateway-app php artisan test

# Verificar status de todos os Connectors
curl -H "X-Admin-Key: sua-admin-key" \
  "https://lgpd-gateway.ciberian.com.br/api/tenants"
```

---

## Documentação

- **Especificação de Requisitos:** `Especificacao-Requisitos-LGPD-Gateway.md`
- **Instruções para IA:** `CLAUDE.md`
- **Swagger/OpenAPI:** http://localhost:8001/api/documentation
- **Projeto consumidor:** [Payproxy](../Especificacao-Requisitos-Payproxy.md) (RF-LGPD-13 a RF-LGPD-15)
