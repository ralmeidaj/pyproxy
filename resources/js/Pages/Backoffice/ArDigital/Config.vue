<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import BackofficeLayout from '@/Layouts/BackofficeLayout.vue'

const props = defineProps({
    tenant: Object,
    config: Object,
})

const page    = usePage()
const success = computed(() => page.props.flash?.success)

const form = useForm({
    enabled:          props.config.enabled,
    pixel_tracking:   props.config.pixel_tracking,
    cpf_confirmation: props.config.cpf_confirmation,
    act_provider:     props.config.act_provider ?? 'serpro',
})

const ACT_PROVIDERS = [
    { value: 'serpro',   label: 'Serpro' },
    { value: 'bry',      label: 'BRy Tecnologia' },
    { value: 'soluti',   label: 'Soluti' },
    { value: 'certisign', label: 'Certisign' },
]

function submit() {
    form.put(route('backoffice.tenants.ar-digital.update', props.tenant.id))
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
            <span class="text-[#2d5294] font-medium">AR Digital</span>
        </nav>

        <div class="flex items-center gap-3 mb-6">
            <h1 class="text-xl font-bold text-[#2d5294]">AR Digital</h1>
            <span class="text-sm text-gray-400">— {{ tenant.name }}</span>
        </div>

        <!-- Success banner -->
        <div v-if="success"
            class="mb-5 rounded-2xl bg-emerald-50 border border-emerald-200 px-5 py-4 text-sm text-emerald-800 font-medium">
            {{ success }}
        </div>

        <div class="max-w-2xl space-y-5">

            <!-- O que é AR Digital -->
            <div class="bg-[#f0f7ff] border border-[#c3daf5] rounded-2xl p-5 text-sm text-[#1a4a6b]">
                <p class="font-semibold mb-1">O que é o AR Digital?</p>
                <p class="leading-relaxed text-[#2d5294]">
                    Módulo de Aviso de Recebimento Digital — prova jurídica de entrega do boleto ao destinatário.
                    Ao habilitar, cada boleto emitido gera uma notificação rastreada com carimbo de tempo
                    RFC 3161 (ICP-Brasil), pixel de leitura de e-mail e confirmação de recebimento pelo pagador.
                </p>
            </div>

            <!-- Formulário -->
            <form @submit.prevent="submit" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-6">

                <!-- Habilitar módulo -->
                <div class="flex items-start justify-between gap-6">
                    <div>
                        <h2 class="text-sm font-semibold text-[#2d5294]">Módulo AR Digital</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Quando habilitado, cada boleto emitido por este tenant gera automaticamente
                            uma notificação de AR Digital rastreada.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="form.enabled = !form.enabled"
                        :class="[
                            'relative flex-shrink-0 w-11 h-6 rounded-full transition-colors duration-200',
                            form.enabled ? 'bg-[#3a9fd8]' : 'bg-gray-200'
                        ]">
                        <span :class="[
                            'absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-200',
                            form.enabled ? 'translate-x-5' : 'translate-x-0'
                        ]" />
                    </button>
                </div>

                <template v-if="form.enabled">
                    <hr class="border-gray-100" />

                    <!-- Pixel tracking -->
                    <div class="flex items-start justify-between gap-6">
                        <div>
                            <h3 class="text-sm font-medium text-gray-700">Pixel de Rastreamento</h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Insere um pixel invisível no e-mail de notificação para registrar
                                o momento em que o destinatário abre a mensagem (evento <code class="text-xs bg-gray-100 px-1 rounded">leitura_email</code>).
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="form.pixel_tracking = !form.pixel_tracking"
                            :class="[
                                'relative flex-shrink-0 w-11 h-6 rounded-full transition-colors duration-200',
                                form.pixel_tracking ? 'bg-[#3a9fd8]' : 'bg-gray-200'
                            ]">
                            <span :class="[
                                'absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-200',
                                form.pixel_tracking ? 'translate-x-5' : 'translate-x-0'
                            ]" />
                        </button>
                    </div>

                    <hr class="border-gray-100" />

                    <!-- CPF confirmation -->
                    <div class="flex items-start justify-between gap-6">
                        <div>
                            <h3 class="text-sm font-medium text-gray-700">Confirmação por CPF</h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Exige que o destinatário informe o CPF na página de acesso ao boleto
                                antes de baixar o PDF. Gera o evento <code class="text-xs bg-gray-100 px-1 rounded">confirmado</code>
                                com maior força probatória.
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="form.cpf_confirmation = !form.cpf_confirmation"
                            :class="[
                                'relative flex-shrink-0 w-11 h-6 rounded-full transition-colors duration-200',
                                form.cpf_confirmation ? 'bg-[#3a9fd8]' : 'bg-gray-200'
                            ]">
                            <span :class="[
                                'absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-200',
                                form.cpf_confirmation ? 'translate-x-5' : 'translate-x-0'
                            ]" />
                        </button>
                    </div>

                    <hr class="border-gray-100" />

                    <!-- ACT Provider -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Provedor ACT ICP-Brasil
                        </label>
                        <p class="text-xs text-gray-500 mb-3">
                            Autoridade de Carimbo de Tempo usada para emitir os carimbos RFC 3161 em cada evento.
                            Todos os provedores listados são credenciados pelo ITI (Instituto Nacional de Tecnologia
                            da Informação).
                        </p>
                        <select
                            v-model="form.act_provider"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#3a9fd8]/30">
                            <option v-for="p in ACT_PROVIDERS" :key="p.value" :value="p.value">
                                {{ p.label }}
                            </option>
                        </select>
                        <p v-if="form.errors.act_provider" class="mt-1 text-xs text-red-500">{{ form.errors.act_provider }}</p>
                        <p class="text-xs text-amber-600 mt-2 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                            Atenção: a contratação do serviço de carimbo de tempo junto ao provedor escolhido é
                            responsabilidade da Ciberian. Em produção, configure as credenciais no arquivo
                            <code class="font-mono">config/services.php</code>.
                        </p>
                    </div>
                </template>

                <!-- Actions -->
                <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                    <a :href="route('backoffice.tenants.show', tenant.id)"
                        class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
                        Voltar ao tenant
                    </a>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-[#3a9fd8] hover:bg-[#2889c8] disabled:opacity-60 text-white text-sm font-medium px-6 py-2.5 rounded-xl transition-colors">
                        {{ form.processing ? 'Salvando…' : 'Salvar configuração' }}
                    </button>
                </div>
            </form>

        </div>
    </BackofficeLayout>
</template>
