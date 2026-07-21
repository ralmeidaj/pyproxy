# LGPD Gateway — Instruções para Claude Code

## Regras fundamentais

**Nunca altere nada antes de perguntar.** Sempre apresente a proposta de mudança e aguarde confirmação explícita antes de editar qualquer arquivo.

**Restart obrigatório após mudança de PHP.** Como `opcache.validate_timestamps=0`, qualquer alteração em arquivo PHP exige `docker restart lgpd-gateway-app` imediatamente após o fix.

**Swagger obrigatório em toda rota da API.** Qualquer rota nova ou modificada em `app/Http/Controllers/` deve ter os atributos `#[OA\...]` adicionados/atualizados no mesmo entregável. Após a alteração, executar `docker exec lgpd-gateway-app php artisan l5-swagger:generate`.

**Documentação obrigatória ao concluir qualquer implementação.** Atualizar `CLAUDE.md` e `README.md` ao finalizar qualquer módulo antes de declarar concluído.

---

## Contexto do projeto

O **LGPD Gateway** é um projeto Laravel 11 independente que atua como middleware entre o Payproxy e os sistemas LGPD municipais (ex.: Datum). Usa Strategy Pattern para rotear requisições de ROT e RIPD ao Datum Connector correto de cada município.

**Projeto relacionado:** [Payproxy](../Especificacao-Requisitos-Payproxy.md) — consome este Gateway via `DatumService` nas rotas `/backoffice/tenants/{tenant}/lgpd`.

**Especificação completa:** `Especificacao-Requisitos-LGPD-Gateway.md`

---

## Arquitetura

### Fluxo de requisição

```
Payproxy  ──GET /api/rot?tenant_id=1──►  LGPD Gateway
                                              │
                                    DatumGatewayService::resolve(1)
                                              │
                                    SalvadorDatumStrategy::getRotItems()
                                              │
                                    GET https://datum-connector.sefaz.salvador.ba.gov.br/rot?orgao_id=SSA-001
                                              │
                                         [Redis cache 5min]
                                              │
                                    ◄── RotResource::collection()
```

### Stack

| Camada | Tecnologia |
|---|---|
| Framework | Laravel 11, PHP 8.3+ |
| Cache | Redis 7 |
| Containerização | Docker |
| Documentação API | OpenAPI/Swagger (`darkaonline/l5-swagger`) |

### Estrutura de pastas

```
app/
├── Contracts/
│   └── DatumStrategyInterface.php    # getRotItems(string $orgaoId): array
│                                     # getRipdReports(string $orgaoId): array
│                                     # healthCheck(): array
├── Http/
│   ├── Controllers/
│   │   ├── RotController.php         # GET /api/rot
│   │   ├── RipdController.php        # GET /api/ripd
│   │   ├── HealthController.php      # GET /api/health
│   │   └── TenantController.php     # GET /api/tenants (admin)
│   ├── Middleware/
│   │   └── GatewayApiKeyMiddleware.php
│   └── Resources/
│       ├── RotResource.php
│       └── RipdResource.php
├── Models/
│   └── GatewayLog.php
└── Services/
    ├── DatumGatewayService.php       # factory + cache + log
    └── Strategies/
        ├── SalvadorDatumStrategy.php
        └── AlagoínhasDatumStrategy.php
config/
└── datum_clients.php                 # mapa tenant_id → Strategy + URL + key
```

---

## Padrão de implementação de nova Strategy

Para adicionar um novo cliente (ex.: Jaguariúna):

1. Criar `app/Services/Strategies/JaguariunaDatumStrategy.php` implementando `DatumStrategyInterface`
2. Adicionar entrada em `config/datum_clients.php`:
```php
'3' => [
    'strategy'  => \App\Services\Strategies\JaguariunaDatumStrategy::class,
    'url'       => env('DATUM_JAGUARIUNA_URL'),
    'api_key'   => env('DATUM_JAGUARIUNA_KEY'),
    'orgao_id'  => env('DATUM_JAGUARIUNA_ORGAO_ID'),
    'timeout'   => 5,
    'cache_ttl' => 300,
],
```
3. Adicionar variáveis no `.env`
4. Zero alteração no Payproxy

---

## Numeração de requisitos

| Prefixo | Tipo | Intervalo |
|---|---|---|
| `RF-GW-xx` | Funcional — LGPD Gateway | RF-GW-01 a RF-GW-14 |
| `RF-DC-xx` | Funcional — Datum Connector (.NET) | RF-DC-01 a RF-DC-06 |
| `RNF-GW-xx` | Não Funcional | RNF-GW-01 a RNF-GW-09 |

---

## Variáveis de ambiente críticas

| Variável | Descrição |
|---|---|
| `GATEWAY_API_KEY` | Key de autenticação usada pelo Payproxy (`X-Api-Key`) |
| `GATEWAY_ADMIN_KEY` | Key administrativa para `GET /api/tenants` |
| `ALERT_EMAIL` | E-mail para alertas de falha consecutiva de Connector |
| `DATUM_{CLIENTE}_URL` | URL do Datum Connector do cliente |
| `DATUM_{CLIENTE}_KEY` | API Key do Datum Connector do cliente |
| `DATUM_{CLIENTE}_ORGAO_ID` | ID do órgão no sistema Datum do cliente |

---

## Testes

- **Unitários:** mockar `DatumStrategyInterface` — testar factory, cache hit/miss, isolamento de falhas
- **Integração:** usar Datum Connector stub (resposta JSON fixo) — testar fluxo completo Controller → Strategy → cache → Resource
- Nunca mockar o Redis nos testes de integração — usar Redis real do Docker

---

## Datum Connector (.NET)

Projeto separado em `datum-connector/` (template). Cada cliente recebe uma cópia configurada. O Connector:
- Lê ROT e RIPD diretamente do SQL Server do Datum
- Expõe `GET /rot`, `GET /ripd` e `GET /health`
- Autenticado por `X-Api-Key`
- Repository por versão do schema Datum (`RotRepositoryV1`, `RotRepositoryV2`)
- Deploy: Docker no servidor do cliente ou IIS

O Gateway nunca acessa o SQL Server do Datum diretamente — sempre via Connector.
