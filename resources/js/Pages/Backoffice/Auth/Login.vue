<script setup>
import { useForm } from '@inertiajs/vue3';
import BackofficeAuthLayout from '@/Layouts/BackofficeAuthLayout.vue';

const form = useForm({
    email: '',
    password: '',
});

function submit() {
    form.post(route('backoffice.auth.login.store'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <BackofficeAuthLayout>
        <h2 class="text-xl font-semibold text-[#2d5294] mb-6">Acesso ao Backoffice</h2>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                <input
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    autofocus
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8] focus:border-transparent"
                    :class="{ 'border-red-400': form.errors.email }"
                />
                <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                <input
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8] focus:border-transparent"
                    :class="{ 'border-red-400': form.errors.password }"
                />
                <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full bg-[#3a9fd8] hover:bg-[#2889c8] disabled:opacity-60 text-white font-medium py-2.5 rounded-lg text-sm transition-colors"
            >
                {{ form.processing ? 'Entrando…' : 'Entrar' }}
            </button>
        </form>
    </BackofficeAuthLayout>
</template>
