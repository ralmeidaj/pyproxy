<script setup>
import { useForm } from '@inertiajs/vue3';
import BackofficeAuthLayout from '@/Layouts/BackofficeAuthLayout.vue';

const form = useForm({ code: '' });

function submit() {
    form.post(route('backoffice.auth.totp.store'), {
        onFinish: () => form.reset('code'),
    });
}
</script>

<template>
    <BackofficeAuthLayout>
        <h2 class="text-xl font-semibold text-[#2d5294] mb-2">Verificação em 2 etapas</h2>
        <p class="text-sm text-gray-500 mb-6">
            Insira o código de 6 dígitos gerado pelo seu aplicativo autenticador.
        </p>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Código TOTP</label>
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
                {{ form.processing ? 'Verificando…' : 'Verificar' }}
            </button>
        </form>
    </BackofficeAuthLayout>
</template>
