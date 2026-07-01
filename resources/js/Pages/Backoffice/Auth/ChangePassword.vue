<script setup>
import { useForm } from '@inertiajs/vue3';
import BackofficeAuthLayout from '@/Layouts/BackofficeAuthLayout.vue';

defineProps({ warning: String });

const form = useForm({
    current_password:      '',
    password:              '',
    password_confirmation: '',
});

function submit() {
    form.post(route('backoffice.auth.password.change.store'), {
        onFinish: () => form.reset('current_password', 'password', 'password_confirmation'),
    });
}
</script>

<template>
    <BackofficeAuthLayout>
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-[#2d5294]">Renovar Senha</h2>
            <p v-if="warning" class="mt-2 text-sm text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                {{ warning }}
            </p>
            <p v-else class="mt-1 text-sm text-gray-500">
                Crie uma nova senha para continuar acessando o backoffice.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Senha atual</label>
                <input
                    v-model="form.current_password"
                    type="password"
                    autocomplete="current-password"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8] focus:border-transparent"
                    :class="{ 'border-red-400': form.errors.current_password }"
                />
                <p v-if="form.errors.current_password" class="mt-1 text-xs text-red-500">{{ form.errors.current_password }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nova senha</label>
                <input
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8] focus:border-transparent"
                    :class="{ 'border-red-400': form.errors.password }"
                />
                <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar nova senha</label>
                <input
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8] focus:border-transparent"
                />
            </div>

            <!-- Requisitos -->
            <ul class="text-xs text-gray-400 space-y-0.5 pl-1">
                <li>• Mínimo de 10 caracteres</li>
                <li>• Ao menos uma letra maiúscula e uma minúscula</li>
                <li>• Ao menos um número</li>
                <li>• Ao menos um símbolo (ex: @, #, !, $)</li>
                <li>• Não pode ser igual à senha atual</li>
            </ul>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full bg-[#3a9fd8] hover:bg-[#2889c8] disabled:opacity-60 text-white font-medium py-2.5 rounded-lg text-sm transition-colors"
            >
                {{ form.processing ? 'Salvando…' : 'Criar nova senha' }}
            </button>
        </form>
    </BackofficeAuthLayout>
</template>
