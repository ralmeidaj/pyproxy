<script setup>
import BackofficeLayout from '@/Layouts/BackofficeLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

defineOptions({ layout: BackofficeLayout })

const props = defineProps({
    requests: Object,
})

const rejectModal = ref(null)
const rejectNotes = ref('')

function openRejectModal(req) {
    rejectModal.value = req
    rejectNotes.value = ''
}

function processForm(req, action) {
    const form = useForm({ action, notes: rejectNotes.value })
    form.post(route('backoffice.anonymization-requests.process', req.id), {
        onSuccess: () => { rejectModal.value = null }
    })
}

const statusColors = {
    pending:  'bg-amber-100 text-amber-700',
    done:     'bg-green-100 text-green-700',
    rejected: 'bg-slate-100 text-slate-600',
}

function formatDate(d) {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
    <div class="px-6 py-6">
        <div class="mb-6">
            <h1 class="text-xl font-bold text-slate-800">Solicitações de Anonimização LGPD</h1>
            <p class="text-sm text-slate-500 mt-1">Fila de solicitações de exclusão de dados pessoais (Art. 18 LGPD)</p>
        </div>

        <div v-if="$page.props.flash?.success" class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
            {{ $page.props.flash.success }}
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">E-mail (mascarado)</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600">Boletos</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600">Solicitado em</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600">Processado por</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="req in requests.data" :key="req.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-slate-700 font-mono text-xs">{{ req.payer_email_masked || '—' }}</td>
                        <td class="px-4 py-3 text-center text-slate-700">{{ req.boleto_count }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="statusColors[req.status]">
                                {{ req.status === 'pending' ? 'Pendente' : req.status === 'done' ? 'Concluída' : 'Rejeitada' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-slate-500 text-xs">{{ formatDate(req.created_at) }}</td>
                        <td class="px-4 py-3 text-center text-slate-500 text-xs">{{ req.processed_by_label || '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <div v-if="req.status === 'pending'" class="flex items-center justify-center gap-2">
                                <button
                                    @click="processForm(req, 'approve')"
                                    class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded transition">
                                    Aprovar
                                </button>
                                <button
                                    @click="openRejectModal(req)"
                                    class="px-3 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-medium rounded transition">
                                    Rejeitar
                                </button>
                            </div>
                            <span v-else class="text-xs text-slate-400">—</span>
                        </td>
                    </tr>
                    <tr v-if="!requests.data?.length">
                        <td colspan="6" class="px-4 py-10 text-center text-slate-400 text-sm">
                            Nenhuma solicitação pendente.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Reject Modal -->
        <div v-if="rejectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
                <h3 class="text-base font-semibold text-slate-800 mb-3">Rejeitar solicitação</h3>
                <p class="text-sm text-slate-500 mb-4">
                    Informe o motivo da rejeição. O contribuinte não receberá esta resposta automaticamente —
                    é necessário contatá-lo separadamente se precisar.
                </p>
                <textarea
                    v-model="rejectNotes"
                    rows="3"
                    placeholder="Motivo da rejeição..."
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4"
                />
                <div class="flex gap-3 justify-end">
                    <button @click="rejectModal = null"
                        class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800">Cancelar</button>
                    <button @click="processForm(rejectModal, 'reject')"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">
                        Confirmar rejeição
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
