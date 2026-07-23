<script setup>
import { router } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import MaskedField from '@/Components/MaskedField.vue'

const props = defineProps({
    boleto: Object,
})

const STATUS_COLORS = {
    pending:   'bg-yellow-100 text-yellow-700',
    paid:      'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
    expired:   'bg-gray-100 text-gray-500',
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

function maskDoc(doc) {
    if (!doc) return '—'
    const d = doc.replace(/\D/g, '')
    if (d.length === 11) return `***.${d.slice(3,6)}.${d.slice(6,9)}-**`
    if (d.length === 14) return `**.${d.slice(2,5)}.${d.slice(5,8)}/${d.slice(8,12)}-**`
    return doc
}

function maskEmail(email) {
    if (!email) return '—'
    const [user, domain] = email.split('@')
    if (!domain) return email
    return user.slice(0, 1) + '***@' + domain
}

function maskPhone(phone) {
    if (!phone) return '—'
    const d = phone.replace(/\D/g, '')
    if (d.length === 11) return `(${d.slice(0,2)}) *****-${d.slice(7)}`
    if (d.length === 10) return `(${d.slice(0,2)}) ****-${d.slice(6)}`
    return phone
}

function cancelBoleto() {
    if (confirm('Tem certeza que deseja cancelar este boleto? Esta ação não pode ser desfeita.')) {
        router.post(route('portal.boletos.cancel', props.boleto.id))
    }
}

function resendNotification() {
    if (confirm('Reenviar e-mail de notificação para o pagador?')) {
        router.post(route('portal.boletos.resend', props.boleto.id))
    }
}
</script>

<template>
    <PortalLayout>

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm text-gray-400 mb-6">
            <a :href="route('portal.boletos.index')" class="hover:text-[#3a9fd8]">Boletos</a>
            <span>/</span>
            <span class="text-[#2d5294] font-medium font-mono">{{ boleto.external_ref }}</span>
        </nav>

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-semibold text-[#2d5294]">Boleto</h1>
                <span :class="['px-3 py-1 rounded-full text-sm font-medium', STATUS_COLORS[boleto.status] ?? 'bg-gray-100 text-gray-600']">
                    {{ boleto.status_label }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                <a v-if="boleto.pdf_url" :href="boleto.pdf_url" target="_blank"
                    class="px-4 py-2 text-sm bg-[#2d5294] text-white rounded-xl hover:bg-[#2d6abf] transition-colors">
                    📄 PDF
                </a>
                <button v-if="boleto.can_resend" @click="resendNotification"
                    class="px-4 py-2 text-sm bg-amber-500 text-white rounded-xl hover:bg-amber-600 transition-colors">
                    Reenviar Notificação
                </button>
                <button v-if="boleto.can_cancel" @click="cancelBoleto"
                    class="px-4 py-2 text-sm bg-red-500 text-white rounded-xl hover:bg-red-600 transition-colors">
                    Cancelar
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Dados principais -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Valor destaque -->
                <div class="bg-gradient-to-r from-[#2d5294] to-[#3a9fd8] rounded-2xl p-6 text-white">
                    <p class="text-white/70 text-sm mb-1">Valor do boleto</p>
                    <p class="text-4xl font-bold">{{ formatCents(boleto.amount_cents) }}</p>
                    <div class="flex items-center gap-6 mt-4 text-sm text-white/80">
                        <div>
                            <span class="block text-white/50 text-xs">Vencimento</span>
                            <span class="font-medium">{{ formatDateOnly(boleto.due_date) }}</span>
                        </div>
                        <div v-if="boleto.paid_at">
                            <span class="block text-white/50 text-xs">Pago em</span>
                            <span class="font-medium text-emerald-300">{{ formatDate(boleto.paid_at) }}</span>
                        </div>
                    </div>
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
                            <dt class="text-gray-500">CPF / CNPJ</dt>
                            <dd class="font-mono">
                                <MaskedField :value="boleto.payer_document" :masked="maskDoc(boleto.payer_document)" field="payer_document" />
                            </dd>
                        </div>
                        <div v-if="boleto.payer_email">
                            <dt class="text-gray-500">E-mail</dt>
                            <dd>
                                <MaskedField :value="boleto.payer_email" :masked="maskEmail(boleto.payer_email)" field="payer_email" />
                            </dd>
                        </div>
                        <div v-if="boleto.payer_phone">
                            <dt class="text-gray-500">Telefone</dt>
                            <dd>
                                <MaskedField :value="boleto.payer_phone" :masked="maskPhone(boleto.payer_phone)" field="payer_phone" />
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Códigos -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h2 class="text-sm font-semibold text-[#2d7ab5] uppercase tracking-wide mb-4">Linha Digitável</h2>
                    <div v-if="boleto.digitable_line" class="flex items-center gap-3">
                        <div class="flex-1 bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 font-mono text-sm text-blue-800 break-all">
                            {{ boleto.digitable_line }}
                        </div>
                        <button @click="copyText(boleto.digitable_line)"
                            class="flex-shrink-0 bg-[#2d5294] text-white text-xs font-medium px-3 py-2 rounded-lg hover:bg-[#2d6abf] transition-colors">
                            Copiar
                        </button>
                    </div>
                    <p v-else class="text-sm text-gray-400">Linha digitável não disponível.</p>

                    <div v-if="boleto.pix_qr_code" class="mt-4">
                        <h3 class="text-xs font-semibold text-[#2d7ab5] uppercase tracking-wide mb-2">PIX QR Code</h3>
                        <div class="flex items-center gap-3">
                            <div class="flex-1 bg-green-50 border border-green-200 rounded-xl px-4 py-3 font-mono text-xs text-green-800 break-all">
                                {{ boleto.pix_qr_code }}
                            </div>
                            <button @click="copyText(boleto.pix_qr_code)"
                                class="flex-shrink-0 bg-emerald-600 text-white text-xs font-medium px-3 py-2 rounded-lg hover:bg-emerald-700 transition-colors">
                                Copiar
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="space-y-6">

                <!-- Identificação -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h2 class="text-sm font-semibold text-[#2d7ab5] uppercase tracking-wide mb-4">Identificação</h2>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-gray-500 text-xs">Ref. Externa</dt>
                            <dd class="font-mono font-medium text-xs">{{ boleto.external_ref }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 text-xs">Configuração</dt>
                            <dd class="font-medium">{{ boleto.boleto_config?.name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 text-xs">Emitido em</dt>
                            <dd>{{ formatDate(boleto.created_at) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Splits -->
                <div v-if="boleto.splits?.length" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h2 class="text-sm font-semibold text-[#2d7ab5] uppercase tracking-wide mb-4">Split de Pagamento</h2>
                    <div class="space-y-2">
                        <div v-for="split in boleto.splits" :key="split.id"
                            class="flex justify-between items-center text-sm border-b border-gray-100 pb-2 last:border-0 last:pb-0">
                            <span class="text-gray-700">{{ split.name }}</span>
                            <span class="font-medium text-[#2d5294]">{{ formatCents(split.amount_cents) }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </PortalLayout>
</template>
