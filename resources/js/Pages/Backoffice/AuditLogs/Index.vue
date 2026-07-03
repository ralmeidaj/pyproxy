<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import BackofficeLayout from '@/Layouts/BackofficeLayout.vue'

const props = defineProps({
    logs:    Object,
    filters: Object,
    tenants: Array,
})

const f = ref({
    tenant_id: props.filters?.tenant_id ?? '',
    action:    props.filters?.action    ?? '',
    actor:     props.filters?.actor     ?? '',
    from:      props.filters?.from      ?? '',
    to:        props.filters?.to        ?? '',
})

let debounce = null
watch(f, () => {
    clearTimeout(debounce)
    debounce = setTimeout(() => applyFilters(), 400)
}, { deep: true })

function applyFilters() {
    router.get(route('backoffice.audit-logs.index'), {
        ...f.value,
        tenant_id: f.value.tenant_id || undefined,
        action:    f.value.action    || undefined,
        actor:     f.value.actor     || undefined,
        from:      f.value.from      || undefined,
        to:        f.value.to        || undefined,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    f.value = { tenant_id: '', action: '', actor: '', from: '', to: '' }
}

function formatDate(d) {
    if (!d) return '—'
    return new Date(d).toLocaleString('pt-BR')
}

const ACTION_COLORS = {
    'boleto.issued':           'bg-blue-100 text-blue-700',
    'boleto.cancelled':        'bg-red-100 text-red-600',
    'boleto.paid':             'bg-emerald-100 text-emerald-700',
    'tenant.created':          'bg-purple-100 text-purple-700',
    'tenant.status_updated':   'bg-amber-100 text-amber-700',
    'api_key.generated':       'bg-indigo-100 text-indigo-700',
    'api_key.revoked':         'bg-red-100 text-red-600',
    'tenant_user.invited':     'bg-teal-100 text-teal-700',
}

function actionColor(action) {
    return ACTION_COLORS[action] ?? 'bg-gray-100 text-gray-600'
}
</script>

<template>
    <BackofficeLayout>

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-bold text-[#2d5294]">Trilha de Auditoria</h1>
                <p class="text-sm text-gray-400 mt-1">Registro imutável de todas as operações da plataforma.</p>
            </div>
            <span class="text-sm text-gray-400">{{ logs.total.toLocaleString('pt-BR') }} registros</span>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">

                <select v-model="f.tenant_id"
                    class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] bg-white">
                    <option value="">Todos os tenants</option>
                    <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>

                <input v-model="f.action" type="text" placeholder="Tipo de ação (ex: boleto.issued)"
                    class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]" />

                <input v-model="f.actor" type="text" placeholder="Ator (nome ou sistema)"
                    class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]" />

                <input v-model="f.from" type="date"
                    class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]" />

                <input v-model="f.to" type="date"
                    class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]" />
            </div>

            <div v-if="f.tenant_id || f.action || f.actor || f.from || f.to"
                class="mt-3 flex justify-end">
                <button @click="clearFilters"
                    class="text-xs text-gray-400 hover:text-gray-600 underline transition-colors">
                    Limpar filtros
                </button>
            </div>
        </div>

        <!-- Tabela -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div v-if="!logs.data.length" class="py-16 text-center">
                <div class="text-4xl mb-3">📋</div>
                <p class="text-sm font-medium text-gray-700">Nenhum registro encontrado</p>
                <p class="text-xs text-gray-400 mt-1">Ajuste os filtros para ver outros registros.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-[#1a2a4a] text-white text-xs uppercase tracking-wide">
                            <th class="px-4 py-3 text-left font-semibold">Data / Hora</th>
                            <th class="px-4 py-3 text-left font-semibold">Ação</th>
                            <th class="px-4 py-3 text-left font-semibold">Ator</th>
                            <th class="px-4 py-3 text-left font-semibold">Recurso</th>
                            <th class="px-4 py-3 text-left font-semibold">Tenant</th>
                            <th class="px-4 py-3 text-left font-semibold">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(log, i) in logs.data" :key="log.id"
                            :class="i % 2 === 0 ? 'bg-white' : 'bg-[#f5f8fc]'"
                            class="hover:bg-blue-50/40 transition-colors">
                            <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap font-mono">
                                {{ formatDate(log.created_at) }}
                            </td>
                            <td class="px-4 py-3">
                                <span :class="['text-xs font-mono font-medium px-2 py-1 rounded-full', actionColor(log.action)]">
                                    {{ log.action }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-xs font-medium text-gray-700">{{ log.actor_label }}</p>
                                <p class="text-xs text-gray-400">{{ log.actor_type }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 font-mono">
                                {{ log.resource_type }}
                                <span v-if="log.resource_id" class="text-gray-400">#{{ log.resource_id }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ log.tenant_id ? `#${log.tenant_id}` : '—' }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-400 font-mono">
                                {{ log.ip ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div v-if="logs.last_page > 1"
                class="flex items-center justify-between px-5 py-4 border-t border-gray-100 bg-gray-50">
                <p class="text-xs text-gray-500">
                    Página {{ logs.current_page }} de {{ logs.last_page }}
                    &nbsp;·&nbsp; {{ logs.total.toLocaleString('pt-BR') }} registros
                </p>
                <div class="flex items-center gap-1">
                    <a v-if="logs.prev_page_url" :href="logs.prev_page_url"
                        class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg hover:bg-white transition-colors text-gray-600">
                        ← Anterior
                    </a>
                    <a v-if="logs.next_page_url" :href="logs.next_page_url"
                        class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg hover:bg-white transition-colors text-gray-600">
                        Próxima →
                    </a>
                </div>
            </div>
        </div>

    </BackofficeLayout>
</template>
