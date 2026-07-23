<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import ContribuinteLayout from '@/Layouts/ContribuinteLayout.vue'

defineOptions({ layout: ContribuinteLayout })

const form = useForm({ cpf: '' })

function submit() {
    form.post(route('contribuinte.verificar'))
}

function formatCpf(e) {
    let v = e.target.value.replace(/\D/g, '')
    if (v.length <= 11) {
        v = v.replace(/(\d{3})(\d)/, '$1.$2')
             .replace(/(\d{3})(\d)/, '$1.$2')
             .replace(/(\d{3})(\d{1,2})$/, '$1-$2')
    } else {
        v = v.replace(/^(\d{2})(\d)/, '$1.$2')
             .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
             .replace(/\.(\d{3})(\d)/, '.$1/$2')
             .replace(/(\d{4})(\d)/, '$1-$2')
    }
    form.cpf = v
}
</script>

<template>
    <div class="max-w-md mx-auto">
        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-slate-800">Consultar meus débitos</h2>
            <p class="mt-2 text-slate-500 text-sm">
                Informe seu CPF ou CNPJ. Enviaremos um link de acesso seguro para o e-mail cadastrado.
            </p>
        </div>

        <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">CPF ou CNPJ</label>
                <input
                    v-model="form.cpf"
                    @input="formatCpf"
                    type="text"
                    maxlength="18"
                    placeholder="000.000.000-00"
                    class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    :class="form.errors.cpf ? 'border-red-400' : 'border-slate-300'"
                />
                <p v-if="form.errors.cpf" class="mt-1 text-xs text-red-600">{{ form.errors.cpf }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50"
            >
                {{ form.processing ? 'Enviando...' : 'Receber link de acesso' }}
            </button>
        </form>

        <p class="mt-4 text-center text-xs text-slate-400">
            O link expira em 24 horas e só pode ser usado uma vez.
            Seus dados são protegidos conforme a LGPD.
        </p>
    </div>
</template>
