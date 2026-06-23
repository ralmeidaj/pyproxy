<script setup>
import { router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import BackofficeLayout from '@/Layouts/BackofficeLayout.vue'

const props = defineProps({
    tenants:  Object,
    filters:  Object,
    statuses: Array,
})

const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? '')

let debounce
watch([search, status], () => {
    clearTimeout(debounce)
    debounce = setTimeout(() => {
        router.get(route('backoffice.tenants.index'), {
            search: search.value || undefined,
            status: status.value || undefined,
        }, { preserveState: true, replace: true })
    }, 300)
})

const statusColors = {
    pending_approval: 'bg-yellow-100 text-yellow-700',
    active:           'bg-emerald-100 text-emerald-700',
    suspended:        'bg-orange-100 text-orange-700',
    inactive:         'bg-gray-100 text-gray-500',
}

function formatDoc(doc) {
    return doc?.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5') ?? doc
}
</script>

<template>
    <BackofficeLayout>

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-bold text-[#2d5294]">Tenants</h1>
                <p class="text-sm text-gray-400 mt-0.5">{{ tenants.total }} tenant{{ tenants.total !== 1 ? 's' : '' }} cadastrado{{ tenants.total !== 1 ? 's' : '' }}</p>
            </div>
            <a :href="route('backoffice.tenants.create')"
                class="flex items-center gap-2 bg-[#3a9fd8] hover:bg-[#2889c8] text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-colors shadow-sm">
                <span>+</span> Novo Tenant
            </a>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 mb-5">
            <input v-model="search" type="text" placeholder="Buscar por nome, CNPJ ou e-mail…"
                class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm w-72 focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] bg-white shadow-sm" />
            <select v-model="status"
                class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] bg-white shadow-sm">
                <option value="">Todos os status</option>
                <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-[#2d5294] text-white text-xs uppercase tracking-wide">
                        <th class="text-left px-5 py-3.5 font-medium">Nome</th>
                        <th class="text-left px-5 py-3.5 font-medium">CNPJ</th>
                        <th class="text-left px-5 py-3.5 font-medium hidden md:table-cell">E-mail</th>
                        <th class="text-left px-5 py-3.5 font-medium">Status</th>
                        <th class="text-left px-5 py-3.5 font-medium hidden lg:table-cell">Cadastro</th>
                        <th class="px-5 py-3.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="tenants.data.length === 0">
                        <td colspan="6" class="py-16 text-center">
                            <div class="text-4xl mb-3">🏢</div>
                            <p class="font-semibold text-gray-700 mb-1">Nenhum tenant encontrado</p>
                            <p class="text-sm text-gray-400 mb-4">Tente ajustar os filtros ou cadastre um novo tenant.</p>
                            <a :href="route('backoffice.tenants.create')"
                                class="inline-flex items-center gap-2 text-sm bg-[#3a9fd8] text-white px-4 py-2 rounded-xl hover:bg-[#2889c8] transition-colors">
                                + Novo Tenant
                            </a>
                        </td>
                    </tr>
                    <tr v-for="tenant in tenants.data" :key="tenant.id"
                        class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-[#f0f4f8] text-[#3a9fd8] text-xs font-bold">
                                    {{ tenant.name[0].toUpperCase() }}
                                </div>
                                <span class="font-medium text-[#2d5294]">{{ tenant.name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 font-mono text-xs text-gray-500">{{ formatDoc(tenant.document) }}</td>
                        <td class="px-5 py-3.5 text-gray-500 hidden md:table-cell">{{ tenant.email }}</td>
                        <td class="px-5 py-3.5">
                            <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', statusColors[tenant.status]]">
                                {{ statuses.find(s => s.value === tenant.status)?.label }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-gray-400 text-xs hidden lg:table-cell">
                            {{ new Date(tenant.created_at).toLocaleDateString('pt-BR') }}
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <a :href="route('backoffice.tenants.show', tenant.id)"
                                class="text-sm text-[#3a9fd8] hover:underline font-medium">
                                Ver →
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="tenants.last_page > 1" class="flex justify-center gap-1.5 mt-5">
            <a v-for="link in tenants.links" :key="link.label"
                :href="link.url ?? '#'"
                class="px-3.5 py-1.5 rounded-lg text-xs border transition-colors"
                :class="link.active
                    ? 'bg-[#3a9fd8] text-white border-[#3a9fd8]'
                    : link.url ? 'border-gray-200 text-gray-600 hover:bg-gray-50 bg-white' : 'border-gray-100 text-gray-300 cursor-default bg-white'"
                v-html="link.label" />
        </div>

    </BackofficeLayout>
</template>
