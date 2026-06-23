<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
    boletos:  Object,
    filters:  Object,
    statuses: Array,
    canWrite: Boolean,
})

const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? '')

const STATUS_COLORS = {
    pending:   'bg-yellow-100 text-yellow-700',
    paid:      'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
    expired:   'bg-gray-100 text-gray-500',
}

let debounce
watch([search, status], () => {
    clearTimeout(debounce)
    debounce = setTimeout(() => {
        router.get(route('portal.boletos.index'), {
            search: search.value || undefined,
            status: status.value || undefined,
        }, { preserveState: true, replace: true })
    }, 300)
})

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

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-bold text-[#2d5294]">Boletos</h1>
                <p class="text-sm text-gray-400 mt-0.5">{{ boletos.total }} boleto{{ boletos.total !== 1 ? 's' : '' }} encontrado{{ boletos.total !== 1 ? 's' : '' }}</p>
            </div>
            <a v-if="canWrite" :href="route('portal.boletos.create')"
                class="flex items-center gap-2 bg-[#2d5294] hover:bg-[#2d6abf] text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-colors shadow-sm">
                <span>+</span> Emitir Boleto
            </a>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap gap-3 mb-5">
            <input v-model="search" type="text" placeholder="Buscar por ref., nome ou CPF/CNPJ…"
                class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm w-72 focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] bg-white shadow-sm" />
            <select v-model="status"
                class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] bg-white shadow-sm">
                <option value="">Todos os status</option>
                <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
        </div>

        <!-- Tabela -->
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-[#2d5294] text-white text-xs uppercase tracking-wide">
                        <th class="text-left px-5 py-3.5 font-medium">Pagador</th>
                        <th class="text-left px-5 py-3.5 font-medium hidden md:table-cell">Ref.</th>
                        <th class="text-right px-5 py-3.5 font-medium">Valor</th>
                        <th class="text-left px-5 py-3.5 font-medium hidden lg:table-cell">Vencimento</th>
                        <th class="text-left px-5 py-3.5 font-medium">Status</th>
                        <th class="px-5 py-3.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="boletos.data.length === 0">
                        <td colspan="6" class="py-16 text-center">
                            <div class="text-4xl mb-3">📄</div>
                            <p class="font-semibold text-gray-700 mb-1">Nenhum boleto encontrado</p>
                            <p class="text-sm text-gray-400 mb-4">Ajuste os filtros ou emita um novo boleto.</p>
                            <a v-if="canWrite" :href="route('portal.boletos.create')"
                                class="inline-flex items-center gap-2 text-sm bg-[#2d5294] text-white px-4 py-2 rounded-xl hover:bg-[#2d6abf] transition-colors">
                                + Emitir Boleto
                            </a>
                        </td>
                    </tr>
                    <tr v-for="b in boletos.data" :key="b.id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3.5">
                            <p class="font-medium text-gray-800">{{ b.payer_name }}</p>
                            <p class="text-xs text-gray-400">{{ b.boleto_config?.name }}</p>
                        </td>
                        <td class="px-5 py-3.5 hidden md:table-cell font-mono text-xs text-gray-500">
                            {{ b.external_ref }}
                        </td>
                        <td class="px-5 py-3.5 text-right font-semibold text-[#2d5294]">
                            {{ formatCents(b.amount_cents) }}
                        </td>
                        <td class="px-5 py-3.5 text-gray-500 text-xs hidden lg:table-cell">
                            {{ formatDate(b.due_date) }}
                        </td>
                        <td class="px-5 py-3.5">
                            <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', STATUS_COLORS[b.status] ?? 'bg-gray-100 text-gray-500']">
                                {{ b.status_label }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <a :href="route('portal.boletos.show', b.id)"
                                class="text-sm text-[#3a9fd8] hover:underline font-medium">Ver →</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <div v-if="boletos.last_page > 1" class="flex justify-center gap-1.5 mt-5">
            <a v-for="link in boletos.links" :key="link.label"
                :href="link.url ?? '#'"
                class="px-3.5 py-1.5 rounded-lg text-xs border transition-colors"
                :class="link.active
                    ? 'bg-[#2d5294] text-white border-[#2d5294]'
                    : link.url ? 'border-gray-200 text-gray-600 hover:bg-gray-50 bg-white' : 'border-gray-100 text-gray-300 cursor-default bg-white'"
                v-html="link.label" />
        </div>

    </PortalLayout>
</template>
