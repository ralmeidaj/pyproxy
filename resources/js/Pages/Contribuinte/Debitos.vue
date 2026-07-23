<script setup>
import ContribuinteLayout from '@/Layouts/ContribuinteLayout.vue'
import { Link } from '@inertiajs/vue3'

defineOptions({ layout: ContribuinteLayout })

const props = defineProps({
    token:   String,
    tenants: Array,
})

const statusColors = {
    pending:   'bg-yellow-100 text-yellow-800',
    paid:      'bg-green-100 text-green-800',
    cancelled: 'bg-slate-100 text-slate-600',
    expired:   'bg-red-100 text-red-700',
}

function formatMoney(cents) {
    return 'R$ ' + (cents / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2 })
}
function formatDate(d) {
    if (!d) return '—'
    const [y, m, day] = d.split('-')
    return `${day}/${m}/${y}`
}

const totalDebitos = props.tenants?.reduce((s, t) => s + t.boletos.length, 0) ?? 0
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Meus Débitos</h2>
                <p class="text-sm text-slate-500 mt-0.5">{{ totalDebitos }} débito(s) encontrado(s)</p>
            </div>
            <Link :href="route('contribuinte.meus-dados', { token })"
                class="text-sm text-blue-600 hover:underline">
                Meus Dados (LGPD) →
            </Link>
        </div>

        <div v-if="!tenants || tenants.length === 0" class="text-center py-16 text-slate-400">
            Nenhum débito encontrado.
        </div>

        <div v-for="tenant in tenants" :key="tenant.name" class="mb-8">
            <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">
                {{ tenant.name }}
            </h3>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">Referência</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600">Valor</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600">Vencimento</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600">Situação</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="boleto in tenant.boletos" :key="boleto.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ boleto.external_ref }}</td>
                            <td class="px-4 py-3 text-right font-medium text-slate-800">{{ formatMoney(boleto.amount_cents) }}</td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ formatDate(boleto.due_date) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="statusColors[boleto.status] || 'bg-slate-100 text-slate-600'">
                                    {{ boleto.status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a v-if="boleto.pdf_url" :href="boleto.pdf_url" target="_blank"
                                        class="text-xs text-blue-600 hover:underline">PDF</a>
                                    <span v-if="boleto.pdf_url && boleto.digitable_line" class="text-slate-300">|</span>
                                    <button v-if="boleto.digitable_line"
                                        @click="navigator.clipboard.writeText(boleto.digitable_line)"
                                        class="text-xs text-slate-500 hover:text-slate-700">
                                        Copiar linha
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
