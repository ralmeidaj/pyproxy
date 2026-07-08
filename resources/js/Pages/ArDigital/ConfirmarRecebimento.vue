<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    token:            { type: String,  required: true },
    valor:            { type: String,  required: true },
    vencimento:       { type: String,  required: true },
    tenant_nome:      { type: String,  required: true },
    cpf_confirmation: { type: Boolean, default: false },
    link_boleto:      { type: String,  required: true },
    status:           { type: String,  required: true },
})

// Link visível somente após validação de CPF (ou imediatamente se não houver confirmação)
const boletoLink = ref(
    !props.cpf_confirmation || props.status === 'confirmado'
        ? props.link_boleto
        : null
)

const cpf      = ref('')
const cpfError = ref('')
const loading  = ref(false)

function mascararCpf(e) {
    const d = e.target.value.replace(/\D/g, '').slice(0, 11)
    cpf.value = d
        .replace(/^(\d{3})(\d)/, '$1.$2')
        .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/^(\d{3})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3-$4')
}

async function confirmar() {
    if (cpf.value.replace(/\D/g, '').length < 11) {
        cpfError.value = 'Informe um CPF válido com 11 dígitos.'
        return
    }

    cpfError.value = ''
    loading.value  = true

    try {
        const { data } = await axios.post(
            route('ar.boleto.confirmar', { token: props.token }),
            { cpf: cpf.value.replace(/\D/g, '') }
        )
        boletoLink.value = data.link_boleto
    } catch (err) {
        if (err.response?.status === 422) {
            cpfError.value = 'CPF não corresponde ao titular do boleto.'
        } else {
            cpfError.value = 'Erro ao processar. Tente novamente.'
        }
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">

        <div class="w-full max-w-md">

            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">

                <!-- Cabeçalho -->
                <div class="bg-gradient-to-r from-[#1a2a4a] to-[#2d5294] px-6 py-5">
                    <p class="text-white/60 text-xs uppercase tracking-widest mb-1">Aviso de Recebimento Digital</p>
                    <h1 class="text-white text-lg font-semibold leading-tight">{{ tenant_nome }}</h1>
                </div>

                <!-- Dados do boleto -->
                <div class="px-6 py-5 flex items-center justify-between border-b border-gray-100">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Valor</p>
                        <p class="text-3xl font-bold text-gray-900">R$&nbsp;{{ valor }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Vencimento</p>
                        <p class="text-lg font-semibold text-gray-700">{{ vencimento }}</p>
                    </div>
                </div>

                <!-- Corpo -->
                <div class="px-6 py-6">

                    <!-- Acesso liberado (sem confirmação CPF ou já confirmado) -->
                    <template v-if="boletoLink">
                        <div class="flex flex-col items-center text-center gap-4">
                            <div class="w-14 h-14 rounded-full bg-green-50 flex items-center justify-center">
                                <svg class="w-7 h-7 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-700 text-sm font-medium">Boleto disponível para download</p>
                                <p class="text-gray-400 text-xs mt-0.5">Clique no botão abaixo para abrir o PDF</p>
                            </div>
                            <a :href="boletoLink" target="_blank" rel="noopener noreferrer"
                               class="w-full inline-flex items-center justify-center gap-2 bg-[#2d5294] hover:bg-[#2d6abf] text-white font-semibold py-3 px-6 rounded-xl text-sm transition-colors shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Baixar Boleto
                            </a>
                        </div>
                    </template>

                    <!-- Confirmação de CPF obrigatória -->
                    <template v-else>
                        <p class="text-sm text-gray-600 mb-5">
                            Para acessar o boleto, confirme o CPF do titular:
                        </p>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    CPF do titular
                                </label>
                                <input
                                    :value="cpf"
                                    @input="mascararCpf"
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    placeholder="000.000.000-00"
                                    maxlength="14"
                                    class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 transition-colors"
                                    :class="cpfError
                                        ? 'border-red-300 bg-red-50 focus:ring-red-200 focus:border-red-400'
                                        : 'border-gray-200 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]'"
                                />
                                <p v-if="cpfError" class="mt-1.5 text-xs text-red-500">{{ cpfError }}</p>
                            </div>

                            <button
                                @click="confirmar"
                                :disabled="loading"
                                class="w-full bg-[#2d5294] hover:bg-[#2d6abf] disabled:opacity-60 disabled:cursor-not-allowed text-white font-semibold py-3 rounded-xl text-sm transition-colors shadow-sm"
                            >
                                {{ loading ? 'Verificando…' : 'Confirmar e Acessar Boleto' }}
                            </button>
                        </div>
                    </template>

                </div>
            </div>

            <!-- Rodapé -->
            <div class="mt-5 text-center space-y-1">
                <div class="flex items-center justify-center gap-1.5 text-gray-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <p class="text-xs">AR Digital com validade jurídica · Payproxy</p>
                </div>
                <p class="text-xs text-gray-300">Este aviso registra eletronicamente a entrega deste documento.</p>
            </div>

        </div>
    </div>
</template>
