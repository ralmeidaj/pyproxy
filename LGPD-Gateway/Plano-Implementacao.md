# Plano de Implementação — LGPD Gateway

## Contexto

O LGPD Gateway é um projeto Laravel 11 independente que atua como middleware entre o Payproxy e os sistemas de gestão LGPD municipais (Datum). Usa Strategy Pattern para rotear por tenant — adicionar um município novo não exige nenhuma alteração no Payproxy.

### Já documentado (não implementado)

- Especificação: `Especificacao-Requisitos-LGPD-Gateway.md` (RF-GW-01 a RF-GW-14, RF-DC-01 a RF-DC-06, RNF-GW-01 a RNF-GW-09)
- Arquitetura: `Arquitetura-LGPD-Gateway.html`
- Convenções: `CLAUDE.md`

---

## Fase 1 — Scaffold e Infraestrutura (estimativa: 3 dias)

### 1.1 Criação do projeto Laravel

**Por quê:** O repositório atual tem apenas documentação — o projeto PHP não existe ainda.

**O que criar:**
- Executar `composer create-project laravel/laravel .` dentro da pasta `LGPD-Gateway/`
- Instalar dependências necessárias:
  - `darkaonline/l5-swagger` — Swagger/OpenAPI
  - `predis/predis` — Redis
  - `guzzlehttp/guzzle` — chamadas HTTP ao Datum Connector
- `.env.example` com todas as variáveis documentadas no `CLAUDE.md`
- `config/datum_clients.php` com estrutura vazia e comentários de exemplo

**Validação:** `php artisan --version` retorna `Laravel Framework 11.x`.

---

### 1.2 Docker Compose

**Por quê:** A infra Ciberian roda em Docker. Desenvolvimento local precisa replicar o ambiente de produção.

**O que criar:**
- `docker-compose.yml` com serviços: `app` (PHP 8.3-FPM), `nginx`, `redis`
- `docker/nginx/default.conf` — proxy reverso para PHP-FPM, bloqueio de HTTP (apenas HTTPS em produção)
- `docker/php/Dockerfile` com PHP 8.3 + extensões: `redis`, `pdo_pgsql`, `bcmath`, `opcache`

**Validação:** `docker compose up -d` → `curl http://localhost:8001/api/health` retorna 401 (middleware ativo).

---

### 1.3 Migration: gateway_logs

**Por quê:** RF-GW-14 exige auditoria de toda requisição recebida.

**O que criar:**
- `database/migrations/*_create_gateway_logs_table.php`:
  ```
  tenant_id      varchar(20) not null
  endpoint       varchar(50) not null     -- 'rot' | 'ripd' | 'health' | 'tenants'
  result         varchar(20) not null     -- 'success' | 'not_configured' | 'unavailable' | 'cached'
  items_count    smallint default 0
  latency_ms     integer not null         -- latência total Gateway
  connector_ms   integer nullable         -- latência só do Connector
  cache_hit      boolean default false
  created_at     timestamp with time zone
  ```
- `app/Models/GatewayLog.php` com `$fillable` declarado

**Validação:** `php artisan migrate` sem erros.

---

## Fase 2 — Autenticação e Strategy Core (estimativa: 2 dias)

### 2.1 GatewayApiKeyMiddleware

**Por quê:** RF-GW-01 — toda requisição sem key válida retorna 401. RF-GW-02 — keys armazenadas como hash SHA-256.

**O que criar:**
- `app/Http/Middleware/GatewayApiKeyMiddleware.php`:
  - Lê `X-Api-Key` do header
  - Compara `hash('sha256', $key)` contra `config('gateway.api_key_hash')`
  - Rota `GET /api/tenants` verifica separadamente contra `config('gateway.admin_key_hash')`
  - Retorna `401` com body `{"error": "Unauthorized"}` se inválida
- `config/gateway.php` com `api_key_hash` e `admin_key_hash` (gerados via `hash('sha256', env('GATEWAY_API_KEY'))`)
- Registrar middleware em `bootstrap/app.php` no grupo de rotas da API

**Validação:** `curl /api/rot` sem header → 401. Com header correto → 200 (ou 422 se sem `tenant_id`).

---

### 2.2 DatumStrategyInterface

**Por quê:** Define o contrato que toda Strategy deve cumprir — desacoplamento entre o Gateway e cada implementação de cliente.

**O que criar:**
```php
// app/Contracts/DatumStrategyInterface.php
interface DatumStrategyInterface
{
    public function getRotItems(string $orgaoId): array;
    public function getRipdReports(string $orgaoId): array;
    public function healthCheck(): array;
}
```

**Validação:** PHPStan não reporta erros nas implementações da interface.

---

### 2.3 DatumGatewayService — Factory

**Por quê:** RF-GW-06 e RF-GW-07 — factory resolve o `tenant_id` para a Strategy correta e retorna `not_configured` quando não mapeado.

**O que criar:**
- `app/Services/DatumGatewayService.php` com método `resolve(string $tenantId): DatumStrategyInterface|null`
- Lê `config('datum_clients')` e instancia a classe `strategy` correspondente via `app()->make()`
- Retorna `null` quando `tenant_id` não está no config (caller gera resposta `not_configured`)

**Validação:** Teste unitário — `DatumGatewayService::resolve('99')` retorna `null`.

---

## Fase 3 — Endpoints da API (estimativa: 3 dias)

### 3.1 RotController + RotResource

**Por quê:** RF-GW-02 — endpoint principal consumido pelo Payproxy.

**O que criar:**
- `app/Http/Controllers/RotController.php`:
  1. Valida presença de `tenant_id` (422 se ausente)
  2. Resolve Strategy via `DatumGatewayService`
  3. Se null → retorna `not_configured` (200)
  4. Chama `strategy->getRotItems(orgaoId)`
  5. Retorna `RotResource::collection()`
  6. Salva `GatewayLog`
- `app/Http/Resources/RotResource.php` — normaliza campos do ROT
- Annotations Swagger `#[OA\Get(...)]` no controller

**Validação:** `GET /api/rot?tenant_id=1` → retorna array de itens ROT com campos `id`, `operacao`, `categorias_dados`, `base_legal`, `status`.

---

### 3.2 RipdController + RipdResource

**Por quê:** RF-GW-03 — segundo endpoint consumido pelo Payproxy.

**O que criar:**
- `app/Http/Controllers/RipdController.php` — mesma estrutura do RotController
- `app/Http/Resources/RipdResource.php`
- Annotations Swagger

**Validação:** `GET /api/ripd?tenant_id=1` → retorna array de relatórios RIPD.

---

### 3.3 HealthController

**Por quê:** RF-GW-04 — permite ao Payproxy verificar disponibilidade do Connector antes de exibir o painel ROT/RIPD.

**O que criar:**
- `app/Http/Controllers/HealthController.php`:
  - Resolve Strategy do tenant
  - Chama `strategy->healthCheck()` com timeout de 3s
  - Retorna status, URL do Connector, versão Datum e latência
- Annotations Swagger

**Validação:** `GET /api/health?tenant_id=1` → retorna `{"connector_status": "online", "latency_ms": 124}`.

---

### 3.4 TenantController (admin)

**Por quê:** RF-GW-05 — visão operacional de todos os tenants configurados. Uso exclusivo da Ciberian.

**O que criar:**
- `app/Http/Controllers/TenantController.php`:
  - Requer `X-Admin-Key` (validado pelo middleware)
  - Itera `config('datum_clients')` e chama `healthCheck()` em cada Strategy
  - Retorna lista com status de cada Connector
- Rota protegida separada em `routes/api.php`

**Validação:** `GET /api/tenants` com `X-Admin-Key` → lista todos os tenants com status online/offline.

---

### 3.5 Swagger / OpenAPI

**Por quê:** CLAUDE.md — obrigatório. Documentação para o Payproxy consumir e para integração futura de clientes.

**O que criar:**
- `config/l5-swagger.php` configurado para o Gateway
- Annotations `#[OA\Info(...)]`, `#[OA\SecurityScheme(...)]` em `app/Http/Controllers/Controller.php`
- Executar `php artisan l5-swagger:generate` após cada controller

**Validação:** `GET /api/documentation` → Swagger UI exibe os 4 endpoints com exemplos de request/response.

---

## Fase 4 — Cache e Resiliência (estimativa: 3 dias)

### 4.1 Redis Cache com TTL por Tenant

**Por quê:** RF-GW-09 — ROT e RIPD mudam raramente; cache de 5 min elimina latência do Connector na maioria das requisições. RF-GW-10 — headers X-Cache e X-Cache-TTL para observabilidade.

**O que criar:**
- No `DatumGatewayService`, envolver chamadas ao Connector com `Cache::remember()`:
  - Chave: `datum.{tipo}.{tenant_id}` (ex: `datum.rot.1`)
  - TTL: lido de `config('datum_clients.{id}.cache_ttl')`, padrão 300s
- Adicionar aos responses os headers:
  - `X-Cache: HIT` ou `MISS`
  - `X-Cache-TTL: {segundos_restantes}` (via `Cache::getMultiple()` com TTL store)
  - `X-Datum-Status: ok | cached | unavailable`

**Validação:** Segunda chamada para mesmo tenant → `X-Cache: HIT`, latência < 20ms.

---

### 4.2 Fallback Graceful (Connector Indisponível)

**Por quê:** RF-GW-11 — falha do Connector de um município não deve retornar 5xx ao Payproxy. RF-GW-13 — isolamento entre tenants.

**O que criar:**
- No `DatumGatewayService`, envolver `getRotItems()` e `getRipdReports()` com `try/catch`:
  - Timeout do Guzzle em 5s (configurável por tenant em `datum_clients.php`)
  - Se falha + cache válido → retorna cache com `X-Datum-Status: cached`
  - Se falha + sem cache → retorna `200` com `{"status": "unavailable", "items": []}`
  - Nunca propaga exceção para o controller → nunca retorna 5xx

**Validação:** Desligar Connector stub → resposta continua 200, `X-Datum-Status: unavailable`.

---

### 4.3 Log de Falhas e Alerta por E-mail

**Por quê:** RF-GW-12 — N falhas consecutivas (padrão: 5 em 10 min) geram alerta à equipe Ciberian.

**O que criar:**
- No `DatumGatewayService`, ao registrar falha: incrementar contador no Redis com TTL de 10 min
  - Chave: `datum.failures.{tenant_id}`
- Quando contador atingir `config('gateway.alert_consecutive_failures')` (padrão 5):
  - Enviar e-mail para `config('gateway.alert_email')` via Laravel Mail
  - Resetar contador para evitar flood de e-mails (cooldown de 30 min por tenant)
- Campos no `GatewayLog` já cobrem o histórico de falhas para análise

**Validação:** Simular 5 falhas consecutivas → e-mail recebido em `ALERT_EMAIL`.

---

## Fase 5 — Primeira Strategy Real: Salvador (estimativa: 2 dias)

### 5.1 SalvadorDatumStrategy

**Por quê:** RF-GW-08 — validar o padrão com um cliente real antes de escalar para outros municípios.

**O que criar:**
- `app/Services/Strategies/SalvadorDatumStrategy.php` implementando `DatumStrategyInterface`:
  - Guzzle HTTP client com `base_uri`, `X-Api-Key` e timeout lidos do `config('datum_clients.1')`
  - `getRotItems()` → `GET {url}/rot?orgao_id={orgaoId}`
  - `getRipdReports()` → `GET {url}/ripd?orgao_id={orgaoId}`
  - `healthCheck()` → `GET {url}/health` com timeout de 3s
  - Normaliza resposta do Connector para o schema do Resource
- Entrada no `config/datum_clients.php`:
  ```php
  '1' => [
      'strategy'  => SalvadorDatumStrategy::class,
      'url'       => env('DATUM_SALVADOR_URL'),
      'api_key'   => env('DATUM_SALVADOR_KEY'),
      'orgao_id'  => env('DATUM_SALVADOR_ORGAO_ID'),
      'timeout'   => 5,
      'cache_ttl' => 300,
  ],
  ```

**Validação:** `GET /api/rot?tenant_id=1` contra Connector stub → resposta normalizada corretamente.

---

### 5.2 Testes — Unitário e Integração

**Por quê:** Garantir que o padrão está correto antes de adicionar mais Strategies.

**O que criar:**
- `tests/Unit/Services/DatumGatewayServiceTest.php`:
  - Mock da `DatumStrategyInterface` — testar factory, `not_configured`, isolamento
- `tests/Feature/Api/RotControllerTest.php`:
  - Stub HTTP (Guzzle mock) para o Datum Connector
  - Testar cache HIT/MISS, fallback de falha, log gravado

**Validação:** `php artisan test` → todos passam.

---

## Fase 6 — Datum Connector .NET Template (estimativa: 1 semana)

### 6.1 Template .NET 8 WebAPI

**Por quê:** RF-DC-01 a RF-DC-06 — cada município recebe uma cópia configurada para seu ambiente Datum.

**O que criar** (pasta `datum-connector/`):
- **ApiKeyMiddleware.cs** — valida `X-Api-Key` contra `appsettings.json`
- **IRotRepository.cs / IRipdRepository.cs** — interfaces dos repositories
- **RotRepositoryV1.cs** — queries para schema Datum v1.x
- **RotRepositoryV2.cs** — queries para schema Datum v2.x
- **DatumVersionDetector.cs** — detecta versão na inicialização via query ao SQL Server
- **Controllers:** `RotController.cs`, `RipdController.cs`, `HealthController.cs`
- **Models:** `RotItem.cs`, `RipdReport.cs` (normalização para o schema do Gateway)
- **appsettings.json:**
  ```json
  {
    "ApiKey": "",
    "OrgaosPermitidos": ["SSA-001"],
    "ConnectionStrings": {
      "Datum": "Server=localhost;Database=datum;..."
    }
  }
  ```
- **Dockerfile** para deploy via container
- **README-instalacao.md** — guia completo Docker + IIS para equipe técnica da prefeitura

**Segurança RF-DC-06:** `orgao_id` validado contra `OrgaosPermitidos` → 403 se não autorizado.

**Validação:** `docker run` do Connector com SQL Server de teste → `GET /health` retorna versão Datum detectada.

---

## Fase 7 — Hardening e Deploy (estimativa: 3 dias)

### 7.1 TLS 1.3 Obrigatório

**Por quê:** RNF-GW-01 — toda comunicação Payproxy → Gateway e Gateway → Connector deve ser HTTPS/TLS 1.3.

**O que criar:**
- `docker/nginx/default.conf`: `ssl_protocols TLSv1.3;` + redirect HTTP → HTTPS
- Documentar obtenção de certificado (Let's Encrypt via Certbot)

**Validação:** `curl http://...` → redirect 301. Conexão sem TLS 1.3 recusada.

---

### 7.2 Benchmark de Performance

**Por quê:** RNF-GW-05 — p95 < 200ms sem cache, < 20ms com cache.

**O que criar:**
- Script de benchmark com `ab` (Apache Bench) ou `wrk`:
  ```bash
  # Sem cache (MISS)
  ab -n 100 -c 10 -H "X-Api-Key: ..." "http://localhost:8001/api/rot?tenant_id=1"

  # Com cache (HIT) — segunda rodada
  ab -n 1000 -c 50 -H "X-Api-Key: ..." "http://localhost:8001/api/rot?tenant_id=1"
  ```
- Documentar resultados em `benchmarks/` com data e configuração

**Validação:** p95 < 200ms (MISS), p95 < 20ms (HIT).

---

### 7.3 Documentação de Instalação do Connector

**Por quê:** RNF-GW-09 — equipe da prefeitura deve conseguir instalar sem apoio presencial.

**O que criar:**
- `datum-connector/README-instalacao.md`:
  - Pré-requisitos: Docker ou IIS + .NET 8 Runtime
  - Geração da API Key: `openssl rand -hex 32`
  - Configuração do `appsettings.json`
  - Verificação de conectividade com Datum SQL Server
  - Teste de smoke: `curl /health`
  - Procedimento de atualização
  - Troubleshooting: erros comuns e soluções

---

## Ordem de entrega sugerida

| # | Item | Fase | Esforço |
|---|------|------|---------|
| 1 | Scaffold Laravel + Docker | 1 | 1 dia |
| 2 | Migration gateway_logs | 1 | 0,5 dia |
| 3 | GatewayApiKeyMiddleware | 2 | 1 dia |
| 4 | DatumStrategyInterface + DatumGatewayService factory | 2 | 1 dia |
| 5 | RotController + RotResource + Swagger | 3 | 1 dia |
| 6 | RipdController + RipdResource | 3 | 0,5 dia |
| 7 | HealthController + TenantController | 3 | 1 dia |
| 8 | Redis cache + headers X-Cache | 4 | 1,5 dias |
| 9 | Fallback graceful (Connector offline) | 4 | 1 dia |
| 10 | Alerta por e-mail (N falhas consecutivas) | 4 | 0,5 dia |
| 11 | SalvadorDatumStrategy | 5 | 1 dia |
| 12 | Testes unitários + integração | 5 | 1 dia |
| 13 | .NET template: Controllers + Models | 6 | 2 dias |
| 14 | .NET template: Repositories V1/V2 + version detector | 6 | 2 dias |
| 15 | .NET template: Dockerfile + README instalação | 6 | 1 dia |
| 16 | TLS 1.3 no Nginx | 7 | 0,5 dia |
| 17 | Benchmark de performance | 7 | 0,5 dia |
| 18 | Documentação final | 7 | 1 dia |

**Estimativa total: ~3 semanas**

---

## Arquivos a criar (resumo)

### Laravel (LGPD Gateway)

```
app/Contracts/DatumStrategyInterface.php
app/Http/Controllers/RotController.php
app/Http/Controllers/RipdController.php
app/Http/Controllers/HealthController.php
app/Http/Controllers/TenantController.php
app/Http/Middleware/GatewayApiKeyMiddleware.php
app/Http/Resources/RotResource.php
app/Http/Resources/RipdResource.php
app/Models/GatewayLog.php
app/Services/DatumGatewayService.php
app/Services/Strategies/SalvadorDatumStrategy.php
app/Services/Strategies/AlagoínhasDatumStrategy.php
config/datum_clients.php
config/gateway.php
database/migrations/*_create_gateway_logs_table.php
routes/api.php
docker-compose.yml
docker/nginx/default.conf
docker/php/Dockerfile
tests/Unit/Services/DatumGatewayServiceTest.php
tests/Feature/Api/RotControllerTest.php
```

### .NET Connector (datum-connector/)

```
datum-connector/
├── src/
│   ├── Controllers/RotController.cs
│   ├── Controllers/RipdController.cs
│   ├── Controllers/HealthController.cs
│   ├── Middleware/ApiKeyMiddleware.cs
│   ├── Repositories/IRotRepository.cs
│   ├── Repositories/RotRepositoryV1.cs
│   ├── Repositories/RotRepositoryV2.cs
│   ├── Services/DatumVersionDetector.cs
│   └── Models/RotItem.cs
├── appsettings.json
├── Dockerfile
└── README-instalacao.md
```

---

## Dependência com o Payproxy

O lado Payproxy desta integração (item #16 do plano do Payproxy) pode ser desenvolvido em paralelo com a Fase 3 do Gateway:

- `app/Services/DatumService.php` no Payproxy — apenas uma chamada HTTP ao Gateway
- `.env` Payproxy: `LGPD_GATEWAY_URL` + `LGPD_GATEWAY_KEY`
- Painel `Backoffice/Tenants/Lgpd.vue` — read-only, consome `DatumService`
- Se `LGPD_GATEWAY_URL` não configurado → painel exibe aviso de "não configurado"

A integração Payproxy → Gateway só pode ser testada de ponta a ponta após a Fase 5 (SalvadorStrategy com Connector stub funcionando).
