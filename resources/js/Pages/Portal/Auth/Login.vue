<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
    email:    '',
    password: '',
})

function submit() {
    form.post(route('portal.auth.login.store'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-[#2d5294] via-[#2d6abf] to-[#3a9fd8] flex items-center justify-center p-4">
        <div class="w-full max-w-sm">

            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-white/20 backdrop-blur mb-4">
                    <span class="text-2xl">📄</span>
                </div>
                <h1 class="text-2xl font-bold text-white">Portal do Tenant</h1>
                <p class="text-white/70 text-sm mt-1">Acesse sua conta Payproxy</p>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">E-mail</label>
                        <input v-model="form.email" type="email" autocomplete="email" autofocus
                            placeholder="seu@email.com"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] transition-colors"
                            :class="form.errors.email ? 'border-red-300 bg-red-50' : ''" />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Senha</label>
                        <input v-model="form.password" type="password" autocomplete="current-password"
                            placeholder="••••••••"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] transition-colors"
                            :class="form.errors.password ? 'border-red-300 bg-red-50' : ''" />
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                    </div>

                    <button type="submit" :disabled="form.processing"
                        class="w-full bg-[#2d5294] hover:bg-[#2d6abf] disabled:opacity-60 text-white font-semibold py-3 rounded-xl text-sm transition-colors shadow-sm">
                        {{ form.processing ? 'Entrando…' : 'Entrar' }}
                    </button>
                </form>
            </div>

            <p class="text-center text-white/50 text-xs mt-6">
                Powered by Payproxy · Ciberian
            </p>
        </div>
    </div>
</template>
