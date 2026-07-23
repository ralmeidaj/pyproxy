<script setup>
import ContribuinteLayout from '@/Layouts/ContribuinteLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'

defineOptions({ layout: ContribuinteLayout })

const props = defineProps({
    token:            String,
    dados:            Object,
    alreadyRequested: Boolean,
})

const exclusaoForm = useForm({})

function solicitarExclusao() {
    if (confirm('Confirma a solicitação de anonimização dos seus dados pessoais? Os dados fiscais (valores e datas) são retidos por lei por pelo menos 5 anos.')) {
        exclusaoForm.post(route('contribuinte.solicitar-exclusao', { token: props.token }))
    }
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Meus Dados</h2>
                <p class="text-sm text-slate-500 mt-0.5">Seus direitos conforme o Art. 18 da LGPD</p>
            </div>
            <Link :href="route('contribuinte.debitos', { token })"
                class="text-sm text-blue-600 hover:underline">
                ← Voltar aos débitos
            </Link>
        </div>

        <!-- Dados pessoais -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6">
            <h3 class="text-sm font-semibold text-slate-700 mb-4">Dados Cadastrais</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <dt class="text-xs text-slate-500">Nome</dt>
                    <dd class="mt-0.5 text-sm font-medium text-slate-800">{{ dados.payer_name || '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">E-mail</dt>
                    <dd class="mt-0.5 text-sm font-medium text-slate-800">{{ dados.payer_email || '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Telefone</dt>
                    <dd class="mt-0.5 text-sm font-medium text-slate-800">{{ dados.payer_phone || '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Total de débitos na plataforma</dt>
                    <dd class="mt-0.5 text-sm font-medium text-slate-800">{{ dados.boleto_count }}</dd>
                </div>
            </dl>
        </div>

        <!-- Ações LGPD -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-sm font-semibold text-slate-700 mb-1">Seus Direitos (LGPD — Art. 18)</h3>
            <p class="text-xs text-slate-500 mb-5">
                Você pode exportar seus dados em PDF ou solicitar a anonimização dos seus dados pessoais.
                Conforme o Art. 195 do CTN, dados fiscais (valores, vencimentos, referências) são retidos por mínimo de 5 anos por obrigação legal.
            </p>

            <div class="flex flex-wrap gap-3">
                <a :href="route('contribuinte.exportar', { token })"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Exportar PDF
                </a>

                <div v-if="alreadyRequested"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-50 border border-amber-200 text-amber-700 text-sm rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Solicitação de exclusão em análise
                </div>

                <button v-else
                    @click="solicitarExclusao"
                    :disabled="exclusaoForm.processing"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-50 hover:bg-red-100 border border-red-200 text-red-700 text-sm font-medium rounded-lg transition disabled:opacity-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Solicitar anonimização dos dados pessoais
                </button>
            </div>
        </div>
    </div>
</template>
