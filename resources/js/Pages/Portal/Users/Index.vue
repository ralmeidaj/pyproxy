<script setup>
import { useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
    users: Array,
    is_me: Number,
})

const toggleForm  = useForm({})
const resendForm  = useForm({})

function toggleActive(user) {
    const action = user.active ? 'desativar' : 'ativar'
    if (! confirm(`Confirma ${action} o usuário "${user.name}"?`)) return
    toggleForm.patch(route('portal.users.toggle-active', user.id))
}

function resendInvite(user) {
    if (! confirm(`Reenviar convite para "${user.email}"?`)) return
    resendForm.post(route('portal.users.resend-invite', user.id))
}

const roleLabels = { admin: 'Administrador', operator: 'Operador', viewer: 'Visualizador' }

function formatDate(d) {
    return d ? new Date(d).toLocaleString('pt-BR') : '—'
}

function inviteExpired(user) {
    return user.pending_invite && user.invite_expires_at && new Date(user.invite_expires_at) < new Date()
}
</script>

<template>
    <PortalLayout>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-bold text-[#2d5294]">Usuários</h1>
                <p class="text-sm text-gray-400 mt-0.5">Gerencie os membros com acesso ao portal.</p>
            </div>
            <a :href="route('portal.users.create')"
                class="bg-[#2d5294] hover:bg-[#2d6abf] text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-colors">
                + Convidar usuário
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div v-if="!users.length" class="py-16 text-center">
                <p class="text-3xl mb-3">👥</p>
                <p class="text-sm font-medium text-gray-700">Nenhum usuário cadastrado</p>
                <p class="text-xs text-gray-400 mt-1">Convide o primeiro membro da sua equipe.</p>
            </div>

            <table v-else class="w-full text-sm">
                <thead class="bg-[#f0f4f8] border-b border-gray-200">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Usuário</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Perfil</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Último acesso</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 flex-shrink-0 rounded-full bg-[#2d5294] flex items-center justify-center text-white text-xs font-bold">
                                    {{ user.name?.[0]?.toUpperCase() }}
                                </div>
                                <div>
                                    <p class="font-medium text-[#2d5294]">
                                        {{ user.name }}
                                        <span v-if="user.id === is_me" class="ml-1 text-xs text-gray-400">(você)</span>
                                    </p>
                                    <p class="text-xs text-gray-400">{{ user.email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <span class="text-xs bg-[#f0f4f8] text-[#2d7ab5] font-medium px-2 py-0.5 rounded-full">
                                {{ roleLabels[user.role] ?? user.role }}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <span v-if="user.active"
                                class="text-xs bg-emerald-100 text-emerald-700 font-medium px-2 py-0.5 rounded-full">Ativo</span>
                            <span v-else-if="inviteExpired(user)"
                                class="text-xs bg-red-100 text-red-600 font-medium px-2 py-0.5 rounded-full">Convite expirado</span>
                            <span v-else-if="user.pending_invite"
                                class="text-xs bg-amber-100 text-amber-700 font-medium px-2 py-0.5 rounded-full">Convite pendente</span>
                            <span v-else
                                class="text-xs bg-gray-100 text-gray-500 font-medium px-2 py-0.5 rounded-full">Inativo</span>
                        </td>
                        <td class="px-4 py-4 text-xs text-gray-400">{{ formatDate(user.last_login_at) }}</td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-3 justify-end">
                                <button v-if="!user.active && user.pending_invite"
                                    @click="resendInvite(user)"
                                    :disabled="resendForm.processing"
                                    class="text-xs text-[#3a9fd8] hover:underline">Reenviar</button>
                                <button v-if="user.id !== is_me"
                                    @click="toggleActive(user)"
                                    :disabled="toggleForm.processing"
                                    :class="['text-xs hover:underline', user.active ? 'text-red-400 hover:text-red-600' : 'text-emerald-600 hover:text-emerald-800']">
                                    {{ user.active ? 'Desativar' : 'Ativar' }}
                                </button>
                                <a :href="route('portal.users.show', user.id)"
                                    class="text-xs text-gray-400 hover:text-[#2d5294] hover:underline">Detalhes</a>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PortalLayout>
</template>
