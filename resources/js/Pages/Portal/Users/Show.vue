<script setup>
import { useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
    member: Object,
    is_me:  Boolean,
})

const toggleForm = useForm({})
const resendForm = useForm({})

function toggleActive() {
    const action = props.member.active ? 'desativar' : 'ativar'
    if (! confirm(`Confirma ${action} o usuário "${props.member.name}"?`)) return
    toggleForm.patch(route('portal.users.toggle-active', props.member.id))
}

function resendInvite() {
    if (! confirm(`Reenviar convite para "${props.member.email}"?`)) return
    resendForm.post(route('portal.users.resend-invite', props.member.id))
}

const roleLabels = { admin: 'Administrador', operator: 'Operador', viewer: 'Visualizador' }

function formatDate(d) {
    return d ? new Date(d).toLocaleString('pt-BR') : '—'
}

function inviteExpired() {
    return props.member.pending_invite && props.member.invite_expires_at
        && new Date(props.member.invite_expires_at) < new Date()
}
</script>

<template>
    <PortalLayout>
        <div class="max-w-lg">
            <div class="flex items-center gap-3 mb-6">
                <a :href="route('portal.users.index')" class="text-gray-400 hover:text-[#2d5294] transition-colors text-sm">← Usuários</a>
                <span class="text-gray-300">/</span>
                <h1 class="text-xl font-bold text-[#2d5294]">{{ member.name }}</h1>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <!-- Avatar + status -->
                <div class="flex items-center gap-4 mb-6">
                    <div class="h-14 w-14 flex-shrink-0 rounded-full bg-[#2d5294] flex items-center justify-center text-white text-xl font-bold">
                        {{ member.name?.[0]?.toUpperCase() }}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">{{ member.name }}</p>
                        <p class="text-sm text-gray-400">{{ member.email }}</p>
                        <div class="flex items-center gap-2 mt-1.5">
                            <span class="text-xs bg-[#f0f4f8] text-[#2d7ab5] font-medium px-2 py-0.5 rounded-full">
                                {{ roleLabels[member.role] ?? member.role }}
                            </span>
                            <span v-if="member.active"
                                class="text-xs bg-emerald-100 text-emerald-700 font-medium px-2 py-0.5 rounded-full">Ativo</span>
                            <span v-else-if="inviteExpired()"
                                class="text-xs bg-red-100 text-red-600 font-medium px-2 py-0.5 rounded-full">Convite expirado</span>
                            <span v-else-if="member.pending_invite"
                                class="text-xs bg-amber-100 text-amber-700 font-medium px-2 py-0.5 rounded-full">Convite pendente</span>
                            <span v-else
                                class="text-xs bg-gray-100 text-gray-500 font-medium px-2 py-0.5 rounded-full">Inativo</span>
                        </div>
                    </div>
                </div>

                <!-- Detalhes -->
                <dl class="divide-y divide-gray-100 text-sm">
                    <div class="py-3 grid grid-cols-2 gap-4">
                        <dt class="text-gray-400 font-medium">Membro desde</dt>
                        <dd class="text-gray-700">{{ formatDate(member.created_at) }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-2 gap-4">
                        <dt class="text-gray-400 font-medium">Último acesso</dt>
                        <dd class="text-gray-700">{{ formatDate(member.last_login_at) }}</dd>
                    </div>
                    <div v-if="member.last_login_ip" class="py-3 grid grid-cols-2 gap-4">
                        <dt class="text-gray-400 font-medium">IP do último acesso</dt>
                        <dd class="font-mono text-gray-700 text-xs">{{ member.last_login_ip }}</dd>
                    </div>
                    <div v-if="member.pending_invite" class="py-3 grid grid-cols-2 gap-4">
                        <dt class="text-gray-400 font-medium">Convite expira em</dt>
                        <dd :class="inviteExpired() ? 'text-red-500' : 'text-amber-600'">
                            {{ formatDate(member.invite_expires_at) }}
                        </dd>
                    </div>
                </dl>

                <!-- Ações -->
                <div v-if="!is_me" class="flex items-center gap-3 mt-6 pt-5 border-t border-gray-100">
                    <button v-if="!member.active && member.pending_invite"
                        @click="resendInvite"
                        :disabled="resendForm.processing"
                        class="bg-[#2d5294] hover:bg-[#2d6abf] disabled:opacity-60 text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-colors">
                        {{ resendForm.processing ? 'Reenviando...' : 'Reenviar convite' }}
                    </button>
                    <button @click="toggleActive"
                        :disabled="toggleForm.processing"
                        :class="[
                            'text-sm font-medium px-4 py-2.5 rounded-xl transition-colors disabled:opacity-60',
                            member.active
                                ? 'bg-red-50 text-red-600 hover:bg-red-100 border border-red-200'
                                : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200',
                        ]">
                        {{ toggleForm.processing ? 'Aguarde...' : (member.active ? 'Desativar usuário' : 'Ativar usuário') }}
                    </button>
                </div>
                <p v-else class="mt-6 pt-5 border-t border-gray-100 text-xs text-gray-400">
                    Esta é a sua própria conta. Para alterar dados de acesso, acesse as configurações de perfil.
                </p>
            </div>
        </div>
    </PortalLayout>
</template>
