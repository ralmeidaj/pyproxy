<script setup>
import { useForm } from '@inertiajs/vue3'

defineProps({ warning: String })

const form = useForm({
    current_password:      '',
    password:              '',
    password_confirmation: '',
})

function submit() {
    form.post(route('portal.auth.password.change.store'), {
        onFinish: () => form.reset('current_password', 'password', 'password_confirmation'),
    })
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-[#2d5294] via-[#2d6abf] to-[#3a9fd8] flex items-center justify-center p-4">
        <div class="w-full max-w-sm">

            <!-- Logo -->
            <div class="flex flex-col items-center mb-8">
                <svg width="64" height="64" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="mb-4">
                    <polygon points="50,18 77.7,34 77.7,66 50,82 22.3,66 22.3,34" fill="#ffffff" fill-opacity="0.18"/>
                    <line x1="50" y1="50" x2="50"   y2="18"  stroke="#ffffff" stroke-opacity="0.5" stroke-width="1.5" stroke-linecap="round"/>
                    <line x1="50" y1="50" x2="77.7" y2="34"  stroke="#ffffff" stroke-opacity="0.5" stroke-width="1.5" stroke-linecap="round"/>
                    <line x1="50" y1="50" x2="77.7" y2="66"  stroke="#ffffff" stroke-opacity="0.5" stroke-width="1.5" stroke-linecap="round"/>
                    <line x1="50" y1="50" x2="50"   y2="82"  stroke="#ffffff" stroke-opacity="0.5" stroke-width="1.5" stroke-linecap="round"/>
                    <line x1="50" y1="50" x2="22.3" y2="66"  stroke="#ffffff" stroke-opacity="0.5" stroke-width="1.5" stroke-linecap="round"/>
                    <line x1="50" y1="50" x2="22.3" y2="34"  stroke="#ffffff" stroke-opacity="0.5" stroke-width="1.5" stroke-linecap="round"/>
                    <circle cx="50"   cy="18" r="4" fill="#ffffff" fill-opacity="0.75"/>
                    <circle cx="77.7" cy="34" r="4" fill="#ffffff" fill-opacity="0.75"/>
                    <circle cx="77.7" cy="66" r="4" fill="#ffffff" fill-opacity="0.75"/>
                    <circle cx="50"   cy="82" r="4" fill="#ffffff" fill-opacity="0.75"/>
                    <circle cx="22.3" cy="66" r="4" fill="#ffffff" fill-opacity="0.75"/>
                    <circle cx="22.3" cy="34" r="4" fill="#ffffff" fill-opacity="0.75"/>
                    <circle cx="50" cy="50" r="10" fill="#1ec86e"/>
                    <polygon points="50,5 89,27.5 89,72.5 50,95 11,72.5 11,27.5"
                             fill="none" stroke="#ffffff" stroke-opacity="0.65" stroke-width="4" stroke-linejoin="round"/>
                </svg>
                <h1 class="text-xl font-bold text-white">Renovar Senha</h1>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8">

                <div v-if="warning" class="mb-5 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                    {{ warning }}
                </div>
                <p v-else class="text-sm text-gray-500 mb-5">
                    Crie uma nova senha para continuar acessando o portal.
                </p>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Senha atual</label>
                        <input v-model="form.current_password" type="password" autocomplete="current-password"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] transition-colors"
                            :class="form.errors.current_password ? 'border-red-300 bg-red-50' : ''" />
                        <p v-if="form.errors.current_password" class="mt-1 text-xs text-red-500">{{ form.errors.current_password }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nova senha</label>
                        <input v-model="form.password" type="password" autocomplete="new-password"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] transition-colors"
                            :class="form.errors.password ? 'border-red-300 bg-red-50' : ''" />
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirmar nova senha</label>
                        <input v-model="form.password_confirmation" type="password" autocomplete="new-password"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] transition-colors" />
                    </div>

                    <!-- Requisitos -->
                    <ul class="text-xs text-gray-400 space-y-0.5 pl-1">
                        <li>• Mínimo de 10 caracteres</li>
                        <li>• Ao menos uma letra maiúscula e uma minúscula</li>
                        <li>• Ao menos um número</li>
                        <li>• Ao menos um símbolo (ex: @, #, !, $)</li>
                        <li>• Não pode ser igual à senha atual</li>
                    </ul>

                    <button type="submit" :disabled="form.processing"
                        class="w-full bg-[#2d5294] hover:bg-[#2d6abf] disabled:opacity-60 text-white font-semibold py-3 rounded-xl text-sm transition-colors shadow-sm">
                        {{ form.processing ? 'Salvando…' : 'Criar nova senha' }}
                    </button>
                </form>
            </div>

            <p class="text-center text-white/50 text-xs mt-6">Powered by Payproxy · Ciberian</p>
        </div>
    </div>
</template>
