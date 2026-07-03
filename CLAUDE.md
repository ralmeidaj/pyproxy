# Payproxy — Instruções para Claude Code

## Regras fundamentais

**Nunca altere nada antes de perguntar.** Sempre apresente a proposta de mudança e aguarde confirmação explícita antes de editar qualquer arquivo.

**Revisão de fases anteriores.** Ao concluir a implementação de cada fase, revisar obrigatoriamente todas as fases anteriores para identificar componentes, layouts ou lógicas que precisem ser atualizados em função do que foi criado na nova fase. Exemplo: um layout compartilhado criado na Fase 2 pode exigir que páginas da Fase 1 sejam migradas para usá-lo.

**CRUD completo por recurso.** Ao implementar qualquer recurso de backoffice, garantir todas as ações aplicáveis antes de declarar a fase concluída: `index`, `create/store`, `show`, `edit/update` e `destroy` (quando couber soft-delete). Nunca entregar um recurso sem edição.

**Restart obrigatório após mudança de PHP.** Como `opcache.validate_timestamps=0`, qualquer alteração em arquivo PHP exige `docker restart payproxy-app` imediatamente após o fix — nunca deixar para o usuário descobrir que o código antigo ainda está em cache.

**Layout full-width.** Todas as telas devem ocupar toda a largura disponível da tela. O `<main>` do layout não deve ter `max-w-*`. Usar apenas padding lateral (`px-6` ou similar) sem container centralizado.

**Swagger obrigatório em toda rota da API.** Qualquer rota nova ou modificada em `app/Http/Controllers/Api/V1/` deve ter os atributos `#[OA\...]` adicionados/atualizados no mesmo entregável. Após a alteração, executar obrigatoriamente `docker exec payproxy-app php artisan l5-swagger:generate` antes de declarar a tarefa concluída.

**Build obrigatório após mudança de Vue/JS.** O Vite HMR não está ativo neste projeto (Docker + Windows). Qualquer alteração em arquivo `.vue` ou JS em `resources/js/` exige executar imediatamente:
```
cd "c:\Users\raimundo.araujo\Documents\Projetos\payproxy" && npm run build
```
Avisar o usuário para fazer **Ctrl+Shift+R** após o build. Nunca declarar tarefa frontend concluída sem ter rodado o build.

---

## Contexto do projeto

Plataforma SaaS intermediária para geração de boletos bancários com split de pagamento e registro DDA (Débito Direto Autorizado), contratada pela SEFAZ Salvador (Secretaria Municipal da Fazenda).

- **Volume estimado:** 341.289 boletos/mês (≈ 4,1 milhões/ano)
- **Parceiro bancário inicial:** PJBank — arquitetura preparada para múltiplos parceiros via padrão adapter
- **Conformidade obrigatória:** LGPD, CNAB FEBRABAN, normas BACEN, dados em território nacional
- **Mensageria v1:** E-mail (Modelo 1) e E-mail + WhatsApp (Modelo 2), configurável por tenant
- **Termo de Referência (TR):** documento base de alinhamento de todos os requisitos

---

## Arquivos principais

| Arquivo | Descrição |
|---|---|
| `Especificacao-Requisitos-Payproxy.md` | Fonte de verdade — especificação de requisitos em Markdown |
| `Especificacao-Requisitos-Payproxy.html` | Documento HTML gerado a partir do MD (não editar diretamente) |
| `logo_ciberian.jpg` | Logo da Ciberian usada no cabeçalho do HTML |

O arquivo HTML é sempre gerado via script Node.js descartável (`gen-html.js`): criar, executar, deletar. Nunca editar o HTML diretamente.

---

## Numeração de requisitos

| Prefixo | Tipo | Exemplo |
|---|---|---|
| `RF-AC-xx` | Funcional — Gestão de Acesso | RF-AC-01 a RF-AC-19 |
| `RF-xx` | Funcional — geral | RF-01 a RF-50 |
| `RF-PART-xx` | Funcional — Parceiros Bancários | RF-PART-01 a RF-PART-08 |
| `RF-MSG-xx` | Funcional — Comunicação | RF-MSG-01 a RF-MSG-06 |
| `RNF-xx` | Não Funcional | RNF-01 a RNF-26 |
| `RN-xx` | Regra de Negócio | RN-01 a RN-09 |

Ao adicionar novos requisitos, verificar sempre se o número já existe antes de atribuir.

---

## Modelo do documento HTML

### Layout geral

- **Largura máxima:** 900px, centralizado na tela
- **Padding corpo:** 40px 48px (print: 20px 30px)
- **Fundo:** branco (`#fff`)
- **Cor de texto:** `#1a1a1a`

### Cabeçalho do documento

```
[espaço em branco]                    [LOGO CIBERIAN — 125px de altura, topo direito]

Especificação de Requisitos           ← h1, 20pt, #1a2a4a
Plataforma Payproxy                  ← subtítulo, 13pt, #555
──────────────────────────────────────── ← border-bottom 3px solid #1a6fb5

[bloco meta-info: Documento | Versão | Data | Status]
```

- Logo: `display:flex; justify-content:flex-end` — alinhada à direita, `height:125px`
- No print o logo reduz para 100px

### Tipografia

| Elemento | Tamanho | Peso | Cor |
|---|---|---|---|
| Fonte base | 11pt | 400 | `#1a1a1a` |
| Família | `'Segoe UI', Arial, sans-serif` | — | — |
| `h1` | 20pt | 700 | `#1a2a4a` |
| `h2` | 14pt | 700 | `#1a2a4a` |
| `h3` | 12pt | 700 | `#1a4a6b` |
| `h4` | 11pt | 700 | `#2a5a7a` |
| `code` | 9.5pt | 400 | `#1a3a5a` |
| Família `code` | `'Consolas', 'Courier New', monospace` | — | — |

### Paleta de cores

| Uso | Cor hex |
|---|---|
| Azul primário (RF, toolbar, bordas) | `#1a6fb5` |
| Azul escuro (títulos, cabeçalho de tabela) | `#1a2a4a` |
| Azul médio (h3) | `#1a4a6b` |
| Azul claro (h4) | `#2a5a7a` |
| Verde (RNF) | `#2a7a2a` |
| Âmbar/laranja (RN) | `#c47a00` |
| Divisores / bordas | `#d0dae8` / `#ccd6e0` |
| Fundo RF | `#f7fafd` |
| Fundo RNF | `#f7fdf7` |
| Fundo RN | `#fdf7f0` |
| Fundo meta-info / code | `#f0f4f8` |
| Zebra tabela (par) | `#f5f8fc` |

### Seções (h2, h3, h4)

Todos os headings são colapsáveis via JS (▼ expandido / ▶ recolhido). Classe `sec-heading` no elemento e `.sec-body` no wrapper gerado dinamicamente. Processamento obrigatório na ordem h4 → h3 → h2 para que o aninhamento funcione corretamente.

### Requisitos (cards)

Cada requisito é um `<div class="req">` com borda esquerda colorida de 4px:

| Classe | Cor da borda | Fundo |
|---|---|---|
| `.req` (RF) | `#1a6fb5` azul | `#f7fafd` |
| `.req.req-rnf` | `#2a7a2a` verde | `#f7fdf7` |
| `.req.req-rn` | `#c47a00` âmbar | `#fdf7f0` |

- Header clicável (▼/▶) expande/recolhe o corpo do requisito
- Requisitos sem corpo usam `req-header--simple` (ícone `—`, sem interação)
- Transição: `max-height 0.25s ease` + `opacity 0.2s ease`

### Toolbar sticky

Fixada no topo da tela (z-index 100), fundo branco semi-transparente com blur. Grupos:

1. **Seções:** Expandir | Recolher
2. `|` separador
3. **Requisitos:** Expandir | Recolher | Só RF (azul) | Só RNF (verde) | Só RN (âmbar)
4. **Legenda** (chips coloridos) — alinhada à direita via `margin-left: auto`

### Tabelas

- Cabeçalho: fundo `#1a2a4a`, texto branco, padding 8px 12px
- Células: padding 7px 12px, borda `#ccd6e0`
- Linhas pares: fundo `#f5f8fc` (zebra)
- Fonte: 10pt

### Print / PDF

- Toolbar oculta
- Todos os cards e seções expandidos (`max-height: none !important`)
- Setas de colapso ocultas
- Margem da página: 2cm × 1,8cm, tamanho A4
- Logo reduz para 100px, h1 reduz para 17pt, h2 para 12pt

---

## Padrão de geração do HTML

Sempre que o HTML precisar ser regerado:

1. Converter `logo_ciberian.jpg` para base64 e salvar em `logo_b64.txt`
2. Criar `gen-html.js` com o script Node.js completo (lê MD + base64, gera HTML)
3. Executar: `node gen-html.js`
4. Deletar `gen-html.js` e `logo_b64.txt`

O HTML nunca deve ser editado diretamente — toda mudança vai no MD e o HTML é regenerado.

---

## Arquitetura da Plataforma

### Stack

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 11, PHP 8.3+ |
| Banco de dados | PostgreSQL 16 |
| Cache / Filas / Rate limiting | Redis 7 |
| Storage (PDFs, exports) | MinIO (S3-compatible) |
| Frontend | Inertia.js + Vue 3 + Tailwind CSS 4 |
| Real-time (dashboard) | Laravel Reverb (WebSocket) |
| WhatsApp | Evolution API (self-hosted Docker) |
| Build frontend | Vite 6 |

### Projeto de referência

Os padrões arquiteturais seguem o projeto `extract-nf` localizado em:
`C:\Users\raimundo.araujo\Documents\Projetos\Estudo\extract-nf`

Padrões a seguir:
- **Service Layer** — lógica de negócio isolada em `app/Services/`, controllers thin
- **Strategy Pattern** — interfaces em `app/Contracts/`, implementações em `app/Services/`
- **Form Requests** — validação centralizada com mensagens em PT-BR
- **API Resources** — transformação JSON via `app/Http/Resources/`
- **Exceptions customizadas** — com código HTTP em `app/Exceptions/`
- **Swagger/OpenAPI** — via atributos PHP 8 + `darkaonline/l5-swagger`
- **Testes** — PHPUnit com mocks e banco em memória (SQLite)

### Estrutura de pastas

```
payproxy/
├── app/
│   ├── Contracts/                    # Interfaces (Strategy Pattern)
│   │   └── BankPartnerInterface.php
│   ├── Exceptions/                   # Exceptions customizadas com HTTP code
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/V1/              # API pública (API Key auth)
│   │   │   └── Backoffice/          # Controllers Inertia (backoffice + portal)
│   │   ├── Middleware/
│   │   │   ├── ApiKeyMiddleware.php
│   │   │   ├── TenantScopeMiddleware.php
│   │   │   └── HmacWebhookMiddleware.php
│   │   ├── Requests/                # Form Requests
│   │   └── Resources/               # API Resources
│   ├── Jobs/                        # Laravel Jobs (filas assíncronas)
│   ├── Models/                      # Eloquent Models
│   ├── Providers/
│   │   └── AppServiceProvider.php   # Bind interfaces → implementações
│   └── Services/
│       ├── BankPartners/
│       │   ├── PJBankService.php    # v1 — único adapter implementado
│       │   └── BankPartnerFactory.php
│       ├── BoletoService.php
│       ├── SplitService.php
│       ├── WebhookDeliveryService.php
│       ├── ReconciliationService.php
│       ├── NotificationService.php
│       ├── ReportService.php
│       ├── SanitizationService.php
│       └── CryptoService.php
├── database/migrations/
├── resources/js/                    # Vue 3 + Inertia (Pages + Components)
├── routes/
│   ├── api.php                      # API pública REST
│   ├── web.php                      # Rotas Inertia
│   └── channels.php                 # Reverb WebSocket
└── docker-compose.yml
```

### Fluxo de requisição

```
Request → Middleware (ApiKey / Auth) → FormRequest (Validação)
    ↓
Controller (thin — apenas orquestra)
    ↓
Service (lógica de negócio)
    ↓
BankPartnerInterface → PJBankService
    ↓
API Resource → Response JSON
```

### Parceiros bancários — escopo v1 vs v2

| Escopo | v1 | v2+ |
|---|---|---|
| `BankPartnerInterface` + `BankPartnerFactory` | ✓ implementado | — |
| `PJBankService` (adapter PJBank) | ✓ único parceiro ativo | — |
| Registro PJBank no banco (`bank_partners`) | ✓ via seed | — |
| UI de gestão de parceiros no backoffice | — | ✓ |
| Tenant seleciona parceiro na emissão | — | ✓ |
| Novos adapters (Bradesco, Sicredi, etc.) | — | ✓ |

Em v1 o parceiro é sempre PJBank. A infraestrutura de adapter já está pronta — adicionar novo parceiro em v2 requer apenas uma nova classe + bind na factory, sem alterar a API pública.

### DTOs — Data Transfer Objects (Backend)

DTOs são classes PHP puras que circulam dados entre FormRequest e Services, eliminando a dependência do objeto `Request` dentro das regras de negócio. Zero impacto de performance — são objetos de valor sem I/O.

**Localização:** `app/DTOs/`  
**Nomenclatura:** `{Ação}Data.php` — ex: `IssueBoletoData.php`, `CreateTenantData.php`

**Estrutura padrão:**

```php
// app/DTOs/IssueBoletoData.php
final readonly class IssueBoletoData
{
    public function __construct(
        public string  $externalRef,
        public string  $payerName,
        public string  $payerDocument,
        public string  $payerEmail,
        public int     $amountCents,      // sempre em centavos
        public string  $dueDate,
        public array   $metadata = [],
    ) {}

    public static function fromRequest(IssueBoletoRequest $request): self
    {
        return new self(
            externalRef:   $request->external_ref,
            payerName:     $request->payer_name,
            payerDocument: $request->payer_document,
            payerEmail:    $request->payer_email,
            amountCents:   $request->amount_cents,
            dueDate:       $request->due_date,
            metadata:      $request->metadata ?? [],
        );
    }
}
```

**Regras:**
- Controller nunca passa `Request` ou arrays avulsos para o Service — sempre um DTO
- DTO criado no Controller a partir do FormRequest validado
- Services e Use Cases dependem do DTO, nunca do `Illuminate\Http\Request`
- Usar `readonly` (PHP 8.2+) para imutabilidade

**Fluxo com DTO:**
```
FormRequest (validação) → DTO (tipagem) → Service (negócio) → API Resource (resposta)
```

---

### Composables + Services JS (Frontend Vue 3)

Organização do frontend para manter componentes `.vue` puramente visuais, sem lógica de estado ou chamadas HTTP inline. Zero impacto de performance — é apenas organização de código.

**Contexto Inertia:** A maioria dos dados chega via props do Inertia (server-side). Services JS são usados para chamadas que não passam pelo Inertia: polling, recarregamentos parciais, chamadas à API pública REST.

#### Composables — `resources/js/composables/`

Extraem estado, computed e comportamento dos componentes.

```js
// resources/js/composables/useBoletos.js
import { ref, computed } from 'vue'
import { BoletoService } from '@/services/BoletoService'

export function useBoletos() {
    const boletos = ref([])
    const loading = ref(false)
    const error   = ref(null)

    const totalPending = computed(() =>
        boletos.value.filter(b => b.status === 'pending').length
    )

    async function fetchBoletos(filters = {}) {
        loading.value = true
        error.value   = null
        try {
            boletos.value = await BoletoService.list(filters)
        } catch (e) {
            error.value = e.message
        } finally {
            loading.value = false
        }
    }

    return { boletos, loading, error, totalPending, fetchBoletos }
}
```

**Nomenclatura:** `use{Domínio}.js` — ex: `useBoletos.js`, `useTenant.js`, `useReports.js`

#### Services JS — `resources/js/services/`

Centralizam todas as chamadas HTTP por domínio. Se a URL ou a biblioteca mudar, altera-se apenas aqui.

```js
// resources/js/services/BoletoService.js
import axios from 'axios'

const api = axios.create({ baseURL: '/api/v1' })

export const BoletoService = {
    list:   (filters) => api.get('/boletos',        { params: filters }).then(r => r.data),
    get:    (id)      => api.get(`/boletos/${id}`).then(r => r.data),
    cancel: (id)      => api.delete(`/boletos/${id}`).then(r => r.data),
    resend: (id)      => api.post(`/boletos/${id}/resend`).then(r => r.data),
}
```

**Nomenclatura:** `{Domínio}Service.js` — ex: `BoletoService.js`, `TenantService.js`

**Regras:**
- Componente `.vue` nunca faz `axios.get()` diretamente — sempre via Composable → Service
- Dados vindos de props Inertia não precisam de Service (já chegam prontos)
- Services JS usam `snake_case` para envio (Laravel padrão); componentes usam `camelCase` — a conversão fica no Service

---

### Decisões arquiteturais importantes

| Decisão | Escolha | Motivo |
|---|---|---|
| Nomes de tabelas | Sempre declarar `$table` explícito quando o nome não segue pluralização padrão | `TenantStatusHistory` → Laravel infere `tenant_status_histories`, mas tabela real é `tenant_status_history`. Nomes problemáticos: `*History`, `*Daily`, `*Status` |
| Valores monetários | Inteiros em centavos | Evita erros de ponto flutuante; `DECIMAL(12,2)` no DB |
| Deleção de dados | Soft-delete (`SoftDeletes` trait) | Histórico para auditoria e LGPD |
| Snapshot de emissão | `config_snapshot` + `splits_snapshot` JSON | Imutabilidade histórica (RN-06) |
| Auth API (tenants) | API Key via middleware customizado | Escopos + limites granulares por key |
| Auth portal/backoffice | Laravel session + TOTP | 2FA obrigatório (`pragmarx/google2fa-laravel`) |
| Filas | Laravel Queues + Redis + Horizon | Nativo, monitoramento via Horizon |
| Real-time | Laravel Reverb | WebSocket nativo, zero infra extra |

### Testes

#### Pirâmide de testes — 4 níveis

| Nível | O que roda | Banco | Velocidade |
|---|---|---|---|
| 1 — Smoke | `php artisan test --filter NomeDoTest` | Nenhum | ~2s |
| 2 — Unitário | Services e DTOs isolados com mocks | SQLite in-memory | ~30s |
| 3 — Integração | Fluxo completo (Controller → Service → DB) | PostgreSQL `payproxy_test` | ~2min |
| 4 — Manual | UI, autenticação, emissão de boleto real | Staging | — |

**Comandos:**

```bash
# Nível 2 — unitários (rápido, sem banco real)
php artisan test --testsuite=Unit

# Nível 3 — integração (banco PostgreSQL de teste)
php artisan test --testsuite=Feature

# Todos com cobertura
php artisan test --coverage
```

#### Regras por nível

**Unitário (`tests/Unit/`):**
- Usar mocks para qualquer dependência externa (banco, Redis, API PJBank, e-mail)
- Banco: SQLite in-memory via `phpunit.xml` (`DB_CONNECTION=sqlite`)
- Nunca usar `RefreshDatabase` — sem banco real nos unitários
- Cada Service e DTO deve ter seu arquivo `*Test.php` correspondente

**Integração (`tests/Feature/`):**
- Usar `RefreshDatabase` trait — banco PostgreSQL `payproxy_test` real, limpo a cada teste
- Nunca mockar o banco em testes de integração (derrota o propósito)
- Mockar apenas dependências externas que não existem em dev: PJBank API, Evolution API (WhatsApp), SMTP
- Padrão `setUp / tearDown` obrigatório para estado compartilhado entre testes

#### Padrão de estrutura

```
tests/
├── Unit/
│   ├── Services/
│   │   ├── BoletoServiceTest.php
│   │   ├── SplitServiceTest.php
│   │   └── CryptoServiceTest.php
│   └── DTOs/
│       └── IssueBoletoDataTest.php
└── Feature/
    ├── Api/V1/
    │   ├── BoletoControllerTest.php
    │   └── BatchControllerTest.php
    └── Webhooks/
        └── PJBankWebhookTest.php
```

#### Lógica temporal

Para testes que dependem de data/hora (vencimento, expiração, jobs noturnos), usar o helper nativo do Laravel — nunca `new \DateTime()` hardcoded:

```php
// Congelar o tempo
$this->travelTo(now()->startOfDay());

// Avançar o tempo
$this->travel(31)->days();

// Ou via Carbon diretamente
Carbon::setTestNow('2026-07-01 02:00:00');
```

#### Meta de cobertura

- **>85%** nas classes de `app/Services/` e `app/DTOs/`
- `app/Jobs/` — cobertura mínima dos caminhos críticos (pagamento recebido, falha de webhook)
- Controllers e Middleware — cobertos via Feature tests, não unitários

#### Banco de teste — configuração

Em `phpunit.xml`, definir o banco de integração separado:

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="pgsql"/>
    <env name="DB_DATABASE" value="payproxy_test"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="MAIL_MAILER" value="array"/>
    <env name="WHATSAPP_ENABLED" value="false"/>
</php>
```

O banco `payproxy_test` deve existir no PostgreSQL local (criado uma única vez):

```sql
CREATE DATABASE payproxy_test OWNER payproxy;
```

---

### Infraestrutura Docker

Serviços necessários (dev e prod):

| Serviço | Imagem | Função |
|---|---|---|
| `app` | PHP 8.3-FPM | Laravel (API + Inertia) |
| `worker` | PHP 8.3-FPM | `php artisan queue:work` |
| `scheduler` | PHP 8.3-FPM | `php artisan schedule:work` |
| `nginx` | nginx:alpine | Proxy reverso (80/443) |
| `postgres` | postgres:16-alpine | Banco de dados |
| `redis` | redis:7-alpine | Cache, filas, rate limiting |
| `minio` | minio/minio | PDFs e exports (S3-compat.) |
| `reverb` | — | Laravel Reverb WebSocket |
| `evolution-api` | — | WhatsApp Business (prod) |
