<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    valid:   Boolean,
    message: String,
    token:   String,
    name:    String,
    email:   String,
    tenant:  String,
})

const form = useForm({
    password:              '',
    password_confirmation: '',
})

function submit() {
    form.post(route('portal.invite.store', props.token), {
        onFinish: () => {
            if (form.hasErrors) {
                form.reset('password', 'password_confirmation')
            }
        },
    })
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#1a3d6e] via-[#2d5294] to-[#1e7abf] px-4 py-12">
        <div class="w-full max-w-md">

            <!-- Logo mark -->
            <div class="flex justify-center mb-8">
                <svg width="52" height="52" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <polygon points="50,18 77.7,34 77.7,66 50,82 22.3,66 22.3,34" fill="#ffffff" fill-opacity="0.12"/>
                    <circle cx="50" cy="50" r="10" fill="#1ec86e"/>
                    <polygon points="50,5 89,27.5 89,72.5 50,95 11,72.5 11,27.5"
                             fill="none" stroke="#ffffff" stroke-opacity="0.55" stroke-width="4" stroke-linejoin="round"/>
                </svg>
            </div>

            <!-- Card — invalid -->
            <div v-if="!valid" class="bg-white rounded-2xl shadow-xl p-8 text-center">
                <p class="text-4xl mb-4">⛔</p>
                <h1 class="text-lg font-bold text-gray-800 mb-2">Link inválido ou expirado</h1>
                <p class="text-sm text-gray-500 leading-relaxed">{{ message }}</p>
                <a :href="route('portal.auth.login.show')"
                    class="mt-6 inline-block text-sm text-[#2d5294] hover:underline">Voltar ao login</a>
            </div>

            <!-- Card — valid -->
            <div v-else class="bg-white rounded-2xl shadow-xl p-8">
                <h1 class="text-xl font-bold text-[#2d5294] mb-1">Criar senha</h1>
                <p class="text-sm text-gray-400 mb-1">
                    Bem-vindo, <strong class="text-gray-700">{{ name }}</strong>!
                </p>
                <p class="text-sm text-gray-400 mb-6">
                    Portal da empresa <strong class="text-gray-700">{{ tenant }}</strong>.
                </p>

                <div class="bg-[#f0f4f8] rounded-xl px-4 py-3 text-xs text-gray-500 mb-5">
                    <p class="font-semibold text-gray-600 mb-1">Requisitos de senha:</p>
                    <ul class="space-y-0.5">
                        <li>• Mínimo 10 caracteres</li>
                        <li>• Letras maiúscula e minúscula</li>
                        <li>• Pelo menos um número</li>
                        <li>• Pelo menos um símbolo (ex: @, #, !, $)</li>
                    </ul>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nova senha</label>
                        <input v-model="form.password" type="password" autocomplete="new-password"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2d5294]/30 focus:border-[#2d5294] transition" />
                        <p v-if="form.errors.password" class="text-xs text-red-500 mt-1">{{ form.errors.password }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Confirmar senha</label>
                        <input v-model="form.password_confirmation" type="password" autocomplete="new-password"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2d5294]/30 focus:border-[#2d5294] transition" />
                        <p v-if="form.errors.password_confirmation" class="text-xs text-red-500 mt-1">{{ form.errors.password_confirmation }}</p>
                    </div>

                    <button type="submit"
                        :disabled="form.processing"
                        class="w-full bg-[#2d5294] hover:bg-[#2d6abf] disabled:opacity-60 text-white font-semibold py-3 rounded-xl text-sm transition-colors mt-2">
                        {{ form.processing ? 'Ativando conta...' : 'Ativar conta' }}
                    </button>
                </form>

                <p class="text-xs text-center text-gray-400 mt-4">
                    E-mail: <strong class="text-gray-600">{{ email }}</strong>
                </p>
            </div>

            <p class="text-center text-white/40 text-xs mt-6">Payproxy — Ciberian &copy; 2026</p>
        </div>
    </div>
</template>
