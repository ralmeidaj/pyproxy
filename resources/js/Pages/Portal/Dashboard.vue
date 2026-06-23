<script setup>
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
    tenant:        Object,
    user:          Object,
    stats:         Object,
    recentBoletos: Array,
})

const STATUS_COLORS = {
    pending:   'bg-yellow-100 text-yellow-700',
    paid:      'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
    expired:   'bg-gray-100 text-gray-500',
}

const kpiCards = [
    {
        label:   'Boletos no Mês',
        value:   () => props.stats?.boletos_this_month ?? 0,
        icon:    '📄',
        color:   'from-[#2d5294] to-[#3a9fd8]',
        iconBg:  'bg-blue-100 text-blue-600',
    },
    {
        label:   'Pagos',
        value:   () => props.stats?.boletos_paid ?? 0,
        icon:    '✅',
        color:   'from-emerald-500 to-emerald-400',
        iconBg:  'bg-emerald-100 text-emerald-600',
    },
    {
        label:   'Pendentes',
        value:   () => props.stats?.boletos_pending ?? 0,
        icon:    '⏳',
        color:   'from-amber-500 to-amber-400',
        iconBg:  'bg-amber-100 text-amber-600',
    },
    {
        label:   'Total Arrecadado',
        value:   () => formatCents(props.stats?.paid_amount_cents ?? 0),
        icon:    '💰',
        color:   'from-violet-500 to-violet-400',
        iconBg:  'bg-violet-100 text-violet-600',
    },
]

function formatCents(cents) {
    return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(dateStr) {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleDateString('pt-BR')
}
</script>

<template>
    <PortalLayout>

        <!-- Hero -->
        <div class="relative overflow-hidden bg-gradient-to-r from-[#2d5294] via-[#2d6abf] to-[#3a9fd8] rounded-2xl px-8 py-7 mb-8 text-white">
            <div class="absolute -top-6 -right-6 w-40 h-40 bg-white/5 rounded-full" />
            <div class="absolute -bottom-10 right-20 w-24 h-24 bg-white/5 rounded-full" />
            <div class="relative">
                <p class="text-white/70 text-sm mb-1">Bem-vindo,</p>
                <h1 class="text-2xl font-bold">{{ user.name }}</h1>
                <p class="text-white/60 text-sm mt-1">{{ tenant.name }} · {{ new Date().toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' }) }}</p>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div v-for="card in kpiCards" :key="card.label"
                class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="h-1 w-full bg-gradient-to-r" :class="card.color" />
                <div class="p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ card.label }}</span>
                        <div :class="['text-lg w-8 h-8 flex items-center justify-center rounded-lg', card.iconBg]">
                            {{ card.icon }}
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-[#2d5294]">{{ card.value() }}</p>
                </div>
            </div>
        </div>

        <!-- Últimos boletos + ações -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Tabela de boletos -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-[#2d5294] text-sm">Últimos Boletos</h2>
                    <a :href="route('portal.boletos.index')"
                        class="text-xs text-[#3a9fd8] hover:underline">Ver todos →</a>
                </div>

                <div v-if="recentBoletos.length === 0" class="py-12 text-center">
                    <div class="text-3xl mb-2">📄</div>
                    <p class="text-sm text-gray-500">Nenhum boleto emitido ainda.</p>
                </div>

                <table v-else class="w-full text-sm">
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="b in recentBoletos" :key="b.id"
                            class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-3">
                                <p class="font-medium text-gray-800 truncate max-w-[180px]">{{ b.payer_name }}</p>
                                <p class="text-xs text-gray-400 font-mono">{{ b.external_ref }}</p>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <p class="font-semibold text-[#2d5294]">
                                    {{ (b.amount_cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}
                                </p>
                                <p class="text-xs text-gray-400">Venc. {{ formatDate(b.due_date) }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="['text-xs font-medium px-2 py-1 rounded-full', STATUS_COLORS[b.status] ?? 'bg-gray-100 text-gray-500']">
                                    {{ b.status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <a :href="route('portal.boletos.show', b.id)"
                                    class="text-xs text-[#3a9fd8] hover:underline">Ver →</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Ações rápidas -->
            <div class="space-y-4">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h2 class="font-semibold text-[#2d5294] text-sm mb-4">Ações Rápidas</h2>
                    <div class="space-y-3">
                        <a :href="route('portal.boletos.create')"
                            class="flex items-center gap-3 p-3.5 bg-[#2d5294] text-white rounded-xl hover:bg-[#2d6abf] transition-colors group">
                            <span class="text-xl">📄</span>
                            <span class="text-sm font-medium">Emitir Boleto</span>
                        </a>
                        <a :href="route('portal.boletos.index')"
                            class="flex items-center gap-3 p-3.5 border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors">
                            <span class="text-xl">🔍</span>
                            <span class="text-sm font-medium">Ver Todos os Boletos</span>
                        </a>
                        <a :href="route('portal.profile')"
                            class="flex items-center gap-3 p-3.5 border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors">
                            <span class="text-xl">👤</span>
                            <span class="text-sm font-medium">Meu Perfil</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </PortalLayout>
</template>
