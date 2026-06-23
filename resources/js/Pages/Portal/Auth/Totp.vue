<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({ code: '' })

function submit() {
    form.post(route('portal.auth.totp.store'), {
        onFinish: () => form.reset('code'),
    })
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-[#2d5294] via-[#2d6abf] to-[#3a9fd8] flex items-center justify-center p-4">
        <div class="w-full max-w-sm">

            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-white/20 backdrop-blur mb-4">
                    <span class="text-2xl">🔐</span>
                </div>
                <h1 class="text-2xl font-bold text-white">Verificação 2FA</h1>
                <p class="text-white/70 text-sm mt-1">Informe o código do seu autenticador</p>
            </div>

            <div class="bg-white rounded-2xl shadow-xl p-8">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Código TOTP</label>
                        <input v-model="form.code" type="text" inputmode="numeric" pattern="[0-9]*"
                            maxlength="6" autofocus autocomplete="one-time-code"
                            placeholder="000000"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-center text-2xl font-mono tracking-widest focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] transition-colors"
                            :class="form.errors.code ? 'border-red-300 bg-red-50' : ''" />
                        <p v-if="form.errors.code" class="mt-1 text-xs text-red-500 text-center">{{ form.errors.code }}</p>
                    </div>

                    <button type="submit" :disabled="form.processing || form.code.length < 6"
                        class="w-full bg-[#2d5294] hover:bg-[#2d6abf] disabled:opacity-60 text-white font-semibold py-3 rounded-xl text-sm transition-colors">
                        {{ form.processing ? 'Verificando…' : 'Verificar' }}
                    </button>

                    <div class="text-center">
                        <a :href="route('portal.auth.login.show')"
                            class="text-sm text-gray-400 hover:text-gray-600 transition-colors">
                            ← Voltar ao login
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
