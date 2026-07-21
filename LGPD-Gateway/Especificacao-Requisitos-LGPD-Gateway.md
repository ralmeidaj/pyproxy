# Especificação de Requisitos — LGPD Gateway

## Contexto

O **LGPD Gateway** é um projeto Laravel independente desenvolvido pela Ciberian que atua como camada de integração entre o **Payproxy** e o sistema de gestão LGPD de cada município cliente (ex.: Datum). Cada município mantém seu registro de operações de tratamento (ROT) e relatórios de impacto (RIPD) no seu próprio sistema LGPD, instalado no servidor da prefeitura. O Gateway padroniza o acesso a esses dados via Strategy Pattern — um novo cliente é integrado adicionando uma nova Strategy, sem nenhuma alteração no Payproxy.

### Posicionamento no ecossistema

```
Payproxy  ──HTTPS / X-Api-Key──►  LGPD Gateway (este projeto)
                                       │
                              Strategy Factory (por tenant_id)
                                       │
               ┌───────────────────────┼───────────────────────┐
               ▼                       ▼                       ▼
   Datum Connector Salvador   Datum Connector Alagoinhas  Datum Connector X
   (.NET — servidor SEFAZ)    (.NET — servidor Alagoinhas) (.NET — servidor X)
               │                       │                       │
         Datum SQL Server        Datum SQL Server        Datum SQL Server
```

### Componentes

| Componente | Tecnologia | Responsável | Onde roda |
|---|---|---|---|
| LGPD Gateway | Laravel 11 / PHP 8.3 | Ciberian | Infra Ciberian (Docker) |
| Datum Connector | .NET 8 WebAPI (template) | Ciberian | Servidor do cliente |
| Datum | Sistema LGPD municipal (terceiro) | Prefeitura | Servidor do cliente |

---

## 1. Requisitos Funcionais — LGPD Gateway

### 1.1 API REST exposta ao Payproxy

**RF-GW-01** O Gateway deve expor uma API REST autenticada por API Key via header `X-Api-Key`. Toda requisição sem key válida retorna HTTP 401.

**RF-GW-02** `GET /api/rot?tenant_id={id}` retorna a lista de itens do ROT (Registro de Operações de Tratamento) do tenant informado, no formato:

```json
{
  "tenant_id": "42",
  "orgao": "SEFAZ Salvador",
  "items": [
    {
      "id": "rot-001",
      "operacao": "Emissão de boleto de cobrança",
      "categorias_dados": ["Nome", "CPF", "Endereço", "E-mail"],
      "finalidade": "Cobrança de tributos municipais",
      "base_legal": "Obrigação Legal",
      "responsavel_dpo": "Maria Silva",
      "ultima_revisao": "2026-06-01",
      "status": "Em conformidade"
    }
  ],
  "generated_at": "2026-07-21T14:00:00Z",
  "source": "datum-connector-v2"
}
```

**RF-GW-03** `GET /api/ripd?tenant_id={id}` retorna a lista de relatórios de impacto (RIPD) do tenant informado, no formato:

```json
{
  "tenant_id": "42",
  "orgao": "SEFAZ Salvador",
  "reports": [
    {
      "id": "ripd-001",
      "titulo": "RIPD — Emissão e cobrança de IPTU",
      "operacoes_avaliadas": ["rot-001", "rot-002"],
      "nivel_risco": "Médio",
      "status": "Finalizado",
      "data_elaboracao": "2026-05-10",
      "responsavel_dpo": "Maria Silva",
      "data_assinatura": "2026-05-15"
    }
  ],
  "generated_at": "2026-07-21T14:00:00Z",
  "source": "datum-connector-v2"
}
```

**RF-GW-04** `GET /api/health?tenant_id={id}` retorna o status de conectividade com o Datum Connector do tenant:

```json
{
  "tenant_id": "42",
  "connector_status": "online",
  "connector_url": "https://datum-connector.sefaz.salvador.ba.gov.br",
  "datum_version": "2.1.3",
  "latency_ms": 124,
  "checked_at": "2026-07-21T14:00:00Z"
}
```

**RF-GW-05** `GET /api/tenants` retorna a lista de tenants configurados no Gateway com seus respectivos status de conectividade. Requer header `X-Admin-Key` (chave separada para operações administrativas).

---

### 1.2 Strategy Factory — Roteamento por Tenant

**RF-GW-06** O Gateway deve implementar o padrão Strategy para roteamento de requisições: cada tenant mapeado no arquivo de configuração possui uma classe Strategy correspondente que conhece a URL, credencial e lógica de comunicação com o Datum Connector daquele cliente.

**RF-GW-07** A Strategy Factory deve receber o `tenant_id` e retornar a Strategy correta. Tenant sem Strategy configurada retorna resposta padronizada com status `not_configured`:

```json
{
  "tenant_id": "99",
  "status": "not_configured",
  "message": "Governança LGPD não configurada para este tenant."
}
```

**RF-GW-08** Adicionar um novo cliente requer apenas: criar nova classe Strategy em `app/Services/Strategies/`, adicionar entrada em `config/datum_clients.php` e definir as variáveis de ambiente correspondentes. Nenhuma alteração no Payproxy é necessária.

---

### 1.3 Cache de Respostas

**RF-GW-09** As respostas dos endpoints `/api/rot` e `/api/ripd` devem ser cacheadas no Redis por **5 minutos** (TTL configurável por tenant). O cache é invalidado automaticamente por TTL — não há invalidação manual (dados de ROT/RIPD mudam raramente).

**RF-GW-10** O header `X-Cache` indica o estado da resposta: `HIT` (servido do cache) ou `MISS` (consultado ao Connector). O header `X-Cache-TTL` indica o tempo restante em segundos.

---

### 1.4 Tratamento de Falhas

**RF-GW-11** Se o Datum Connector de um tenant estiver indisponível (timeout ou erro HTTP), o Gateway retorna HTTP 200 com os dados do cache (se existir e não expirado) e header `X-Datum-Status: cached`. Se o cache também estiver expirado, retorna HTTP 200 com `status: unavailable` e array vazio — nunca HTTP 5xx para o Payproxy.

**RF-GW-12** Cada falha de comunicação com um Datum Connector é registrada em log com: tenant_id, URL tentada, código de erro, latência e timestamp. Falhas consecutivas (configurável, padrão: 5 em 10 minutos) geram alerta por e-mail para a equipe Ciberian.

**RF-GW-13** O isolamento entre tenants é total: a indisponibilidade do Connector de um município não afeta as requisições de outros municípios.

---

### 1.5 Auditoria

**RF-GW-14** Toda requisição recebida pelo Gateway é registrada em `gateway_logs` com: tenant_id, endpoint, resultado (success/not_configured/unavailable/cached), itens retornados, latência total e latência do Connector.

---

## 2. Requisitos Funcionais — Datum Connector (.NET)

O Datum Connector é um projeto .NET 8 WebAPI fornecido como template pela Ciberian. Cada cliente recebe uma cópia do template, configurada para o seu ambiente Datum específico.

### 2.1 API REST exposta ao Gateway

**RF-DC-01** O Connector expõe API REST autenticada por API Key via header `X-Api-Key`. Toda requisição sem key válida retorna HTTP 401.

**RF-DC-02** `GET /rot?orgao_id={id}` retorna os itens de ROT do órgão informado, lidos diretamente do banco de dados Datum (SQL Server). A estrutura de retorno segue o schema normalizado definido pela Ciberian, compatível com todas as versões do Datum suportadas.

**RF-DC-03** `GET /ripd?orgao_id={id}` retorna os relatórios RIPD do órgão informado.

**RF-DC-04** `GET /health` retorna status do Connector e versão do Datum detectada:

```json
{
  "status": "healthy",
  "datum_version": "2.1.3",
  "database": "connected",
  "uptime_seconds": 86400
}
```

**RF-DC-05** O Connector implementa um Repository por versão do schema Datum (`RotRepositoryV1`, `RotRepositoryV2`), detectado automaticamente na inicialização. Atualização do Datum pelo cliente requer apenas adicionar o Repository da nova versão — sem alteração no Gateway.

**RF-DC-06** O Connector nunca expõe dados de outros órgãos — o `orgao_id` é validado contra a lista de órgãos permitidos configurada no `appsettings.json`. Requisição com `orgao_id` não autorizado retorna HTTP 403.

---

## 3. Requisitos Não Funcionais

### 3.1 Segurança

**RNF-GW-01** Toda comunicação entre Payproxy → Gateway e Gateway → Connector deve ser feita sobre HTTPS/TLS 1.3. Requisições HTTP sem TLS são recusadas.

**RNF-GW-02** As API Keys do Gateway são armazenadas como hash SHA-256 — o valor completo não é recuperável. A key do Payproxy e a key administrativa (`X-Admin-Key`) são distintas e têm escopos separados.

**RNF-GW-03** As credenciais dos Datum Connectors (URL + API Key por cliente) são armazenadas em variáveis de ambiente — nunca em banco de dados ou código-fonte.

**RNF-GW-04** O Gateway não armazena nenhum dado pessoal de contribuintes — apenas metadados operacionais (ROT/RIPD são dados institucionais do DPO, não dados de titulares).

### 3.2 Performance e Disponibilidade

**RNF-GW-05** O tempo de resposta do Gateway (excluindo latência do Connector) deve ser inferior a **200ms** em p95. Com cache ativo, inferior a **20ms**.

**RNF-GW-06** O Gateway deve ter disponibilidade mínima de **99,5%** mensal. A indisponibilidade de um Datum Connector não é contabilizada como indisponibilidade do Gateway.

**RNF-GW-07** O Gateway deve ser deployado como container Docker, compatível com a infraestrutura Ciberian existente.

### 3.3 Extensibilidade

**RNF-GW-08** Adicionar suporte a um novo município deve levar no máximo **1 dia de trabalho** — criação da Strategy, configuração do `.env` e deploy. Sem alteração em código existente do Gateway.

**RNF-GW-09** O Datum Connector (.NET) deve ser fornecido como template com documentação de instalação suficiente para que a equipe técnica da prefeitura instale e configure sem apoio presencial da Ciberian.

---

## 4. Estrutura de Configuração

### `config/datum_clients.php`

```php
return [
    // tenant_id => configuração do Datum Connector
    '1' => [
        'strategy'  => \App\Services\Strategies\SalvadorDatumStrategy::class,
        'url'       => env('DATUM_SALVADOR_URL'),
        'api_key'   => env('DATUM_SALVADOR_KEY'),
        'orgao_id'  => env('DATUM_SALVADOR_ORGAO_ID'),
        'timeout'   => 5, // segundos
        'cache_ttl' => 300, // segundos
    ],
    '2' => [
        'strategy'  => \App\Services\Strategies\AlagoínhasDatumStrategy::class,
        'url'       => env('DATUM_ALAGOINHAS_URL'),
        'api_key'   => env('DATUM_ALAGOINHAS_KEY'),
        'orgao_id'  => env('DATUM_ALAGOINHAS_ORGAO_ID'),
        'timeout'   => 5,
        'cache_ttl' => 300,
    ],
];
```

### Variáveis de ambiente (.env)

```env
# Autenticação — Payproxy
GATEWAY_API_KEY=<key longa aleatória — gerada na instalação>
GATEWAY_ADMIN_KEY=<key administrativa — acesso restrito à Ciberian>

# Cache
REDIS_HOST=redis
REDIS_PORT=6379

# Alertas de falha
ALERT_EMAIL=ops@ciberian.com.br
ALERT_CONSECUTIVE_FAILURES=5
ALERT_WINDOW_MINUTES=10

# Datum Connector — Salvador
DATUM_SALVADOR_URL=https://datum-connector.sefaz.salvador.ba.gov.br
DATUM_SALVADOR_KEY=<key fornecida pelo Connector instalado em Salvador>
DATUM_SALVADOR_ORGAO_ID=SSA-001

# Datum Connector — Alagoinhas
DATUM_ALAGOINHAS_URL=https://datum-connector.alagoinhas.ba.gov.br
DATUM_ALAGOINHAS_KEY=<key fornecida pelo Connector instalado em Alagoinhas>
DATUM_ALAGOINHAS_ORGAO_ID=ALA-001
```

---

## 5. Estrutura de Pastas — LGPD Gateway

```
lgpd-gateway/
├── app/
│   ├── Contracts/
│   │   └── DatumStrategyInterface.php   # getRotItems() · getRipdReports() · healthCheck()
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── RotController.php
│   │   │   ├── RipdController.php
│   │   │   ├── HealthController.php
│   │   │   └── TenantController.php
│   │   ├── Middleware/
│   │   │   └── GatewayApiKeyMiddleware.php
│   │   └── Resources/
│   │       ├── RotResource.php
│   │       └── RipdResource.php
│   ├── Models/
│   │   └── GatewayLog.php
│   └── Services/
│       ├── DatumGatewayService.php       # factory: tenant_id → Strategy
│       └── Strategies/
│           ├── SalvadorDatumStrategy.php
│           └── AlagoínhasDatumStrategy.php
├── config/
│   └── datum_clients.php
├── database/migrations/
│   └── create_gateway_logs_table.php
└── routes/
    └── api.php
```

---

## 6. Estrutura de Pastas — Datum Connector (.NET)

```
datum-connector/
├── src/
│   ├── Controllers/
│   │   ├── RotController.cs
│   │   ├── RipdController.cs
│   │   └── HealthController.cs
│   ├── Repositories/
│   │   ├── IRotRepository.cs
│   │   ├── IRotRepository.cs
│   │   ├── RotRepositoryV1.cs   # schema Datum v1.x
│   │   └── RotRepositoryV2.cs   # schema Datum v2.x
│   ├── Models/
│   │   ├── RotItem.cs
│   │   └── RipdReport.cs
│   └── Middleware/
│       └── ApiKeyMiddleware.cs
├── appsettings.json              # orgao_ids permitidos, connection string
├── Dockerfile
└── README.md
```

---

## 7. Numeração de Requisitos

| Prefixo | Tipo | Intervalo |
|---|---|---|
| `RF-GW-xx` | Funcional — LGPD Gateway | RF-GW-01 a RF-GW-14 |
| `RF-DC-xx` | Funcional — Datum Connector | RF-DC-01 a RF-DC-06 |
| `RNF-GW-xx` | Não Funcional | RNF-GW-01 a RNF-GW-09 |
