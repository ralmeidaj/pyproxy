<script setup>
import { useForm } from '@inertiajs/vue3'
import BackofficeLayout from '@/Layouts/BackofficeLayout.vue'

const props = defineProps({
    tenant:       Object,
    bankPartners: Array,
})

const form = useForm({
    bank_partner_id:             props.bankPartners[0]?.id ?? '',
    name:                        '',
    is_default:                  false,
    credential_api_key:          '',
    credential_chave:            '',
    prazo_vencimento_dias:       3,
    multa_percentual:            2,
    juros_percentual_mes:        1,
    desconto_percentual:         0,
    desconto_antecedencia_dias:  0,
    instrucoes:                  [],
    webhook_url:                 '',
    webhook_secret:              '',
    status:                      'active',
})

function submit() {
    form.post(route('backoffice.tenants.boleto-configs.store', props.tenant.id))
}
</script>

<template>
    <BackofficeLayout>
        <div class="mb-6 flex items-center gap-2 text-sm text-gray-500">
            <a :href="route('backoffice.tenants.index')" class="hover:text-[#3a9fd8]">Tenants</a>
            <span>/</span>
            <a :href="route('backoffice.tenants.show', tenant.id)" class="hover:text-[#3a9fd8]">{{ tenant.name }}</a>
            <span>/</span>
            <span class="text-gray-700 font-medium">Nova Configuração de Boleto</span>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
            <h1 class="text-xl font-semibold text-[#2d5294] mb-6">Nova Configuração de Boleto</h1>

            <form @submit.prevent="submit" class="space-y-8">

                <!-- Parceiro e nome -->
                <section>
                    <h2 class="text-sm font-semibold text-[#2d7ab5] uppercase tracking-wide mb-4">Identificação</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Parceiro Bancário</label>
                            <select v-model="form.bank_partner_id"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]">
                                <option v-for="bp in bankPartners" :key="bp.id" :value="bp.id">{{ bp.name }}</option>
                            </select>
                            <p v-if="form.errors.bank_partner_id" class="text-red-500 text-xs mt-1">{{ form.errors.bank_partner_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome da Configuração</label>
                            <input v-model="form.name" type="text" placeholder="Ex: Boleto Principal"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <input id="is_default" type="checkbox" v-model="form.is_default" class="w-4 h-4 accent-[#3a9fd8]" />
                            <label for="is_default" class="text-sm text-gray-700">Configuração padrão do tenant</label>
                        </div>
                    </div>
                </section>

                <!-- Credenciais -->
                <section>
                    <h2 class="text-sm font-semibold text-[#2d7ab5] uppercase tracking-wide mb-4">Credenciais PJBank</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Credencial PJBank <span class="text-xs text-gray-400 font-normal">(vai na URL)</span></label>
                            <input v-model="form.credential_api_key" type="password" autocomplete="new-password"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                            <p v-if="form.errors.credential_api_key" class="text-red-500 text-xs mt-1">{{ form.errors.credential_api_key }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Chave PJBank <span class="text-xs text-gray-400 font-normal">(header x-chave)</span></label>
                            <input v-model="form.credential_chave" type="password" autocomplete="new-password"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                            <p v-if="form.errors.credential_chave" class="text-red-500 text-xs mt-1">{{ form.errors.credential_chave }}</p>
                        </div>
                    </div>
                </section>

                <!-- Parâmetros do boleto -->
                <section>
                    <h2 class="text-sm font-semibold text-[#2d7ab5] uppercase tracking-wide mb-4">Parâmetros do Boleto</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prazo Vencimento (dias)</label>
                            <input v-model.number="form.prazo_vencimento_dias" type="number" min="1" max="365"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Multa (%)</label>
                            <input v-model.number="form.multa_percentual" type="number" min="0" max="10" step="0.01"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Juros a.m. (%)</label>
                            <input v-model.number="form.juros_percentual_mes" type="number" min="0" max="3" step="0.01"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Desconto (%)</label>
                            <input v-model.number="form.desconto_percentual" type="number" min="0" max="100" step="0.01"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Antecedência desc. (dias)</label>
                            <input v-model.number="form.desconto_antecedencia_dias" type="number" min="0" max="365"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                        </div>
                    </div>
                </section>

                <!-- Webhook -->
                <section>
                    <h2 class="text-sm font-semibold text-[#2d7ab5] uppercase tracking-wide mb-4">Webhook (opcional)</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">URL de Webhook</label>
                            <input v-model="form.webhook_url" type="url" placeholder="https://sistema.cliente.com.br/webhook"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                            <p v-if="form.errors.webhook_url" class="text-red-500 text-xs mt-1">{{ form.errors.webhook_url }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Webhook Secret (HMAC)</label>
                            <input v-model="form.webhook_secret" type="password" autocomplete="new-password"
                                placeholder="Deixe em branco para gerar automaticamente"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                        </div>
                    </div>
                </section>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <a :href="route('backoffice.tenants.show', tenant.id)"
                        class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </a>
                    <button type="submit" :disabled="form.processing"
                        class="px-6 py-2 text-sm bg-[#3a9fd8] text-white rounded-lg hover:bg-[#2889c8] disabled:opacity-50 transition-colors">
                        <span v-if="form.processing">Salvando...</span>
                        <span v-else>Criar Configuração</span>
                    </button>
                </div>
            </form>
        </div>
    </BackofficeLayout>
</template>
