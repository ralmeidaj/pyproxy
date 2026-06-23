<script setup>
import { useForm } from '@inertiajs/vue3';
import BackofficeAuthLayout from '@/Layouts/BackofficeAuthLayout.vue';

const props = defineProps({
    secret: String,
    qrCodeUrl: String,
});

const form = useForm({ code: '' });

function submit() {
    form.post(route('backoffice.auth.totp.setup.store'), {
        onFinish: () => form.reset('code'),
    });
}
</script>

<template>
    <BackofficeAuthLayout>
        <h2 class="text-xl font-semibold text-[#2d5294] mb-2">Ativar verificação em 2 etapas</h2>
        <p class="text-sm text-gray-500 mb-5">
            Escaneie o QR Code abaixo com o seu aplicativo autenticador (Google Authenticator, Authy etc.) e confirme com o código gerado.
        </p>

        <div class="flex justify-center mb-4">
            <img
                :src="`https://api.qrserver.com/v1/create-qr-code/?data=${encodeURIComponent(qrCodeUrl)}&size=180x180`"
                alt="QR Code TOTP"
                class="rounded border border-gray-200 p-1"
                width="180"
                height="180"
            />
        </div>

        <p class="text-xs text-center text-gray-500 mb-1">Ou insira a chave manualmente:</p>
        <p class="text-sm font-mono text-center bg-gray-50 border border-gray-200 rounded px-3 py-2 mb-6 tracking-widest select-all">
            {{ secret }}
        </p>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Código de confirmação</label>
                <input
                    v-model="form.code"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    autofocus
                    placeholder="000000"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-center tracking-widest font-mono focus:outline-none focus:ring-2 focus:ring-[#3a9fd8] focus:border-transparent"
                    :class="{ 'border-red-400': form.errors.code }"
                />
                <p v-if="form.errors.code" class="mt-1 text-xs text-red-500">{{ form.errors.code }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full bg-[#3a9fd8] hover:bg-[#2889c8] disabled:opacity-60 text-white font-medium py-2.5 rounded-lg text-sm transition-colors"
            >
                {{ form.processing ? 'Ativando…' : 'Ativar 2FA' }}
            </button>
        </form>
    </BackofficeAuthLayout>
</template>
