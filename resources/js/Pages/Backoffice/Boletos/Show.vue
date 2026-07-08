<script setup>
import BackofficeLayout from '@/Layouts/BackofficeLayout.vue'

const props = defineProps({
    tenant:            Object,
    boleto:            Object,
    notificationLogs:  Array,
    arNotifications:   Array,
})

const STATUS_COLORS = {
    pending:   'bg-yellow-100 text-yellow-700',
    paid:      'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
    expired:   'bg-gray-100 text-gray-600',
}

const NOTIF_STATUS_COLORS = {
    queued: 'bg-yellow-100 text-yellow-700',
    sent:   'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
}

const NOTIF_STATUS_LABELS = {
    queued: 'Na fila',
    sent:   'Enviado',
    failed: 'Falhou',
}

const AR_STATUS_COLORS = {
    enviado:    'bg-yellow-100 text-yellow-700',
    entregue:   'bg-blue-100 text-blue-700',
    lido:       'bg-indigo-100 text-indigo-700',
    confirmado: 'bg-emerald-100 text-emerald-700',
    bounce:     'bg-red-100 text-red-600',
}

const AR_STATUS_LABELS = {
    enviado:    'Enviado',
    entregue:   'Entregue',
    lido:       'Lido',
    confirmado: 'Confirmado',
    bounce:     'Bounce',
}

const AR_EVENT_LABELS = {
    envio:              'Envio',
    leitura_pixel:      'Leitura (pixel)',
    leitura_email:      'Abertura e-mail',
    acesso_link:        'Acesso ao link',
    confirmado:         'Confirmação de recebimento',
    entrega_provedor:   'Entrega (provedor)',
    bounce:             'Bounce',
    envio_whatsapp:     'Envio WhatsApp',
}

const AR_CANAL_ICONS = {
    email:    '✉️',
    whatsapp: '💬',
    web:      '🌐',
    smtp_dsn: '📬',
}

const NOTIF_EVENT_LABELS = {
    issued:    'Emissão',
    paid:      'Pagamento',
    cancelled: 'Cancelamento',
    due_soon:  'Venc. próximo',
    overdue:   'Vencido',
}

const NOTIF_CHANNEL_ICONS = {
    email:    '✉️',
    whatsapp: '💬',
}

function formatCents(cents) {
    if (cents == null) return '—'
    return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(dateStr) {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleString('pt-BR')
}

function formatDateOnly(dateStr) {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleDateString('pt-BR')
}

function copyText(text) {
    navigator.clipboard.writeText(text)
}
</script>

<template>
    <BackofficeLayout>
        <div class="mb-6 flex items-center gap-2 text-sm text-gray-500">
            <a :href="route('backoffice.tenants.index')" class="hover:text-[#3a9fd8]">Tenants</a>
            <span>/</span>
            <a :href="route('backoffice.tenants.show', tenant.id)" class="hover:text-[#3a9fd8]">{{ tenant.name }}</a>
            <span>/</span>
            <a :href="route('backoffice.tenants.boletos.index', tenant.id)" class="hover:text-[#3a9fd8]">Boletos</a>
            <span>/</span>
            <span class="text-gray-700 font-medium font-mono">{{ boleto.external_ref }}</span>
        </div>

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-semibold text-[#2d5294]">Boleto</h1>
                <span :class="['px-3 py-1 rounded-full text-sm font-medium', STATUS_COLORS[boleto.status] ?? 'bg-gray-100 text-gray-600']">
                    {{ boleto.status_label }}
                </span>
            </div>
            <a v-if="boleto.pdf_url" :href="boleto.pdf_url" target="_blank"
                class="px-4 py-2 text-sm bg-[#3a9fd8] text-white rounded-lg hover:bg-[#2889c8] transition-colors">
                Ver PDF
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Dados principais -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Identificação -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h2 class="text-sm font-semibold text-[#2d7ab5] uppercase tracking-wide mb-4">Identificação</h2>
                    <dl class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm">
                        <div>
                            <dt class="text-gray-500">Ref. Externa</dt>
                            <dd class="font-mono font-medium">{{ boleto.external_ref }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">ID Interno</dt>
                            <dd class="font-mono text-xs text-gray-600">{{ boleto.id }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Parceiro Bancário</dt>
                            <dd>{{ boleto.bank_partner?.name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">ID no Banco</dt>
                            <dd class="font-mono text-xs">{{ boleto.bank_boleto_id ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">DDA Registrado</dt>
                            <dd>
                                <span v-if="boleto.dda_registered" class="text-green-600 font-medium">Sim</span>
                                <span v-else class="text-gray-400">Não</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Emitido em</dt>
                            <dd>{{ formatDate(boleto.created_at) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Pagador -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h2 class="text-sm font-semibold text-[#2d7ab5] uppercase tracking-wide mb-4">Pagador</h2>
                    <dl class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm">
                        <div>
                            <dt class="text-gray-500">Nome</dt>
                            <dd class="font-medium">{{ boleto.payer_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">CPF/CNPJ</dt>
                            <dd class="font-mono">{{ boleto.payer_document }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">E-mail</dt>
                            <dd>{{ boleto.payer_email }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Telefone</dt>
                            <dd>{{ boleto.payer_phone ?? '—' }}</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-gray-500">Endereço</dt>
                            <dd>{{ boleto.payer_address ? JSON.stringify(boleto.payer_address) : '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Valores -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h2 class="text-sm font-semibold text-[#2d7ab5] uppercase tracking-wide mb-4">Valores e Datas</h2>
                    <dl class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm">
                        <div>
                            <dt class="text-gray-500">Valor</dt>
                            <dd class="text-lg font-bold text-[#2d5294]">{{ formatCents(boleto.amount_cents) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Vencimento</dt>
                            <dd class="font-medium">{{ formatDateOnly(boleto.due_date) }}</dd>
                        </div>
                        <div v-if="boleto.paid_at">
                            <dt class="text-gray-500">Pago em</dt>
                            <dd class="text-green-700 font-medium">{{ formatDate(boleto.paid_at) }}</dd>
                        </div>
                        <div v-if="boleto.paid_amount_cents">
                            <dt class="text-gray-500">Valor Pago</dt>
                            <dd class="text-green-700 font-medium">{{ formatCents(boleto.paid_amount_cents) }}</dd>
                        </div>
                        <div v-if="boleto.paid_channel">
                            <dt class="text-gray-500">Canal de Pagamento</dt>
                            <dd class="capitalize">{{ boleto.paid_channel }}</dd>
                        </div>
                        <div v-if="boleto.cancelled_at">
                            <dt class="text-gray-500">Cancelado em</dt>
                            <dd class="text-red-600">{{ formatDate(boleto.cancelled_at) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Códigos -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h2 class="text-sm font-semibold text-[#2d7ab5] uppercase tracking-wide mb-4">Códigos de Cobrança</h2>
                    <div class="space-y-3">
                        <div v-if="boleto.barcode">
                            <dt class="text-xs text-gray-500 mb-1">Código de Barras</dt>
                            <div class="flex items-center gap-2">
                                <dd class="font-mono text-xs bg-gray-50 border border-gray-200 rounded px-3 py-2 flex-1 break-all">
                                    {{ boleto.barcode }}
                                </dd>
                                <button @click="copyText(boleto.barcode)"
                                    class="text-xs text-[#3a9fd8] hover:underline whitespace-nowrap">Copiar</button>
                            </div>
                        </div>
                        <div v-if="boleto.digitable_line">
                            <dt class="text-xs text-gray-500 mb-1">Linha Digitável</dt>
                            <div class="flex items-center gap-2">
                                <dd class="font-mono text-xs bg-gray-50 border border-gray-200 rounded px-3 py-2 flex-1 break-all">
                                    {{ boleto.digitable_line }}
                                </dd>
                                <button @click="copyText(boleto.digitable_line)"
                                    class="text-xs text-[#3a9fd8] hover:underline whitespace-nowrap">Copiar</button>
                            </div>
                        </div>
                        <div v-if="boleto.pix_qr_code">
                            <dt class="text-xs text-gray-500 mb-1">PIX QR Code</dt>
                            <div class="flex items-center gap-2">
                                <dd class="font-mono text-xs bg-gray-50 border border-gray-200 rounded px-3 py-2 flex-1 break-all">
                                    {{ boleto.pix_qr_code }}
                                </dd>
                                <button @click="copyText(boleto.pix_qr_code)"
                                    class="text-xs text-[#3a9fd8] hover:underline whitespace-nowrap">Copiar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notificações -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h2 class="text-sm font-semibold text-[#2d7ab5] uppercase tracking-wide mb-4">Notificações Enviadas</h2>

                    <div v-if="!notificationLogs || notificationLogs.length === 0"
                        class="flex flex-col items-center py-8 text-center">
                        <div class="text-3xl mb-2">🔔</div>
                        <p class="text-sm text-gray-500">Nenhuma notificação registrada para este boleto.</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-400 border-b border-gray-100">
                                    <th class="pb-2 font-medium">Evento</th>
                                    <th class="pb-2 font-medium">Canal</th>
                                    <th class="pb-2 font-medium">Destinatário</th>
                                    <th class="pb-2 font-medium">Status</th>
                                    <th class="pb-2 font-medium">Enviado em</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="log in notificationLogs" :key="log.id">
                                    <td class="py-2.5 pr-4">
                                        <span class="font-medium text-gray-700">
                                            {{ NOTIF_EVENT_LABELS[log.event] ?? log.event }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 pr-4">
                                        <span class="flex items-center gap-1.5">
                                            <span>{{ NOTIF_CHANNEL_ICONS[log.channel] ?? '📨' }}</span>
                                            <span class="capitalize text-gray-600">{{ log.channel }}</span>
                                        </span>
                                    </td>
                                    <td class="py-2.5 pr-4 text-gray-500 text-xs font-mono truncate max-w-[180px]">
                                        {{ log.recipient }}
                                    </td>
                                    <td class="py-2.5 pr-4">
                                        <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', NOTIF_STATUS_COLORS[log.status] ?? 'bg-gray-100 text-gray-600']">
                                            {{ NOTIF_STATUS_LABELS[log.status] ?? log.status }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-gray-400 text-xs">
                                        {{ log.sent_at ? formatDate(log.sent_at) : '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Erros expandidos -->
                        <div v-for="log in notificationLogs.filter(l => l.error)" :key="'err-' + log.id"
                            class="mt-3 bg-red-50 border border-red-100 rounded-lg px-4 py-3">
                            <p class="text-xs font-medium text-red-600 mb-1">
                                Erro em {{ NOTIF_EVENT_LABELS[log.event] ?? log.event }} ({{ log.channel }}):
                            </p>
                            <p class="text-xs font-mono text-red-500">{{ log.error }}</p>
                        </div>
                    </div>
                </div>

            <!-- AR Digital -->
            <div v-if="arNotifications && arNotifications.length > 0"
                class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-[#2d7ab5] uppercase tracking-wide mb-4">AR Digital</h2>

                <div v-for="ar in arNotifications" :key="ar.id" class="mb-6 last:mb-0">
                    <!-- Header da notificação -->
                    <div class="flex items-center gap-3 mb-3">
                        <span :class="['px-2.5 py-1 rounded-full text-xs font-semibold', AR_STATUS_COLORS[ar.status] ?? 'bg-gray-100 text-gray-600']">
                            {{ AR_STATUS_LABELS[ar.status] ?? ar.status }}
                        </span>
                        <span class="text-xs text-gray-400 font-mono">{{ ar.destinatario_email }}</span>
                        <span v-if="ar.destinatario_whatsapp" class="text-xs text-gray-400">
                            💬 {{ ar.destinatario_whatsapp }}
                        </span>
                        <span class="ml-auto text-xs text-gray-400">{{ formatDate(ar.created_at) }}</span>
                    </div>

                    <!-- CPF confirmado -->
                    <div v-if="ar.cpf_hash" class="mb-3 flex items-center gap-2 text-xs text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-lg px-3 py-2">
                        <span>CPF confirmado pelo destinatário</span>
                        <code class="font-mono text-emerald-600">{{ ar.cpf_hash.substring(0, 16) }}…</code>
                    </div>

                    <!-- Token / link -->
                    <div class="mb-3 flex items-center gap-2">
                        <code class="text-xs bg-gray-50 border border-gray-200 rounded px-3 py-1.5 flex-1 font-mono break-all text-gray-500">
                            /ar/boleto/{{ ar.token }}
                        </code>
                        <a :href="`/ar/boleto/${ar.token}`" target="_blank"
                            class="text-xs text-[#3a9fd8] hover:underline whitespace-nowrap">Abrir</a>
                    </div>

                    <!-- Timeline de eventos -->
                    <div v-if="ar.events && ar.events.length > 0" class="mt-3">
                        <p class="text-xs text-gray-400 mb-2 font-medium uppercase tracking-wide">Eventos registrados</p>
                        <ol class="space-y-2">
                            <li v-for="ev in ar.events" :key="ev.id || ev.ocorrido_em"
                                class="flex items-start gap-3 text-xs">
                                <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-gray-100 text-sm">
                                    {{ AR_CANAL_ICONS[ev.canal] ?? '📋' }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-baseline gap-2 flex-wrap">
                                        <span class="font-medium text-gray-700">
                                            {{ AR_EVENT_LABELS[ev.tipo] ?? ev.tipo }}
                                        </span>
                                        <span class="text-gray-400 text-[10px]">
                                            {{ ev.ocorrido_em ? formatDate(ev.ocorrido_em) : '—' }}
                                        </span>
                                    </div>
                                    <div v-if="ev.ip || ev.smtp_code" class="text-gray-400 mt-0.5">
                                        <span v-if="ev.ip">IP: {{ ev.ip }}</span>
                                        <span v-if="ev.smtp_code" class="ml-2">SMTP {{ ev.smtp_code }}</span>
                                    </div>
                                </div>
                            </li>
                        </ol>
                    </div>

                    <!-- Laudo disponível -->
                    <div v-if="ar.laudo_path" class="mt-3">
                        <a :href="route('backoffice.tenants.boletos.ar-laudo', [tenant.id, boleto.id])"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 text-xs text-[#3a9fd8] hover:underline font-medium">
                            📋 Baixar Laudo AR Digital
                        </a>
                    </div>

                    <hr v-if="arNotifications.length > 1" class="mt-4 border-gray-100" />
                </div>
            </div>

            </div>

            <!-- Sidebar: Splits + Config Snapshot -->
            <div class="space-y-6">

                <!-- Split -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h2 class="text-sm font-semibold text-[#2d7ab5] uppercase tracking-wide mb-4">Split de Pagamento</h2>
                    <div v-if="!boleto.splits || boleto.splits.length === 0" class="text-sm text-gray-400">
                        Sem splits configurados.
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="split in boleto.splits" :key="split.id"
                            class="flex justify-between items-center text-sm border-b border-gray-100 pb-2 last:border-0 last:pb-0">
                            <span class="text-gray-700">{{ split.name }}</span>
                            <span class="font-medium text-[#2d5294]">{{ formatCents(split.amount_cents) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Config snapshot -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h2 class="text-sm font-semibold text-[#2d7ab5] uppercase tracking-wide mb-4">Snapshot de Emissão</h2>
                    <p class="text-xs text-gray-500 mb-3">Parâmetros vigentes na data de emissão (imutável por RN-06).</p>
                    <pre v-if="boleto.config_snapshot"
                        class="text-xs font-mono bg-gray-50 border border-gray-200 rounded p-3 overflow-x-auto whitespace-pre-wrap">{{ JSON.stringify(boleto.config_snapshot, null, 2) }}</pre>
                    <p v-else class="text-sm text-gray-400">—</p>
                </div>

            </div>
        </div>
    </BackofficeLayout>
</template>
