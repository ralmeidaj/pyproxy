<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
    configs:         Array,
    defaultConfigId: Number,
})

const form = useForm({
    config_id:      props.defaultConfigId ?? props.configs?.[0]?.id ?? '',
    external_ref:   '',
    payer_name:     '',
    payer_document: '',
    payer_email:    '',
    payer_phone:    '',
    amount:         '',
    due_date:       '',
    metadata:       {},
})

const amountDisplay = ref('')

function updateAmount(e) {
    const raw = e.target.value.replace(/\D/g, '')
    form.amount = raw ? (Number(raw) / 100) : ''
    amountDisplay.value = raw
        ? (Number(raw) / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
        : ''
}

const minDate = computed(() => new Date().toISOString().slice(0, 10))

function submit() {
    form.post(route('portal.boletos.store'))
}
</script>

<template>
    <PortalLayout>

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm text-gray-400 mb-6">
            <a :href="route('portal.boletos.index')" class="hover:text-[#3a9fd8] transition-colors">Boletos</a>
            <span>/</span>
            <span class="text-[#2d5294] font-medium">Emitir Boleto</span>
        </nav>

        <div class="max-w-2xl">
            <div class="mb-6">
                <h1 class="text-xl font-bold text-[#2d5294]">Emitir Boleto</h1>
                <p class="text-sm text-gray-400 mt-1">Preencha os dados para gerar um novo boleto.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">

                <!-- Configuração -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                    <h2 class="text-xs font-semibold text-[#2d7ab5] uppercase tracking-wider">Configuração</h2>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Configuração de Boleto <span class="text-red-400">*</span>
                        </label>
                        <select v-model="form.config_id"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] bg-white"
                            :class="form.errors.config_id ? 'border-red-300 bg-red-50' : ''">
                            <option v-for="c in configs" :key="c.id" :value="c.id">
                                {{ c.name }} {{ c.is_default ? '(padrão)' : '' }}
                            </option>
                        </select>
                        <p v-if="form.errors.config_id" class="mt-1 text-xs text-red-500">{{ form.errors.config_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Referência Externa <span class="text-red-400">*</span>
                        </label>
                        <input v-model="form.external_ref" type="text"
                            placeholder="ex: NF-2024-001, PROCESSO-12345"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]"
                            :class="form.errors.external_ref ? 'border-red-300 bg-red-50' : ''" />
                        <p v-if="form.errors.external_ref" class="mt-1 text-xs text-red-500">{{ form.errors.external_ref }}</p>
                    </div>
                </div>

                <!-- Pagador -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                    <h2 class="text-xs font-semibold text-[#2d7ab5] uppercase tracking-wider">Dados do Pagador</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Nome completo <span class="text-red-400">*</span>
                            </label>
                            <input v-model="form.payer_name" type="text"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]"
                                :class="form.errors.payer_name ? 'border-red-300 bg-red-50' : ''" />
                            <p v-if="form.errors.payer_name" class="mt-1 text-xs text-red-500">{{ form.errors.payer_name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                CPF / CNPJ <span class="text-red-400">*</span>
                            </label>
                            <input v-model="form.payer_document" type="text"
                                placeholder="000.000.000-00"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]"
                                :class="form.errors.payer_document ? 'border-red-300 bg-red-50' : ''" />
                            <p v-if="form.errors.payer_document" class="mt-1 text-xs text-red-500">{{ form.errors.payer_document }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Telefone</label>
                            <input v-model="form.payer_phone" type="text"
                                placeholder="(71) 99999-9999"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]" />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">E-mail</label>
                            <input v-model="form.payer_email" type="email"
                                placeholder="pagador@email.com"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]"
                                :class="form.errors.payer_email ? 'border-red-300 bg-red-50' : ''" />
                            <p v-if="form.errors.payer_email" class="mt-1 text-xs text-red-500">{{ form.errors.payer_email }}</p>
                        </div>
                    </div>
                </div>

                <!-- Valor e Vencimento -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                    <h2 class="text-xs font-semibold text-[#2d7ab5] uppercase tracking-wider">Cobrança</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Valor <span class="text-red-400">*</span>
                            </label>
                            <input :value="amountDisplay" @input="updateAmount"
                                type="text" placeholder="R$ 0,00"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]"
                                :class="form.errors.amount ? 'border-red-300 bg-red-50' : ''" />
                            <p v-if="form.errors.amount" class="mt-1 text-xs text-red-500">{{ form.errors.amount }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Vencimento <span class="text-red-400">*</span>
                            </label>
                            <input v-model="form.due_date" type="date" :min="minDate"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8]"
                                :class="form.errors.due_date ? 'border-red-300 bg-red-50' : ''" />
                            <p v-if="form.errors.due_date" class="mt-1 text-xs text-red-500">{{ form.errors.due_date }}</p>
                        </div>
                    </div>
                </div>

                <!-- Ações -->
                <div class="flex items-center gap-3 pt-1">
                    <button type="submit" :disabled="form.processing"
                        class="bg-[#2d5294] hover:bg-[#2d6abf] disabled:opacity-60 text-white font-medium px-6 py-2.5 rounded-xl text-sm transition-colors shadow-sm">
                        {{ form.processing ? 'Gerando boleto…' : 'Emitir Boleto' }}
                    </button>
                    <a :href="route('portal.boletos.index')"
                        class="border border-gray-200 text-gray-600 hover:bg-gray-50 px-6 py-2.5 rounded-xl text-sm transition-colors">
                        Cancelar
                    </a>
                </div>

            </form>
        </div>
    </PortalLayout>
</template>
