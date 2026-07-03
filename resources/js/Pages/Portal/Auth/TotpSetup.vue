<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    secret:    String,
    qrCodeUrl: String,
})

const form = useForm({ code: '' })

function submit() {
    form.post(route('portal.auth.totp.setup.store'), {
        onFinish: () => form.reset('code'),
    })
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-[#2d5294] via-[#2d6abf] to-[#3a9fd8] flex items-center justify-center p-4">
        <div class="w-full max-w-sm">

            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-white/20 backdrop-blur mb-4">
                    <span class="text-2xl">🛡️</span>
                </div>
                <h1 class="text-2xl font-bold text-white">Ativar verificação em 2 etapas</h1>
                <p class="text-white/70 text-sm mt-1">Configure o seu app autenticador</p>
            </div>

            <div class="bg-white rounded-2xl shadow-xl p-8 space-y-6">

                <!-- Instrução -->
                <p class="text-sm text-gray-500 text-center leading-relaxed">
                    Escaneie o QR Code abaixo com o seu app autenticador
                    (Authy, Microsoft Authenticator, Google Authenticator etc.)
                    e confirme com o código gerado.
                </p>

                <!-- QR Code -->
                <div class="flex justify-center">
                    <div class="p-3 border border-gray-200 rounded-xl bg-gray-50 inline-block">
                        <img
                            :src="`https://api.qrserver.com/v1/create-qr-code/?data=${encodeURIComponent(qrCodeUrl)}&size=180x180`"
                            alt="QR Code para configurar 2FA"
                            width="180"
                            height="180"
                            class="block"
                        />
                    </div>
                </div>

                <!-- Chave manual -->
                <div>
                    <p class="text-xs text-center text-gray-400 mb-1.5">Ou insira a chave manualmente no app:</p>
                    <p class="text-sm font-mono text-center bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 tracking-widest select-all text-gray-700 break-all">
                        {{ secret }}
                    </p>
                </div>

                <!-- Confirmação -->
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5 text-center">
                            Código de confirmação
                        </label>
                        <input
                            v-model="form.code"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="6"
                            autofocus
                            autocomplete="one-time-code"
                            placeholder="000000"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-center text-2xl font-mono tracking-widest focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] transition-colors"
                            :class="form.errors.code ? 'border-red-300 bg-red-50' : ''"
                        />
                        <p v-if="form.errors.code" class="mt-1 text-xs text-red-500 text-center">{{ form.errors.code }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing || form.code.length < 6"
                        class="w-full bg-[#2d5294] hover:bg-[#2d6abf] disabled:opacity-60 text-white font-semibold py-3 rounded-xl text-sm transition-colors"
                    >
                        {{ form.processing ? 'Ativando…' : 'Ativar 2FA' }}
                    </button>
                </form>

            </div>

            <p class="text-center text-white/50 text-xs mt-6">
                A verificação em 2 etapas é obrigatória para acessar o portal.
            </p>
        </div>
    </div>
</template>
