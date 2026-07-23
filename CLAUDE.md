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

**Documentação obrigatória ao concluir qualquer implementação.** Ao finalizar qualquer fase, módulo ou funcionalidade relevante, atualizar obrigatoriamente:
- `CLAUDE.md` — refletir novos services, rotas, decisões arquiteturais, padrões e regras que a IA precisa conhecer em sessões futuras
- `README.md` — refletir novos módulos, comandos, variáveis de ambiente e rotas para o desenvolvedor humano

Nunca declarar uma implementação concluída sem ter atualizado os dois documentos.

---

## Contexto do projeto

Plataforma SaaS intermediária para geração de boletos bancários com split de pagamento, registro DDA (Débito Direto Autorizado) e módulo AR Digital (Aviso de Recebimento com validade jurídica ICP-Brasil), contratada pela SEFAZ Salvador (Secretaria Municipal da Fazenda).

- **Volume estimado:** 341.289 boletos/mês (≈ 4,1 milhões/ano)
- **Parceiro bancário inicial:** PJBank — arquitetura preparada para múltiplos parceiros via padrão adapter
- **Conformidade obrigatória:** LGPD, CNAB FEBRABAN, normas BACEN, dados em território nacional
- **Mensageria v1:** E-mail (Modelo 1) e E-mail + WhatsApp (Modelo 2), configurável por tenant
- **WhatsApp:** OVC360 API via ouvimosvc.com.br (BSP Meta credenciado, Salvador/BA) — integração via webhook OVC360 (`POST /api/v1/integrations/hooks/ciberian-boleto`), acionada automaticamente ao emitir boleto. Tenant precisa de `communication_model = email_whatsapp` e pagador deve ter `payer_phone`. A `invoice_id` enviada é o hash do token PJBank extraído do campo `pdf_url` (ex.: `d36ce166b78403f08b8311ea0f807ced236fe1aa`), nunca o `nossonumero`.
- **AR Digital:** rastreamento de entrega com carimbos RFC 3161 ICP-Brasil, confirmação de recebimento por CPF e laudo PDF jurídico
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
| `RF-IMP-xx` | Funcional — Importação de Arquivos | RF-IMP-01 a RF-IMP-15 |
| `RF-COB-xx` | Funcional — Régua de Cobrança | RF-COB-01 a RF-COB-10 |
| `RF-CONT-xx` | Funcional — Portal do Contribuinte | RF-CONT-01 a RF-CONT-12 |
| `RF-GEO-xx` | Funcional — Geointeligência | RF-GEO-01 a RF-GEO-06 |
| `RF-PARC-xx` | Funcional — Parcelamento e Carnê | RF-PARC-01 a RF-PARC-08 |
| `RF-APP-xx` | Funcional — App Nativo do Contribuinte | RF-APP-01 a RF-APP-09 |
| `RF-TRIB-xx` | Funcional — Modelo de Dados Tributários | RF-TRIB-01 a RF-TRIB-04 |
| `RF-INC-xx` | Funcional — Reporte de Inconsistência Cadastral | RF-INC-01 a RF-INC-05 |
| `RF-CRM-xx` | Funcional — CzRM / Jornada do Cidadão | RF-CRM-01 a RF-CRM-04 |
| `RF-LGPD-xx` | Funcional — Segurança & Conformidade LGPD | RF-LGPD-01 a RF-LGPD-15 |
| `RNF-xx` | Não Funcional | RNF-01 a RNF-31 |
| `RN-xx` | Regra de Negócio | RN-01 a RN-09 |

Ao adicionar novos requisitos, verificar sempre se o número já existe antes de atribuir.

### Decisões de escopo — fora do v1

| Item | Decisão |
|---|---|
| Cartão de crédito/débito | Fora do escopo. Meios suportados: boleto, PIX e DDA. |
| Integração SFTP/CNAB de entrada | Fora do escopo. Importação via API REST (RF-IMP). |
| KYC / Abertura de conta pelo app | Fora do escopo. Credenciais PJBank configuradas manualmente no backoffice. |
| Retorno de baixa ao GRP | Fora do escopo. Payproxy não envia dados de volta ao sistema tributário municipal. |
| Arquivo de retorno FEBRABAN | Coberto por webhook (RF-25) + conciliação ativa noturna (RF-29). Sem arquivo adicional. |

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
| WhatsApp | OVC360 API via ouvimosvc.com.br (BSP Meta credenciado) |
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
│       ├── ArDigitalService.php         # Orquestra o fluxo AR Digital
│       ├── ArEvidencePdfService.php     # Gera laudo PDF (DomPDF + QR code RFC 3161)
│       ├── ArTrackingService.php        # Pixel, token, hash SHA-256, confirmação CPF
│       ├── Rfc3161TimestampService.php  # Carimbos de tempo ACT ICP-Brasil
│       ├── BoletoService.php
│       ├── SplitService.php
│       ├── WebhookDeliveryService.php
│       ├── ReconciliationService.php
│       ├── NotificationService.php
│       ├── ReportService.php
│       ├── SanitizationService.php
│       ├── CryptoService.php
│       ├── ContribuinteService.php      # Busca boletos por CPF (regexp_replace PostgreSQL)
│       ├── MeusDadosPdfService.php      # Gera PDF LGPD Art. 18 via DomPDF
│       └── DataRetentionService.php     # Expurgo e anonimização LGPD (semanal)
├── database/migrations/
├── resources/js/                    # Vue 3 + Inertia (Pages + Components)
│   ├── Components/
│   │   └── MaskedField.vue           # Exibe dado mascarado com botão "Exibir" + audit POST
│   ├── Layouts/
│   │   └── ContribuinteLayout.vue    # Layout do Portal do Contribuinte (público)
│   └── Pages/
│       ├── ArDigital/
│       │   └── ConfirmarRecebimento.vue  # Landing page pública AR Digital
│       ├── Contribuinte/
│       │   ├── Index.vue              # Tela de entrada (CPF) — envia link por e-mail
│       │   ├── Debitos.vue            # Lista de débitos agrupados por município
│       │   └── MeusDados.vue          # Dados pessoais + ações LGPD (exportar, solicitar exclusão)
│       └── Backoffice/
│           ├── ArDigital/
│           │   └── Config.vue
│           └── AnonymizationRequests/
│               └── Index.vue          # Fila de solicitações de anonimização LGPD
├── routes/
│   ├── api.php                      # API pública REST
│   ├── web.php                      # Rotas Inertia + rotas públicas (AR, contribuinte)
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
| WhatsApp — envio | OVC360 API (`SendWhatsAppNotificationJob`) | Evolution API e Meta Graph API direto descartados — OVC360 é o endpoint homologado da Ouvimos/Ciberian. `invoice_id` = hash PJBank de `pdf_url`, nunca `nossonumero` |
| WhatsApp — webhook | Meta Cloud API (apenas recebimento de eventos AR Digital) | Verificação de entrega/leitura para `ar_digital_notifications`; controlado por `META_WA_WEBHOOK_VERIFY_TOKEN` |
| AR Digital — PDF | DomPDF (`barryvdh/laravel-dompdf`) | Já instalado; gera PDF A4 a partir de HTML com QR code SVG (`bacon/bacon-qr-code`) |
| AR Digital — CPF/CNPJ | Hash SHA-256 irreversível (`cpf_hash`) | LGPD: dado pessoal nunca armazenado em texto claro |
| AR Digital — carimbo stub | JSON base64 com `act_provider=stub-dev` | Dev sem ACT real; flag `_stub: true` no TSR permite distinguir em produção |

---

## Módulo AR Digital

Implementado nas Fases 1–10. Fornece prova jurídica de entrega e recebimento de boletos.

### Tabelas

| Tabela | Propósito |
|---|---|
| `ar_digital_configs` | Configuração por tenant (enabled, pixel_tracking, cpf_confirmation, act_provider) |
| `ar_digital_notifications` | Uma por boleto emitido com AR ativo — token UUID, status, hash do documento |
| `ar_digital_events` | Cada evento rastreado (envio, entrega, abertura, confirmação, bounce) |
| `ar_digital_timestamps` | Carimbo RFC 3161 por evento (TSR base64, provedor ACT, hash_input) |

### Fluxo de status

```
enviado → entregue → lido → confirmado   (progressão normal)
enviado → bounce                          (falha de entrega — terminal)
```
Nunca há downgrade de status. `statusMaisAvancado()` em `ArDigitalService` garante isso.

### Rotas públicas AR Digital (`routes/web.php`)

```
GET  /ar/pixel/{token}              # Pixel 1×1 — registra leitura_pixel
GET  /ar/boleto/{token}             # Landing page — exibe boleto ao destinatário
POST /ar/boleto/{token}/confirmar   # Confirmação de recebimento via CPF
```

### Rotas de webhook (`routes/api.php`)

```
POST /api/webhooks/smtp-dsn         # DSN SMTP (entrega ou bounce via e-mail)
GET  /api/webhooks/meta-whatsapp    # Verificação do webhook Meta (hub.challenge)
POST /api/webhooks/meta-whatsapp    # Eventos de entrega WhatsApp (delivered/read)
```

### Rota backoffice

```
GET /backoffice/tenants/{tenant}/ar-digital       # Configuração AR Digital do tenant
PUT /backoffice/tenants/{tenant}/ar-digital       # Salvar configuração
GET /backoffice/tenants/{tenant}/boletos/{boleto}/ar-laudo  # Download do laudo PDF
```

### Jobs AR Digital

| Job | Trigger | O que faz |
|---|---|---|
| `ApplyRfc3161TimestampJob` | A cada novo evento | Aplica carimbo de tempo RFC 3161 via ACT ICP-Brasil (ou stub em dev) |
| `GenerateArEvidencePdfJob` | 120s após emissão; imediato ao atingir estado terminal | Gera laudo PDF com cadeia completa de evidências e salva no MinIO |

### Laudo PDF — conteúdo

Gerado por `ArEvidencePdfService::gerar()` com DomPDF + `bacon/bacon-qr-code`:
1. Identificação (referência `ARD-XXXXXX`, token UUID, hash do documento)
2. Dados do boleto (ref externa, valor, vencimento, emitente)
3. Dados do destinatário — **mascarados LGPD** (e-mail, telefone, CPF como hash SHA-256)
4. Cadeia de evidências — tabela com todos os eventos e seus carimbos RFC 3161
5. QR code SVG de verificação online
6. Rodapé com aviso LGPD e declaração de validade jurídica ICP-Brasil

### Configuração ACT ICP-Brasil (produção)

Adicionar em `config/services.php`:
```php
'act' => [
    'enabled' => env('ACT_ENABLED', false),
    'serpro'  => ['url' => env('ACT_SERPRO_URL'), 'user' => env('ACT_SERPRO_USER'), 'password' => env('ACT_SERPRO_PASSWORD')],
    'bry'     => ['url' => env('ACT_BRY_URL'),    'user' => env('ACT_BRY_USER'),    'password' => env('ACT_BRY_PASSWORD')],
    'soluti'     => ['url' => env('ACT_SOLUTI_URL'),    'user' => env('ACT_SOLUTI_USER'),    'password' => env('ACT_SOLUTI_PASSWORD')],
    'certisign'  => ['url' => env('ACT_CERTISIGN_URL'), 'user' => env('ACT_CERTISIGN_USER'), 'password' => env('ACT_CERTISIGN_PASSWORD')],
    // Sandbox gratuito para testes em dev (sem ICP-Brasil)
    'freetsa'    => ['url' => 'https://freetsa.org/tsr', 'user' => '', 'password' => ''],
],
```
Quando `ACT_ENABLED=true` + credenciais preenchidas, `Rfc3161TimestampService` usa a API real em vez do stub.
Para testar em dev sem contratar ACT ICP-Brasil, use `freetsa` como provider (sem credenciais — autenticação condicional).

### Configuração OVC360 — Envio de WhatsApp

O envio de WhatsApp é feito via webhook OVC360 (Ouvimos BSP Meta). O job `SendWhatsAppNotificationJob` é disparado pelo `BoletoObserver` ao emitir boleto, quando o tenant tem `communication_model = email_whatsapp`.

```env
OVC360_ENABLED=true
OVC360_INTEGRATION_KEY=<chave fornecida pela Ouvimos (X-Integration-Key)>
OVC360_ENDPOINT=https://ovc360api.ouvimosvc.com.br/api/v1/integrations/hooks/ciberian-boleto
```

Payload enviado ao OVC360:
```json
{
  "name":       "Nome do pagador",
  "phone":      "5579999999999",
  "email":      "pagador@email.com",
  "invoice_id": "<hash PJBank de pdf_url — NÃO é o nossonumero>",
  "due_date":   "15/07",
  "price":      "R$ 1.234,56"
}
```

**Regra crítica do `invoice_id`:** OVC360 constrói `api.pjbank.com.br/boletos/{invoice_id}`. O `nossonumero` (campo `bank_boleto_id`) não funciona nessa URL. Usar sempre `basename(parse_url($boleto->pdf_url ?? '', PHP_URL_PATH))` para extrair o hash correto.

### Configuração Meta WhatsApp (somente webhook de entrega AR Digital)

```env
META_WA_ENABLED=true
META_WA_WEBHOOK_VERIFY_TOKEN=<token de verificação>
META_WA_API_VERSION=v19.0
```
`META_WA_PHONE_ID` e `META_WA_ACCESS_TOKEN` são necessários apenas se Payproxy passar a enviar WhatsApp diretamente via Meta Graph API (não é o caso em v1 — OVC360 gerencia isso).

---

## Módulo Segurança & LGPD — Fase 2

Implementado: expiração de API Keys, mascaramento de dados pessoais, consentimento WhatsApp e rotina de retenção.

### Tabelas

| Tabela | Propósito |
|---|---|
| `whatsapp_consents` | Consentimento LGPD por tenant + cpf_hash. Colunas: `tenant_id`, `cpf_hash`, `phone_hash`, `consented_at`, `consent_text_version`, `consent_ip`, `revoked_at`, `revocation_ip`. Unique em `[tenant_id, cpf_hash]`. |

### Services / Helpers

| Classe | Localização | Propósito |
|---|---|---|
| `MaskHelper` | `app/Helpers/MaskHelper.php` | Mascara CPF (`***.xxx.xxx-**`), e-mail (`r***@domain.com`) e telefone (`(XX) *****-XXXX`) |
| `DataRetentionService` | `app/Services/DataRetentionService.php` | Anonimiza boletos/AR > 5 anos, purga audit_logs > 2 anos, purga API keys revogadas > 90 dias |
| `WhatsappConsent` | `app/Models/WhatsappConsent.php` | `hasActiveConsent(tenantId, document)` e `grantConsent(tenantId, document, phone, ip)` |

### Jobs / Commands

| Artefato | Trigger | O que faz |
|---|---|---|
| `NotifyExpiringApiKeysJob` | Diário às 09:00 | E-mail ao tenant 15 e 7 dias antes do vencimento da API Key |
| `RunDataRetentionCommand` | Semanal (dom 03:00) | Expurgo LGPD — suporta `--dry-run` para simulação segura |

### Rotas adicionais

```
GET  /whatsapp-opt-in/{boleto}                              # Consentimento opt-in WhatsApp (signed URL, 30 dias)
POST /backoffice/tenants/{tenant}/boletos/{boleto}/reveal-field  # Audit trail ao revelar dado mascarado
```

### Mascaramento de dados (UI)

- `MaskedField.vue` exibe dado mascarado; ao clicar "Exibir" revela valor completo e dispara `POST reveal-field` (fire-and-forget)
- Aplicado em `Boletos/Index.vue` (payer_document) e `Boletos/Show.vue` (payer_document, payer_email, payer_phone)
- Mascaramento é feito no Vue, **não** no `BoletoResource` (API pública retorna dado completo — tenant tem acesso ao próprio dado)

### Consentimento WhatsApp

- `SendWhatsAppNotificationJob` verifica `WhatsappConsent::hasActiveConsent()` antes de enviar; se ausente, loga e pula
- Link de opt-in gerado em `SendEmailNotificationJob` (evento `Issued`, tenant `email_whatsapp`, pagador sem consentimento)
- URL assinada via `URL::signedRoute('whatsapp.opt-in', ...)` com validade de 30 dias
- `cpf_hash = hash('sha256', digits_only_document)` — dado pessoal nunca armazenado em texto claro

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

## Módulo Portal do Contribuinte — Fase 3

Implementado: fluxo de acesso por CPF/CNPJ, listagem de débitos, aba "Meus Dados" LGPD e fila de anonimização no backoffice.

### Tabelas

| Tabela | Propósito |
|---|---|
| `contribuintes` | Entidade global (sem `tenant_id`). `cpf_hash` SHA-256 único. |
| `contribuinte_access_tokens` | Token UUID por acesso. `cpf_encrypted` AES-256-GCM para lookup reverso. `expires_at` 24h. |
| `anonymization_requests` | Solicitação de anonimização Art. 18 LGPD. `boleto_ids` JSON snapshottado na criação. Status: `pending` / `done` / `rejected`. |

> **Nota:** `contribuinte_id` foi adicionado como FK nullable em `boletos` para futura associação automática (migração de dados ainda não executada — coluna existe, dados não populados).

### Services

| Classe | Localização | Propósito |
|---|---|---|
| `ContribuinteService` | `app/Services/ContribuinteService.php` | `findEmailByCpf`, `getBoletos`, `getPersonalData`, `getBoletoIdsByToken` — usa `regexp_replace(payer_document, '[^0-9]', '', 'g')` para lookup por CPF no PostgreSQL |
| `MeusDadosPdfService` | `app/Services/MeusDadosPdfService.php` | Gera PDF LGPD Art. 18 via DomPDF usando `resources/views/pdf/meus-dados.blade.php` |

### Controllers

| Classe | Localização | Propósito |
|---|---|---|
| `ContribuinteController` | `app/Http/Controllers/ContribuinteController.php` | Portal público: show, verificar, debitos, meusDados, exportar, solicitarExclusao |
| `AnonymizationRequestController` | `app/Http/Controllers/Backoffice/AnonymizationRequestController.php` | Backoffice: index (fila) + process (approve/reject) |

### Rotas Portal do Contribuinte (`routes/web.php`)

```
GET  /contribuinte                             # Tela de entrada — CPF
POST /contribuinte/verificar                   # Envia link por e-mail (throttle 5/15min)
GET  /contribuinte/debitos/{token}             # Lista débitos por município
GET  /contribuinte/meus-dados/{token}          # Dados pessoais + ações LGPD
GET  /contribuinte/exportar/{token}            # Download PDF LGPD
POST /contribuinte/solicitar-exclusao/{token}  # Cria AnonymizationRequest
```

### Rota Backoffice LGPD (`routes/web.php`)

```
GET  /backoffice/anonymization-requests                    # Fila de solicitações (auth.backoffice)
POST /backoffice/anonymization-requests/{id}/process       # Aprovar ou rejeitar
```

### Fluxo de anonimização

1. Contribuinte acessa `/contribuinte`, informa CPF → e-mail enviado com link único (24h)
2. Clica no link → vê débitos ou aba "Meus Dados"
3. Em "Meus Dados" solicita exclusão → `AnonymizationRequest` criada com `boleto_ids` snapshot
4. Admin no backoffice aprova → campos `payer_name`, `payer_document`, `payer_email`, `payer_phone`, `payer_address` zerados nos boletos listados
5. Dados fiscais (valores, vencimentos, referências) **não são apagados** — retenção legal 5 anos (CTN Art. 195)

### Lookup de CPF no banco

`payer_document` pode estar formatado (`000.000.000-00`) ou sem formatação (`00000000000`). O lookup usa:
```sql
regexp_replace(payer_document, '[^0-9]', '', 'g') = ?
```
O reverso (descriptografar CPF do token) usa `CryptoService::decrypt($token->cpf_encrypted)`.

### Mail

| Classe | View |
|---|---|
| `ContribuinteAccessLinkMail` | `resources/views/mail/contribuinte/access-link.blade.php` |

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
