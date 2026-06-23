<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BackofficeLayout from '@/Layouts/BackofficeLayout.vue'

const props  = defineProps({ tenant: Object })
const page   = usePage()
const apiKeyCreated = computed(() => page.props.flash?.api_key_created)
const copied = ref(false)

function copyKey() {
    navigator.clipboard.writeText(apiKeyCreated.value?.plain_key ?? '')
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
}

const showStatusForm = ref(false)
const statusForm = useForm({ status: '', reason: '' })

function submitStatus() {
    statusForm.patch(route('backoffice.tenants.status', props.tenant.id), {
        onSuccess: () => { showStatusForm.value = false; statusForm.reset() },
    })
}

const revokeForm = useForm({})
function revokeKey(apiKeyId) {
    if (!confirm('Revogar esta API key? Esta ação não pode ser desfeita.')) return
    revokeForm.delete(route('backoffice.tenants.api-keys.revoke', [props.tenant.id, apiKeyId]))
}

const statusColors = {
    pending_approval: 'bg-yellow-100 text-yellow-700',
    active:           'bg-emerald-100 text-emerald-700',
    suspended:        'bg-orange-100 text-orange-700',
    inactive:         'bg-gray-100 text-gray-500',
}

const STATUS_LABELS = {
    pending_approval: 'Pendente de Aprovação',
    active:           'Ativo',
    suspended:        'Suspenso',
    inactive:         'Inativo',
}

function formatDoc(doc) {
    return doc?.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5') ?? doc
}
function formatDate(d) {
    return d ? new Date(d).toLocaleString('pt-BR') : '—'
}
</script>

<template>
    <BackofficeLayout>

        <!-- API key created banner -->
        <div v-if="apiKeyCreated"
            class="mb-5 relative overflow-hidden rounded-2xl bg-emerald-50 border border-emerald-200 p-5">
            <div class="flex items-start gap-4">
                <div class="text-2xl">🔑</div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-emerald-800 mb-0.5">
                        API Key criada: <span class="font-normal">{{ apiKeyCreated.name }}</span>
                    </p>
                    <p class="text-xs text-emerald-600 mb-3">Copie agora — esta chave não será exibida novamente.</p>
                    <div class="flex items-center gap-2">
                        <code class="text-xs bg-white border border-emerald-200 rounded-xl px-4 py-2 font-mono flex-1 break-all">
                            {{ apiKeyCreated.plain_key }}
                        </code>
                        <button @click="copyKey"
                            class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl transition-colors whitespace-nowrap font-medium">
                            {{ copied ? '✓ Copiado!' : 'Copiar' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm text-gray-400 mb-6">
            <a :href="route('backoffice.tenants.index')" class="hover:text-[#3a9fd8] transition-colors">Tenants</a>
            <span>/</span>
            <span class="text-[#2d5294] font-medium">{{ tenant.name }}</span>
        </nav>

        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f0f4f8] text-[#3a9fd8] text-xl font-bold">
                {{ tenant.name[0].toUpperCase() }}
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold text-[#2d5294]">{{ tenant.name }}</h1>
                    <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', statusColors[tenant.status]]">
                        {{ tenant.status_label }}
                    </span>
                </div>
                <p class="text-sm text-gray-400">{{ formatDoc(tenant.document) }}</p>
            </div>
            <a :href="route('backoffice.tenants.edit', tenant.id)"
                class="ml-auto flex items-center gap-2 text-sm border border-gray-200 hover:bg-gray-50 text-gray-600 px-4 py-2 rounded-xl transition-colors">
                ✏️ Editar
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            <!-- Main column -->
            <div class="lg:col-span-2 space-y-5">

                <!-- Dados -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h2 class="text-xs font-semibold text-[#2d7ab5] uppercase tracking-wider mb-4">Dados do Tenant</h2>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt class="text-xs text-gray-400 mb-0.5">CNPJ</dt>
                            <dd class="font-mono font-medium text-[#2d5294]">{{ formatDoc(tenant.document) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 mb-0.5">E-mail</dt>
                            <dd class="text-[#2d5294]">{{ tenant.email }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 mb-0.5">Telefone</dt>
                            <dd class="text-[#2d5294]">{{ tenant.phone || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 mb-0.5">Comunicação</dt>
                            <dd class="text-[#2d5294]">{{ tenant.communication_model === 'email' ? 'Modelo 1 — E-mail' : 'Modelo 2 — E-mail + WhatsApp' }}</dd>
                        </div>
                        <div v-if="tenant.notes" class="md:col-span-2">
                            <dt class="text-xs text-gray-400 mb-0.5">Observações</dt>
                            <dd class="text-[#2d5294]">{{ tenant.notes }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Configs de Boleto -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xs font-semibold text-[#2d7ab5] uppercase tracking-wider">Configurações de Boleto</h2>
                        <div class="flex items-center gap-2">
                            <a v-if="tenant.boleto_configs?.length"
                                :href="route('backoffice.tenants.boletos.index', tenant.id)"
                                class="text-xs border border-gray-200 hover:bg-gray-50 text-gray-600 px-3 py-1.5 rounded-lg transition-colors">
                                Ver Boletos
                            </a>
                            <a v-if="tenant.status === 'active'"
                                :href="route('backoffice.tenants.boleto-configs.create', tenant.id)"
                                class="text-xs bg-[#3a9fd8] hover:bg-[#2889c8] text-white px-3 py-1.5 rounded-lg transition-colors">
                                + Nova Configuração
                            </a>
                        </div>
                    </div>
                    <div v-if="!tenant.boleto_configs?.length" class="py-8 text-center">
                        <div class="text-3xl mb-2">📄</div>
                        <p class="text-sm font-medium text-gray-700 mb-1">Sem configurações de boleto</p>
                        <p class="text-xs text-gray-400">Crie uma configuração para habilitar a emissão via API.</p>
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="cfg in tenant.boleto_configs" :key="cfg.id"
                            class="flex items-center justify-between px-4 py-3 rounded-xl border border-gray-100 hover:border-gray-200 hover:bg-gray-50 transition-colors">
                            <div class="flex items-center gap-3">
                                <span class="text-lg">🏦</span>
                                <div>
                                    <span class="text-sm font-medium text-[#2d5294]">{{ cfg.name }}</span>
                                    <span v-if="cfg.is_default"
                                        class="ml-2 text-xs bg-[#f0f4f8] text-[#2d7ab5] px-1.5 py-0.5 rounded-full">Padrão</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span :class="['text-xs font-medium px-2 py-0.5 rounded-full', cfg.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500']">
                                    {{ cfg.status === 'active' ? 'Ativo' : 'Inativo' }}
                                </span>
                                <a :href="route('backoffice.tenants.boleto-configs.edit', [tenant.id, cfg.id])"
                                    class="text-xs text-[#3a9fd8] hover:underline font-medium">Editar</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Keys -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xs font-semibold text-[#2d7ab5] uppercase tracking-wider">API Keys</h2>
                        <a v-if="tenant.status === 'active'"
                            :href="route('backoffice.tenants.api-keys.create', tenant.id)"
                            class="text-xs bg-[#3a9fd8] hover:bg-[#2889c8] text-white px-3 py-1.5 rounded-lg transition-colors">
                            + Nova API Key
                        </a>
                    </div>
                    <div v-if="!tenant.api_keys?.length" class="py-8 text-center">
                        <div class="text-3xl mb-2">🔑</div>
                        <p class="text-sm font-medium text-gray-700 mb-1">Nenhuma API key</p>
                        <p class="text-xs text-gray-400">Crie uma API key para o tenant acessar a plataforma.</p>
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="key in tenant.api_keys" :key="key.id"
                            class="flex items-center justify-between px-4 py-3 rounded-xl border border-gray-100 hover:border-gray-200 transition-colors">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-medium text-sm text-[#2d5294]">{{ key.name }}</span>
                                    <span v-if="key.revoked_at" class="text-xs bg-gray-100 text-gray-400 px-1.5 py-0.5 rounded-full">Revogada</span>
                                    <span v-else-if="key.expires_at && new Date(key.expires_at) < new Date()" class="text-xs bg-red-100 text-red-500 px-1.5 py-0.5 rounded-full">Expirada</span>
                                    <span v-else class="text-xs bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded-full">Ativa</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <code class="text-xs text-gray-400 font-mono">{{ key.key_prefix }}…</code>
                                    <div class="flex gap-1">
                                        <span v-for="s in key.scopes" :key="s"
                                            class="text-xs bg-[#f0f4f8] text-[#2d7ab5] px-1.5 py-0.5 rounded">{{ s }}</span>
                                    </div>
                                </div>
                            </div>
                            <button v-if="!key.revoked_at"
                                @click="revokeKey(key.id)"
                                class="text-xs text-red-400 hover:text-red-600 hover:underline ml-4 flex-shrink-0">
                                Revogar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-5">

                <!-- Status -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-xs font-semibold text-[#2d7ab5] uppercase tracking-wider mb-4">Alterar Status</h2>
                    <div v-if="!tenant.allowed_transitions?.length" class="text-xs text-gray-400 text-center py-4">
                        Nenhuma transição disponível.
                    </div>
                    <div v-else>
                        <button v-if="!showStatusForm"
                            @click="showStatusForm = true"
                            class="w-full text-sm border border-gray-200 hover:bg-gray-50 text-gray-700 px-3 py-2.5 rounded-xl transition-colors">
                            Alterar status
                        </button>
                        <form v-else @submit.prevent="submitStatus" class="space-y-3">
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Novo status</label>
                                <select v-model="statusForm.status"
                                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 bg-white">
                                    <option value="">Selecione…</option>
                                    <option v-for="t in tenant.allowed_transitions" :key="t.value" :value="t.value">
                                        {{ t.label }}
                                    </option>
                                </select>
                                <p v-if="statusForm.errors.status" class="mt-1 text-xs text-red-500">{{ statusForm.errors.status }}</p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Motivo (obrigatório)</label>
                                <textarea v-model="statusForm.reason" rows="3"
                                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 resize-none" />
                                <p v-if="statusForm.errors.reason" class="mt-1 text-xs text-red-500">{{ statusForm.errors.reason }}</p>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" :disabled="statusForm.processing"
                                    class="flex-1 bg-[#3a9fd8] hover:bg-[#2889c8] disabled:opacity-60 text-white text-xs font-medium py-2 rounded-xl transition-colors">
                                    Confirmar
                                </button>
                                <button type="button" @click="showStatusForm = false"
                                    class="flex-1 border border-gray-200 text-gray-600 text-xs py-2 rounded-xl hover:bg-gray-50 transition-colors">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Histórico -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-xs font-semibold text-[#2d7ab5] uppercase tracking-wider mb-4">Histórico de Status</h2>
                    <div v-if="!tenant.status_history?.length" class="text-xs text-gray-400 text-center py-4">
                        Sem histórico.
                    </div>
                    <ol v-else class="space-y-4">
                        <li v-for="h in tenant.status_history" :key="h.id"
                            class="relative pl-4 border-l-2 border-gray-100">
                            <p class="text-xs text-gray-400 mb-0.5">{{ formatDate(h.created_at) }}</p>
                            <p class="text-xs font-semibold text-[#2d5294]">
                                {{ h.from_status ? (STATUS_LABELS[h.from_status] ?? h.from_status) : '—' }}
                                → {{ STATUS_LABELS[h.to_status] ?? h.to_status }}
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ h.reason }}</p>
                            <p class="text-xs text-gray-400">por {{ h.backoffice_user?.email }}</p>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </BackofficeLayout>
</template>
