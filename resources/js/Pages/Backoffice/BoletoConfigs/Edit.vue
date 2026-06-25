<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import BackofficeLayout from '@/Layouts/BackofficeLayout.vue'

const props = defineProps({
    tenant:       Object,
    config:       Object,
    bankPartners: Array,
    splits:       Array,
})

const editingId = ref(null)

const splitForm = useForm({
    name:     '',
    type:     'percentage',
    value:    '',
    priority: 0,
    payee_details: {
        nome:                  '',
        cnpj:                  '',
        banco_repasse:         '',
        agencia_repasse:       '',
        conta_repasse:         '',
        porcentagem_encargos:  0,
    },
})

function addSplit() {
    splitForm.post(
        route('backoffice.tenants.boleto-configs.split-configs.store', [props.tenant.id, props.config.id]),
        { onSuccess: () => splitForm.reset() }
    )
}

function startEdit(split) {
    editingId.value = split.id
    splitForm.name     = split.name
    splitForm.type     = split.type
    splitForm.value    = parseFloat(split.value)
    splitForm.priority = split.priority ?? 0
    splitForm.payee_details = {
        nome:                 split.payee_details?.nome                ?? '',
        cnpj:                 split.payee_details?.cnpj                ?? '',
        banco_repasse:        split.payee_details?.banco_repasse       ?? '',
        agencia_repasse:      split.payee_details?.agencia_repasse     ?? '',
        conta_repasse:        split.payee_details?.conta_repasse       ?? '',
        porcentagem_encargos: split.payee_details?.porcentagem_encargos ?? 0,
    }
    splitForm.clearErrors()
}

function saveEdit() {
    splitForm.put(
        route('backoffice.tenants.boleto-configs.split-configs.update', [props.tenant.id, props.config.id, editingId.value]),
        {
            preserveScroll: true,
            onSuccess: () => {
                editingId.value = null
                splitForm.reset()
                router.reload({ only: ['splits'] })
            },
        }
    )
}

function cancelEdit() {
    editingId.value = null
    splitForm.reset()
}

function removeSplit(splitId) {
    if (!confirm('Remover este favorecido do split?')) return
    useForm({}).delete(
        route('backoffice.tenants.boleto-configs.split-configs.destroy', [props.tenant.id, props.config.id, splitId])
    )
}

const form = useForm({
    bank_partner_id:             props.config.bank_partner_id,
    name:                        props.config.name,
    is_default:                  props.config.is_default,
    credential_api_key:          '',
    credential_chave:            '',
    prazo_vencimento_dias:       props.config.prazo_vencimento_dias,
    multa_percentual:            props.config.multa_percentual,
    juros_percentual_mes:        props.config.juros_percentual_mes,
    desconto_percentual:         props.config.desconto_percentual,
    desconto_antecedencia_dias:  props.config.desconto_antecedencia_dias,
    instrucoes:                  props.config.instrucoes ?? [],
    webhook_url:                 props.config.webhook_url ?? '',
    webhook_secret:              '',
    status:                      props.config.status,
})

function submit() {
    form.put(route('backoffice.tenants.boleto-configs.update', [props.tenant.id, props.config.id]))
}
</script>

<template>
    <BackofficeLayout>
        <div class="mb-6 flex items-center gap-2 text-sm text-gray-500">
            <a :href="route('backoffice.tenants.index')" class="hover:text-[#3a9fd8]">Tenants</a>
            <span>/</span>
            <a :href="route('backoffice.tenants.show', tenant.id)" class="hover:text-[#3a9fd8]">{{ tenant.name }}</a>
            <span>/</span>
            <span class="text-gray-700 font-medium">Editar Configuração de Boleto</span>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
            <h1 class="text-xl font-semibold text-[#2d5294] mb-6">Editar: {{ config.name }}</h1>

            <form @submit.prevent="submit" class="space-y-8">

                <!-- Identificação -->
                <section>
                    <h2 class="text-sm font-semibold text-[#2d7ab5] uppercase tracking-wide mb-4">Identificação</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Parceiro Bancário</label>
                            <select v-model="form.bank_partner_id"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]">
                                <option v-for="bp in bankPartners" :key="bp.id" :value="bp.id">{{ bp.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome da Configuração</label>
                            <input v-model="form.name" type="text"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <input id="is_default" type="checkbox" v-model="form.is_default" class="w-4 h-4 accent-[#3a9fd8]" />
                            <label for="is_default" class="text-sm text-gray-700">Configuração padrão do tenant</label>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select v-model="form.status"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]">
                                <option value="active">Ativo</option>
                                <option value="inactive">Inativo</option>
                            </select>
                        </div>
                    </div>
                </section>

                <!-- Credenciais -->
                <section>
                    <h2 class="text-sm font-semibold text-[#2d7ab5] uppercase tracking-wide mb-4">Credenciais PJBank</h2>
                    <p class="text-xs text-gray-500 mb-3">Deixe em branco para manter as credenciais atuais.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Credencial PJBank <span class="text-xs text-gray-400 font-normal">(vai na URL)</span></label>
                            <input v-model="form.credential_api_key" type="password" autocomplete="new-password"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Chave PJBank <span class="text-xs text-gray-400 font-normal">(header x-chave)</span></label>
                            <input v-model="form.credential_chave" type="password" autocomplete="new-password"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                        </div>
                    </div>
                </section>

                <!-- Parâmetros -->
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
                    <h2 class="text-sm font-semibold text-[#2d7ab5] uppercase tracking-wide mb-4">Webhook</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">URL de Webhook</label>
                            <input v-model="form.webhook_url" type="url"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Novo Webhook Secret</label>
                            <p class="text-xs text-gray-500 mb-1">
                                <span v-if="config.has_webhook_secret">Secret já configurado — deixe em branco para manter.</span>
                                <span v-else>Deixe em branco para gerar automaticamente.</span>
                            </p>
                            <input v-model="form.webhook_secret" type="password" autocomplete="new-password"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                        </div>
                    </div>
                </section>

                <!-- Split configs — CRUD -->
                <section>
                    <h2 class="text-sm font-semibold text-[#2d7ab5] uppercase tracking-wide mb-4">Split de Pagamento</h2>

                    <!-- Tabela de splits existentes -->
                    <div v-if="splits && splits.length > 0" class="overflow-x-auto mb-6">
                        <table class="w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
                            <thead class="bg-[#2d5294] text-white">
                                <tr>
                                    <th class="px-4 py-2 text-left">Favorecido</th>
                                    <th class="px-4 py-2 text-left">CNPJ</th>
                                    <th class="px-4 py-2 text-left">Banco / Ag / Conta</th>
                                    <th class="px-4 py-2 text-left">Tipo</th>
                                    <th class="px-4 py-2 text-right">Valor</th>
                                    <th class="px-4 py-2 text-center">Prio.</th>
                                    <th class="px-4 py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(split, i) in splits" :key="split.id"
                                    :class="[
                                        editingId === split.id ? 'bg-blue-50 ring-2 ring-inset ring-[#3a9fd8]' : (i % 2 === 1 ? 'bg-[#f5f8fc]' : 'bg-white')
                                    ]">
                                    <td class="px-4 py-2 font-medium">{{ split.name }}</td>
                                    <td class="px-4 py-2 text-gray-500 text-xs">{{ split.payee_details?.cnpj ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-500 text-xs">
                                        {{ split.payee_details
                                            ? split.payee_details.banco_repasse + ' / ' + split.payee_details.agencia_repasse + ' / ' + split.payee_details.conta_repasse
                                            : '—' }}
                                    </td>
                                    <td class="px-4 py-2">{{ split.type === 'percentage' ? 'Percentual' : 'Fixo' }}</td>
                                    <td class="px-4 py-2 text-right">
                                        {{ split.type === 'percentage' ? parseFloat(split.value) + '%' : 'R$ ' + Number(split.value).toFixed(2) }}
                                    </td>
                                    <td class="px-4 py-2 text-center">{{ split.priority }}</td>
                                    <td class="px-4 py-2 text-center flex items-center justify-center gap-3">
                                        <button type="button" @click="startEdit(split)"
                                            class="text-[#2d7ab5] hover:text-[#1e5a8a] text-xs font-medium">
                                            Editar
                                        </button>
                                        <button type="button" @click="removeSplit(split.id)"
                                            class="text-red-500 hover:text-red-700 text-xs font-medium">
                                            Remover
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="text-sm text-gray-400 mb-6">Nenhum favorecido configurado.</p>

                    <!-- Formulário para adicionar / editar split -->
                    <div :class="editingId ? 'border border-[#3a9fd8] rounded-xl p-5 bg-blue-50' : 'border border-dashed border-gray-300 rounded-xl p-5 bg-gray-50'">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">
                            {{ editingId ? 'Editar Favorecido' : 'Adicionar Favorecido' }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Nome do Favorecido</label>
                                <input v-model="splitForm.name" type="text" placeholder="Ex: SEFAZ Salvador"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                                <p v-if="splitForm.errors.name" class="text-red-500 text-xs mt-1">{{ splitForm.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">CNPJ</label>
                                <input v-model="splitForm.payee_details.cnpj" type="text" placeholder="00.000.000/0001-00"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                                <p v-if="splitForm.errors['payee_details.cnpj']" class="text-red-500 text-xs mt-1">{{ splitForm.errors['payee_details.cnpj'] }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Nome Completo (razão social)</label>
                                <input v-model="splitForm.payee_details.nome" type="text" placeholder="Razão social completa"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Banco (código)</label>
                                <input v-model="splitForm.payee_details.banco_repasse" type="text" placeholder="260"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                                <p v-if="splitForm.errors['payee_details.banco_repasse']" class="text-red-500 text-xs mt-1">{{ splitForm.errors['payee_details.banco_repasse'] }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Agência</label>
                                <input v-model="splitForm.payee_details.agencia_repasse" type="text" placeholder="0001"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                                <p v-if="splitForm.errors['payee_details.agencia_repasse']" class="text-red-500 text-xs mt-1">{{ splitForm.errors['payee_details.agencia_repasse'] }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Conta</label>
                                <input v-model="splitForm.payee_details.conta_repasse" type="text" placeholder="123456789"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                                <p v-if="splitForm.errors['payee_details.conta_repasse']" class="text-red-500 text-xs mt-1">{{ splitForm.errors['payee_details.conta_repasse'] }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
                                <select v-model="splitForm.type"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]">
                                    <option value="percentage">Percentual (%)</option>
                                    <option value="fixed_amount">Valor Fixo (R$)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    {{ splitForm.type === 'percentage' ? 'Percentual (%)' : 'Valor (R$)' }}
                                </label>
                                <input v-model.number="splitForm.value" type="number" min="0.01" step="0.01"
                                    :placeholder="splitForm.type === 'percentage' ? '85' : '15.00'"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                                <p v-if="splitForm.errors.value" class="text-red-500 text-xs mt-1">{{ splitForm.errors.value }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">% Encargos repassados</label>
                                <input v-model.number="splitForm.payee_details.porcentagem_encargos" type="number" min="0" max="100" step="1"
                                    placeholder="0"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]" />
                            </div>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button v-if="editingId" type="button" @click="cancelEdit"
                                class="px-5 py-2 text-sm border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                Cancelar
                            </button>
                            <button type="button"
                                @click="editingId ? saveEdit() : addSplit()"
                                :disabled="splitForm.processing"
                                class="px-5 py-2 text-sm bg-[#2d5294] text-white rounded-lg hover:bg-[#1e3d75] disabled:opacity-50 transition-colors">
                                <span v-if="splitForm.processing">{{ editingId ? 'Salvando...' : 'Adicionando...' }}</span>
                                <span v-else>{{ editingId ? 'Salvar alterações' : '+ Adicionar Favorecido' }}</span>
                            </button>
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
                        <span v-else>Salvar Alterações</span>
                    </button>
                </div>
            </form>
        </div>
    </BackofficeLayout>
</template>
