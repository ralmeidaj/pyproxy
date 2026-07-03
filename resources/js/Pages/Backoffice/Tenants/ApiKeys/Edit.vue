<script setup>
import { useForm } from '@inertiajs/vue3'
import BackofficeLayout from '@/Layouts/BackofficeLayout.vue'
import { ref } from 'vue'

const props = defineProps({
    tenant: Object,
    apiKey: Object,
})

const AVAILABLE_SCOPES = [
    { value: 'boleto:write', label: 'boleto:write', description: 'Emitir e cancelar boletos' },
    { value: 'boleto:read',  label: 'boleto:read',  description: 'Consultar boletos' },
    { value: 'report:read',  label: 'report:read',  description: 'Acessar relatórios' },
]

const form = useForm({
    name:                   props.apiKey.name,
    scopes:                 props.apiKey.scopes ?? [],
    rate_limit_per_minute:  props.apiKey.rate_limit_per_minute ?? 60,
    daily_limit:            props.apiKey.daily_limit ?? '',
    monthly_limit:          props.apiKey.monthly_limit ?? '',
    max_amount_cents:       props.apiKey.max_amount_cents ?? '',
    allow_batch:            props.apiKey.allow_batch ?? true,
    allowed_metadata_types: (props.apiKey.allowed_metadata_types ?? []).join(', '),
    expires_at:             props.apiKey.expires_at ?? '',
})

function submit() {
    const payload = {
        ...form.data(),
        daily_limit:            form.daily_limit        !== '' ? Number(form.daily_limit)       : null,
        monthly_limit:          form.monthly_limit      !== '' ? Number(form.monthly_limit)     : null,
        max_amount_cents:       form.max_amount_cents   !== '' ? Number(form.max_amount_cents)  : null,
        rate_limit_per_minute:  Number(form.rate_limit_per_minute),
        allowed_metadata_types: form.allowed_metadata_types
            ? form.allowed_metadata_types.split(',').map(s => s.trim()).filter(Boolean)
            : null,
        expires_at: form.expires_at || null,
    }
    form.transform(() => payload).put(route('backoffice.tenants.api-keys.update', [props.tenant.id, props.apiKey.id]))
}

const maxAmountDisplay = ref(
    props.apiKey.max_amount_cents
        ? (Number(props.apiKey.max_amount_cents) / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
        : ''
)

function updateMaxAmount(e) {
    const raw = e.target.value.replace(/\D/g, '')
    form.max_amount_cents = raw
    maxAmountDisplay.value = raw ? (Number(raw) / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : ''
}
</script>

<template>
    <BackofficeLayout>

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm text-gray-400 mb-6">
            <a :href="route('backoffice.tenants.index')" class="hover:text-[#3a9fd8] transition-colors">Tenants</a>
            <span>/</span>
            <a :href="route('backoffice.tenants.show', tenant.id)" class="hover:text-[#3a9fd8] transition-colors">{{ tenant.name }}</a>
            <span>/</span>
            <span class="text-[#2d5294] font-medium">Editar API Key</span>
        </nav>

        <div>
            <div class="mb-6">
                <h1 class="text-xl font-bold text-[#2d5294]">Editar API Key</h1>
                <p class="text-sm text-gray-400 mt-1">
                    Altere permissões e limites sem revogar a chave.
                    A chave em si não é alterada.
                </p>
            </div>

            <!-- Key prefix info -->
            <div class="flex items-center gap-3 bg-blue-50 border border-blue-200 rounded-2xl px-5 py-4 mb-5">
                <span class="text-blue-500 text-lg">🔑</span>
                <div>
                    <p class="text-xs text-blue-600 font-medium">Prefixo da chave</p>
                    <p class="font-mono text-sm text-blue-800">{{ apiKey.key_prefix }}••••••••••••••••</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">

                <!-- Nome e escopos -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-5">
                    <h2 class="text-xs font-semibold text-[#2d7ab5] uppercase tracking-wider">Identificação e Escopos</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Nome <span class="text-red-400">*</span>
                        </label>
                        <input v-model="form.name" type="text" placeholder="ex: Integração Produção"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] transition-colors"
                            :class="form.errors.name ? 'border-red-300 bg-red-50' : ''" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Escopos <span class="text-red-400">*</span>
                        </label>
                        <div class="space-y-2">
                            <label v-for="scope in AVAILABLE_SCOPES" :key="scope.value"
                                class="flex items-start gap-3 p-3.5 border rounded-xl cursor-pointer transition-all"
                                :class="form.scopes.includes(scope.value)
                                    ? 'border-[#3a9fd8] bg-blue-50'
                                    : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'">
                                <input type="checkbox" :value="scope.value" v-model="form.scopes"
                                    class="mt-0.5 accent-[#3a9fd8]" />
                                <div>
                                    <p class="text-sm font-mono font-semibold text-[#2d7ab5]">{{ scope.label }}</p>
                                    <p class="text-xs text-gray-500">{{ scope.description }}</p>
                                </div>
                            </label>
                        </div>
                        <p v-if="form.errors.scopes" class="mt-1 text-xs text-red-500">{{ form.errors.scopes }}</p>
                    </div>
                </div>

                <!-- Limites -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                    <h2 class="text-xs font-semibold text-[#2d7ab5] uppercase tracking-wider">Limites de Uso</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1.5">Req/min (padrão: 60)</label>
                            <input v-model="form.rate_limit_per_minute" type="number" min="1" max="600"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1.5">Limite diário (boletos)</label>
                            <input v-model="form.daily_limit" type="number" min="1" placeholder="Sem limite"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1.5">Limite mensal (boletos)</label>
                            <input v-model="form.monthly_limit" type="number" min="1" placeholder="Sem limite"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1.5">Valor máximo por boleto</label>
                            <input :value="maxAmountDisplay" @input="updateMaxAmount"
                                type="text" placeholder="Sem limite (R$)"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]" />
                        </div>
                    </div>
                </div>

                <!-- Config extra -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                    <h2 class="text-xs font-semibold text-[#2d7ab5] uppercase tracking-wider">Configurações Adicionais</h2>
                    <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl hover:bg-gray-50 transition-colors">
                        <input type="checkbox" v-model="form.allow_batch" class="accent-[#3a9fd8]" />
                        <span class="text-sm text-gray-700">Permitir emissão em lote (batch)</span>
                    </label>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1.5">
                            Tipos de metadados permitidos <span class="text-gray-400">(separados por vírgula)</span>
                        </label>
                        <input v-model="form.allowed_metadata_types" type="text"
                            placeholder="ex: nfe, processo, contrato"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1.5">Expira em</label>
                        <input v-model="form.expires_at" type="date"
                            :min="new Date(Date.now() + 86400000).toISOString().slice(0, 10)"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]" />
                        <p class="mt-1 text-xs text-gray-400">Deixe em branco para não expirar.</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <button type="submit" :disabled="form.processing"
                        class="bg-[#3a9fd8] hover:bg-[#2889c8] disabled:opacity-60 text-white font-medium px-6 py-2.5 rounded-xl text-sm transition-colors shadow-sm">
                        {{ form.processing ? 'Salvando…' : 'Salvar alterações' }}
                    </button>
                    <a :href="route('backoffice.tenants.show', tenant.id)"
                        class="border border-gray-200 text-gray-600 hover:bg-gray-50 px-6 py-2.5 rounded-xl text-sm transition-colors">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </BackofficeLayout>
</template>
