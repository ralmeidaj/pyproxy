<script setup>
import { useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
    user:   Object,
    tenant: Object,
})

const passwordForm = useForm({
    current_password:       '',
    password:               '',
    password_confirmation:  '',
})

function changePassword() {
    passwordForm.put(route('portal.profile.password'), {
        onSuccess: () => passwordForm.reset(),
    })
}

const roleLabels = {
    admin:    'Administrador',
    operator: 'Operador',
    viewer:   'Visualizador',
}

function formatDate(dateStr) {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleString('pt-BR')
}
</script>

<template>
    <PortalLayout>

        <div class="mb-6">
            <h1 class="text-xl font-bold text-[#2d5294]">Meu Perfil</h1>
            <p class="text-sm text-gray-400 mt-1">Gerencie seus dados e segurança de acesso.</p>
        </div>

        <div class="max-w-2xl space-y-6">

            <!-- Info do usuário -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-center gap-4 mb-6">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#2d5294] text-white text-xl font-bold">
                        {{ user.name?.[0]?.toUpperCase() ?? '?' }}
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">{{ user.name }}</h2>
                        <p class="text-sm text-gray-500">{{ user.email }}</p>
                        <span class="inline-block mt-1 text-xs font-medium bg-blue-100 text-[#2d5294] px-2.5 py-0.5 rounded-full">
                            {{ roleLabels[user.role] ?? user.role }}
                        </span>
                    </div>
                </div>

                <dl class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm border-t border-gray-100 pt-4">
                    <div>
                        <dt class="text-gray-500">Empresa</dt>
                        <dd class="font-medium">{{ tenant.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">2FA</dt>
                        <dd>
                            <span v-if="user.totp_enabled" class="text-emerald-600 font-medium">Ativado ✓</span>
                            <span v-else class="text-amber-600 font-medium">
                                Desativado ·
                                <a :href="route('portal.auth.totp.setup.show')" class="underline hover:text-amber-800">Ativar agora</a>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Último acesso</dt>
                        <dd>{{ formatDate(user.last_login_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">IP do último acesso</dt>
                        <dd class="font-mono text-xs">{{ user.last_login_ip ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Alterar senha -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-[#2d7ab5] uppercase tracking-wider mb-5">Alterar Senha</h2>

                <form @submit.prevent="changePassword" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Senha atual</label>
                        <input v-model="passwordForm.current_password" type="password"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]"
                            :class="passwordForm.errors.current_password ? 'border-red-300 bg-red-50' : ''" />
                        <p v-if="passwordForm.errors.current_password" class="mt-1 text-xs text-red-500">{{ passwordForm.errors.current_password }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Nova senha</label>
                            <input v-model="passwordForm.password" type="password"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]"
                                :class="passwordForm.errors.password ? 'border-red-300 bg-red-50' : ''" />
                            <p v-if="passwordForm.errors.password" class="mt-1 text-xs text-red-500">{{ passwordForm.errors.password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirmar nova senha</label>
                            <input v-model="passwordForm.password_confirmation" type="password"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]" />
                        </div>
                    </div>

                    <p class="text-xs text-gray-400">Mínimo de 8 caracteres com letras maiúsculas, minúsculas e números.</p>

                    <button type="submit" :disabled="passwordForm.processing"
                        class="bg-[#2d5294] hover:bg-[#2d6abf] disabled:opacity-60 text-white font-medium px-5 py-2.5 rounded-xl text-sm transition-colors">
                        {{ passwordForm.processing ? 'Salvando…' : 'Alterar Senha' }}
                    </button>
                </form>
            </div>

        </div>
    </PortalLayout>
</template>
