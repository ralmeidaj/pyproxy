<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import BackofficeLayout from '@/Layouts/BackofficeLayout.vue'

const props = defineProps({
    tenant:   Object,
    boletos:  Object,
    filters:  Object,
    statuses: Array,
})

const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? '')

function applyFilters() {
    router.get(
        route('backoffice.tenants.boletos.index', props.tenant.id),
        { search: search.value || undefined, status: status.value || undefined },
        { preserveState: true, replace: true },
    )
}

const STATUS_COLORS = {
    pending:   'bg-yellow-100 text-yellow-700',
    paid:      'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
    expired:   'bg-gray-100 text-gray-600',
}

function statusLabel(statusValue) {
    return props.statuses.find(s => s.value === statusValue)?.label ?? statusValue
}

function formatCents(cents) {
    return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(dateStr) {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleDateString('pt-BR')
}
</script>

<template>
    <BackofficeLayout>
        <div class="mb-6 flex items-center gap-2 text-sm text-gray-500">
            <a :href="route('backoffice.tenants.index')" class="hover:text-[#3a9fd8]">Tenants</a>
            <span>/</span>
            <a :href="route('backoffice.tenants.show', tenant.id)" class="hover:text-[#3a9fd8]">{{ tenant.name }}</a>
            <span>/</span>
            <span class="text-gray-700 font-medium">Boletos</span>
        </div>

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-semibold text-[#2d5294]">Boletos — {{ tenant.name }}</h1>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Buscar (Ref. ext. / CPF/CNPJ)</label>
                <input v-model="search" type="text" placeholder="ex: REF-001 ou 00.000.000/0001-00"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8] w-72" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select v-model="status"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]">
                    <option value="">Todos</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </div>
            <button @click="applyFilters"
                class="px-4 py-2 text-sm bg-[#3a9fd8] text-white rounded-lg hover:bg-[#2889c8] transition-colors">
                Filtrar
            </button>
        </div>

        <!-- Tabela -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#2d5294] text-white">
                        <tr>
                            <th class="px-4 py-3 text-left">Ref. Externa</th>
                            <th class="px-4 py-3 text-left">Pagador</th>
                            <th class="px-4 py-3 text-right">Valor</th>
                            <th class="px-4 py-3 text-center">Vencimento</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">DDA</th>
                            <th class="px-4 py-3 text-center">Emitido em</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="boletos.data.length === 0">
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                                Nenhum boleto encontrado.
                            </td>
                        </tr>
                        <tr v-for="(b, i) in boletos.data" :key="b.id"
                            :class="['border-t border-gray-100', i % 2 === 1 ? 'bg-[#f5f8fc]' : 'bg-white']">
                            <td class="px-4 py-3 font-mono text-xs">{{ b.external_ref }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ b.payer_name }}</div>
                                <div class="text-xs text-gray-500">{{ b.payer_document }}</div>
                            </td>
                            <td class="px-4 py-3 text-right font-medium">{{ formatCents(b.amount_cents) }}</td>
                            <td class="px-4 py-3 text-center">{{ formatDate(b.due_date) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', STATUS_COLORS[b.status] ?? 'bg-gray-100 text-gray-600']">
                                    {{ statusLabel(b.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span v-if="b.dda_registered" class="text-green-600 text-xs font-medium">Sim</span>
                                <span v-else class="text-gray-400 text-xs">—</span>
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-gray-500">{{ formatDate(b.created_at) }}</td>
                            <td class="px-4 py-3 text-right">
                                <a :href="route('backoffice.tenants.boletos.show', [tenant.id, b.id])"
                                    class="text-[#3a9fd8] hover:underline text-xs font-medium">
                                    Detalhes
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div v-if="boletos.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex justify-between items-center text-sm text-gray-600">
                <span>{{ boletos.from }}–{{ boletos.to }} de {{ boletos.total }}</span>
                <div class="flex gap-1">
                    <a v-if="boletos.prev_page_url" :href="boletos.prev_page_url"
                        class="px-3 py-1 border border-gray-200 rounded hover:bg-gray-50">‹</a>
                    <span class="px-3 py-1 bg-[#3a9fd8] text-white rounded">{{ boletos.current_page }}</span>
                    <a v-if="boletos.next_page_url" :href="boletos.next_page_url"
                        class="px-3 py-1 border border-gray-200 rounded hover:bg-gray-50">›</a>
                </div>
            </div>
        </div>
    </BackofficeLayout>
</template>
