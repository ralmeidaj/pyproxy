<script setup>
import { useForm } from '@inertiajs/vue3'
import BackofficeLayout from '@/Layouts/BackofficeLayout.vue'

const props = defineProps({ tenant: Object })

const form = useForm({
    name:                props.tenant.name,
    document:            props.tenant.document,
    email:               props.tenant.email,
    phone:               props.tenant.phone ?? '',
    communication_model: props.tenant.communication_model,
    notes:               props.tenant.notes ?? '',
    email_entity_name:   props.tenant.email_entity_name ?? '',
    email_logo_url:      props.tenant.email_logo_url ?? '',
    email_custom_text:   props.tenant.email_custom_text ?? '',
})

function submit() {
    form.put(route('backoffice.tenants.update', props.tenant.id))
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
            <span class="text-[#2d5294] font-medium">Editar</span>
        </nav>

        <div>
            <div class="mb-6">
                <h1 class="text-xl font-bold text-[#2d5294]">Editar Tenant</h1>
                <p class="text-sm text-gray-400 mt-1">Atualize os dados do tenant.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">

                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                    <h2 class="text-xs font-semibold text-[#2d7ab5] uppercase tracking-wider">Dados da Empresa</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Nome da empresa</label>
                            <input v-model="form.name" type="text"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] transition-colors"
                                :class="form.errors.name ? 'border-red-300 bg-red-50' : ''" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">CNPJ</label>
                            <input v-model="form.document" type="text" placeholder="00.000.000/0001-00"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] transition-colors"
                                :class="form.errors.document ? 'border-red-300 bg-red-50' : ''" />
                            <p v-if="form.errors.document" class="mt-1 text-xs text-red-500">{{ form.errors.document }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Telefone</label>
                            <input v-model="form.phone" type="text" placeholder="(71) 99999-9999"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] transition-colors" />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">E-mail</label>
                            <input v-model="form.email" type="email"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] transition-colors"
                                :class="form.errors.email ? 'border-red-300 bg-red-50' : ''" />
                            <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                    <h2 class="text-xs font-semibold text-[#2d7ab5] uppercase tracking-wider">Configurações</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Modelo de comunicação</label>
                        <select v-model="form.communication_model"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] transition-colors bg-white">
                            <option value="email">Modelo 1 — E-mail</option>
                            <option value="email_whatsapp">Modelo 2 — E-mail + WhatsApp</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Observações</label>
                        <textarea v-model="form.notes" rows="3"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] transition-colors resize-none" />
                    </div>
                </div>

                <!-- Template de E-mail (RF-MSG-04) -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                    <div>
                        <h2 class="text-xs font-semibold text-[#2d7ab5] uppercase tracking-wider">Template de E-mail</h2>
                        <p class="text-xs text-gray-400 mt-1">
                            Personalize o cabeçalho e rodapé dos e-mails enviados aos pagadores deste tenant.
                            Deixe em branco para usar o padrão da plataforma.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nome do remetente / entidade</label>
                        <input v-model="form.email_entity_name" type="text"
                            placeholder="Ex: Prefeitura de Salvador — SEFAZ"
                            maxlength="150"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] transition-colors"
                            :class="form.errors.email_entity_name ? 'border-red-300 bg-red-50' : ''" />
                        <p class="mt-1 text-xs text-gray-400">Aparece no cabeçalho e rodapé do e-mail (máx. 150 caracteres).</p>
                        <p v-if="form.errors.email_entity_name" class="mt-1 text-xs text-red-500">{{ form.errors.email_entity_name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">URL do logo</label>
                        <input v-model="form.email_logo_url" type="url"
                            placeholder="https://example.com/logo.png"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] transition-colors"
                            :class="form.errors.email_logo_url ? 'border-red-300 bg-red-50' : ''" />
                        <p class="mt-1 text-xs text-gray-400">Imagem exibida no cabeçalho no lugar do nome. Deve ser HTTPS e acessível publicamente.</p>
                        <p v-if="form.errors.email_logo_url" class="mt-1 text-xs text-red-500">{{ form.errors.email_logo_url }}</p>

                        <!-- Preview do logo -->
                        <div v-if="form.email_logo_url" class="mt-3 inline-flex items-center gap-3 bg-[#2d5294] rounded-xl px-4 py-3">
                            <img :src="form.email_logo_url" alt="Preview" class="h-10 max-w-[160px] object-contain" />
                            <span class="text-xs text-white/50">preview</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Texto complementar no rodapé</label>
                        <textarea v-model="form.email_custom_text" rows="3" maxlength="1000"
                            placeholder="Ex: Em caso de dúvidas, entre em contato com a Secretaria da Fazenda pelo telefone (71) 3202-0000."
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30 focus:border-[#3a9fd8] transition-colors resize-none"
                            :class="form.errors.email_custom_text ? 'border-red-300 bg-red-50' : ''" />
                        <p class="mt-1 text-xs text-gray-400">Aparece após o conteúdo principal do e-mail (máx. 1.000 caracteres).</p>
                        <p v-if="form.errors.email_custom_text" class="mt-1 text-xs text-red-500">{{ form.errors.email_custom_text }}</p>
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
