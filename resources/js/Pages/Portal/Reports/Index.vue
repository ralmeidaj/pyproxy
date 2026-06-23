<script setup>
import PortalLayout from '@/Layouts/PortalLayout.vue'
import { router, useForm } from '@inertiajs/vue3'

const props = defineProps({
    summary:       Object,
    byChannel:     Array,
    delinquency:   Object,
    recentExports: Array,
    filters:       Object,
})

function brl(cents) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format((cents ?? 0) / 100)
}

function applyFilters(e) {
    e.preventDefault()
    const fd = new FormData(e.target)
    router.get(route('portal.reports.index'), Object.fromEntries(fd), { preserveState: true })
}

const exportForm = useForm({
    format: 'csv',
    from:   props.filters.from,
    to:     props.filters.to,
    status: '',
})

function submitExport() {
    exportForm.post(route('portal.reports.export'))
}

const channelLabels = {
    barcode: 'Código de barras',
    pix:     'PIX',
    dda:     'DDA',
    online:  'Internet Banking',
    cash:    'Guichê de Caixa',
    debit:   'Débito Automático',
    unknown: 'Não informado',
}

const statusColors = {
    pending:    'bg-amber-100 text-amber-800',
    processing: 'bg-blue-100 text-blue-800',
    completed:  'bg-emerald-100 text-emerald-800',
    failed:     'bg-red-100 text-red-800',
}
</script>

<template>
    <PortalLayout>

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-xl font-bold text-[#2d5294]">Relatórios</h1>
            <p class="text-sm text-gray-400 mt-0.5">Análise de emissão, liquidação e inadimplência</p>
        </div>

        <!-- Filtro de período -->
        <form @submit="applyFilters"
            class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-6">
            <div class="flex gap-4 items-end flex-wrap">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">De</label>
                    <input type="date" name="from" :value="filters.from"
                        class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Até</label>
                    <input type="date" name="to" :value="filters.to"
                        class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                </div>
                <button type="submit"
                    class="rounded-xl bg-[#2d5294] px-5 py-2 text-sm font-medium text-white hover:bg-[#1e3d72] transition-colors">
                    Filtrar
                </button>
            </div>
        </form>

        <!-- KPIs RF-44 -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                <p class="text-xs text-gray-400 mb-1">Total emitido</p>
                <p class="text-2xl font-bold text-[#2d5294]">{{ summary.total_issued.toLocaleString('pt-BR') }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ brl(summary.amount_issued_cents) }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                <p class="text-xs text-gray-400 mb-1">Total pago</p>
                <p class="text-2xl font-bold text-emerald-600">{{ summary.total_paid.toLocaleString('pt-BR') }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ brl(summary.amount_paid_cents) }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                <p class="text-xs text-gray-400 mb-1">Taxa de liquidação</p>
                <p class="text-2xl font-bold text-[#2d5294]">{{ summary.liquidation_rate }}%</p>
                <p class="text-xs text-gray-400 mt-1">
                    {{ summary.total_cancelled }} cancelados · {{ summary.total_expired }} expirados
                </p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                <p class="text-xs text-gray-400 mb-1">Ticket médio</p>
                <p class="text-2xl font-bold text-[#2d5294]">{{ brl(summary.avg_ticket_cents) }}</p>
            </div>
        </div>

        <!-- Por canal + Inadimplência -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            <!-- RF-46 -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-[#2d5294] mb-4">Liquidações por Canal</h2>
                <div v-if="byChannel.length === 0"
                    class="text-center text-sm text-gray-400 py-8">
                    Nenhuma liquidação no período
                </div>
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-400 border-b border-gray-100">
                            <th class="text-left pb-2 font-medium">Canal</th>
                            <th class="text-right pb-2 font-medium">Qtd.</th>
                            <th class="text-right pb-2 font-medium">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in byChannel" :key="row.channel"
                            class="border-b border-gray-50 last:border-0">
                            <td class="py-2 text-gray-700">
                                {{ channelLabels[row.channel] ?? row.channel }}
                            </td>
                            <td class="py-2 text-right font-medium text-gray-800">
                                {{ row.count.toLocaleString('pt-BR') }}
                            </td>
                            <td class="py-2 text-right text-emerald-600 font-medium">
                                {{ brl(row.amount_cents) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- RF-48 -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-[#2d5294] mb-4">Inadimplência</h2>
                <div class="space-y-3">
                    <div class="flex items-center justify-between py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-600">Total vencidos (pendente)</span>
                        <span class="font-bold text-gray-800">{{ delinquency.total_overdue.toLocaleString('pt-BR') }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-600">Vencidos &gt; 30 dias</span>
                        <span class="font-bold text-amber-600">{{ delinquency.over_30_days.toLocaleString('pt-BR') }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-600">Vencidos &gt; 60 dias</span>
                        <span class="font-bold text-orange-600">{{ delinquency.over_60_days.toLocaleString('pt-BR') }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-sm text-gray-600">Vencidos &gt; 90 dias</span>
                        <span class="font-bold text-red-600">{{ delinquency.over_90_days.toLocaleString('pt-BR') }}</span>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-xs text-gray-400">Valor total inadimplente</span>
                        <span class="text-sm font-bold text-red-600">{{ brl(delinquency.total_overdue_cents) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RF-49/50: Exportação -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-[#2d5294] mb-4">Exportar Dados</h2>
            <form @submit.prevent="submitExport">
                <div class="flex gap-4 items-end flex-wrap">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">De</label>
                        <input type="date" v-model="exportForm.from"
                            class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Até</label>
                        <input type="date" v-model="exportForm.to"
                            class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                        <select v-model="exportForm.status"
                            class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]">
                            <option value="">Todos</option>
                            <option value="pending">Pendente</option>
                            <option value="paid">Pago</option>
                            <option value="cancelled">Cancelado</option>
                            <option value="expired">Expirado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Formato</label>
                        <select v-model="exportForm.format"
                            class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]">
                            <option value="csv">CSV</option>
                            <option value="json">JSON</option>
                        </select>
                    </div>
                    <button type="submit" :disabled="exportForm.processing"
                        class="rounded-xl bg-emerald-600 px-5 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50 transition-colors">
                        Exportar
                    </button>
                </div>
            </form>

            <!-- Exportações recentes -->
            <div v-if="recentExports.length > 0" class="mt-5 border-t border-gray-100 pt-4">
                <p class="text-xs font-medium text-gray-400 mb-3">Exportações recentes</p>
                <div class="space-y-2">
                    <div v-for="exp in recentExports" :key="exp.id"
                        class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-3">
                            <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', statusColors[exp.status]]">
                                {{ exp.status }}
                            </span>
                            <span class="text-gray-500 uppercase text-xs">{{ exp.format }}</span>
                            <span v-if="exp.row_count !== null" class="text-gray-400 text-xs">
                                {{ exp.row_count.toLocaleString('pt-BR') }} registros
                            </span>
                        </div>
                        <a v-if="exp.status === 'completed' && exp.download_url"
                            :href="exp.download_url" target="_blank"
                            class="text-xs text-[#3a9fd8] hover:underline font-medium">
                            Baixar
                        </a>
                        <span v-else-if="exp.status === 'failed'"
                            class="text-xs text-red-500">Falhou</span>
                        <span v-else class="text-xs text-gray-400">Processando...</span>
                    </div>
                </div>
            </div>
        </div>

    </PortalLayout>
</template>
