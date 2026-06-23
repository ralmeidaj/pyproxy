<script setup>
import BackofficeLayout from '@/Layouts/BackofficeLayout.vue'

const props = defineProps({
    user:  Object,
    stats: Object,
})

const kpis = [
    {
        label: 'Tenants Ativos',
        value: props.stats?.active_tenants ?? 0,
        icon:  '🏢',
        color: 'from-[#3a9fd8] to-[#1a4a8a]',
        bg:    'bg-blue-50',
        text:  'text-[#3a9fd8]',
    },
    {
        label: 'Pendentes de Aprovação',
        value: props.stats?.pending_tenants ?? 0,
        icon:  '⏳',
        color: 'from-amber-400 to-amber-500',
        bg:    'bg-amber-50',
        text:  'text-amber-600',
    },
    {
        label: 'Boletos no Mês',
        value: props.stats?.boletos_this_month ?? 0,
        icon:  '📄',
        color: 'from-purple-400 to-purple-500',
        bg:    'bg-purple-50',
        text:  'text-purple-600',
    },
    {
        label: 'Pagos no Mês',
        value: props.stats?.boletos_paid_month ?? 0,
        icon:  '✅',
        color: 'from-emerald-400 to-emerald-500',
        bg:    'bg-emerald-50',
        text:  'text-emerald-600',
    },
]

const totalTenants = props.stats?.total_tenants ?? 0
</script>

<template>
    <BackofficeLayout>

        <!-- Hero -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#2d5294] via-[#2d6abf] to-[#3a9fd8] p-7 text-white shadow-lg mb-6">
            <div class="pointer-events-none absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/5" />
            <div class="pointer-events-none absolute -bottom-12 right-32 h-36 w-36 rounded-full bg-white/5" />
            <div class="relative">
                <p class="text-sm text-white/60 mb-1">Bem-vindo de volta,</p>
                <h1 class="text-2xl font-bold mb-1">{{ user?.name }}</h1>
                <p class="text-sm text-white/60">Plataforma Payproxy — Backoffice</p>
            </div>
            <div class="absolute right-7 top-1/2 -translate-y-1/2 text-right hidden sm:block">
                <p class="text-4xl font-bold">{{ totalTenants }}</p>
                <p class="text-sm text-white/60">tenant{{ totalTenants !== 1 ? 's' : '' }} cadastrado{{ totalTenants !== 1 ? 's' : '' }}</p>
            </div>
        </div>

        <!-- KPI grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div v-for="kpi in kpis" :key="kpi.label"
                class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div :class="['absolute inset-x-0 top-0 h-1 bg-gradient-to-r', kpi.color]" />
                <div :class="['inline-flex h-10 w-10 items-center justify-center rounded-xl text-xl', kpi.bg]">
                    {{ kpi.icon }}
                </div>
                <p :class="['mt-3 text-2xl font-bold', kpi.text]">{{ kpi.value }}</p>
                <p class="mt-0.5 text-xs text-gray-400">{{ kpi.label }}</p>
            </div>
        </div>

        <!-- Quick links -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a :href="route('backoffice.tenants.index')"
                class="group flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:shadow-md hover:border-[#3a9fd8]/30 transition-all">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-2xl group-hover:scale-105 transition-transform">
                    🏢
                </div>
                <div>
                    <p class="font-semibold text-[#2d5294]">Gerenciar Tenants</p>
                    <p class="text-sm text-gray-400">Cadastrar, editar e gerenciar tenants</p>
                </div>
                <span class="ml-auto text-gray-300 group-hover:text-[#3a9fd8] transition-colors text-lg">›</span>
            </a>

            <a :href="route('backoffice.tenants.create')"
                class="group flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:shadow-md hover:border-[#3a9fd8]/30 transition-all">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-2xl group-hover:scale-105 transition-transform">
                    ➕
                </div>
                <div>
                    <p class="font-semibold text-[#2d5294]">Novo Tenant</p>
                    <p class="text-sm text-gray-400">Onboarding de novo cliente</p>
                </div>
                <span class="ml-auto text-gray-300 group-hover:text-[#3a9fd8] transition-colors text-lg">›</span>
            </a>
        </div>

    </BackofficeLayout>
</template>
