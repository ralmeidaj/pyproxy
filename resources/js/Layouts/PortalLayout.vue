<script setup>
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'

const page = usePage()
const url  = computed(() => page.url)

const sidebarOpen = ref(false)

const user   = computed(() => page.props.auth?.portal?.user   ?? page.props.user   ?? {})
const tenant = computed(() => page.props.auth?.portal?.tenant ?? page.props.tenant ?? {})

const flash = computed(() => page.props.flash ?? {})

function isActive(routeName) {
    try {
        return url.value.startsWith(route(routeName).replace(window.location.origin, ''))
    } catch {
        return false
    }
}

function logout() {
    router.post(route('portal.auth.logout'))
}

const navItems = [
    { name: 'Dashboard',  route: 'portal.dashboard',      icon: '📊' },
    { name: 'Boletos',    route: 'portal.boletos.index',  icon: '📄' },
    { name: 'Relatórios', route: 'portal.reports.index',  icon: '📈' },
    { name: 'Perfil',     route: 'portal.profile',        icon: '👤' },
]

const roleLabels = {
    admin:    'Administrador',
    operator: 'Operador',
    viewer:   'Visualizador',
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex">

        <!-- Overlay mobile -->
        <div v-if="sidebarOpen" @click="sidebarOpen = false"
            class="fixed inset-0 bg-black/40 z-20 lg:hidden" />

        <!-- Sidebar -->
        <aside :class="[
            'fixed inset-y-0 left-0 z-30 flex w-64 flex-col bg-[#2d5294] transition-transform duration-200',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        ]">
            <!-- Logo / Tenant -->
            <div class="flex items-center gap-3 px-5 py-5 border-b border-white/10">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-white/20 text-white font-bold text-sm">
                    {{ tenant.name?.[0]?.toUpperCase() ?? 'P' }}
                </div>
                <div class="min-w-0">
                    <p class="text-white font-semibold text-sm truncate">{{ tenant.name ?? 'Portal' }}</p>
                    <p class="text-white/50 text-xs">Payproxy</p>
                </div>
            </div>

            <!-- Nav -->
            <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
                <a v-for="item in navItems" :key="item.route"
                    :href="route(item.route)"
                    :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group',
                        isActive(item.route)
                            ? 'bg-white/20 text-white'
                            : 'text-white/70 hover:bg-white/10 hover:text-white',
                    ]">
                    <span class="text-base w-5 text-center flex-shrink-0">{{ item.icon }}</span>
                    <span>{{ item.name }}</span>
                    <span v-if="isActive(item.route)"
                        class="ml-auto w-1.5 h-1.5 rounded-full bg-white flex-shrink-0" />
                </a>
            </nav>

            <!-- User info + logout -->
            <div class="px-4 py-4 border-t border-white/10">
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-white/20 text-white text-xs font-bold">
                        {{ user.name?.[0]?.toUpperCase() ?? '?' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-white text-xs font-medium truncate">{{ user.name }}</p>
                        <p class="text-white/50 text-xs truncate">{{ user.email }}</p>
                    </div>
                </div>
                <span class="inline-block text-xs text-white/60 bg-white/10 px-2 py-0.5 rounded-md mb-3">
                    {{ roleLabels[user.role] ?? user.role }}
                </span>
                <button @click="logout"
                    class="w-full text-left text-xs text-white/60 hover:text-white transition-colors py-1 flex items-center gap-2">
                    <span>→</span> Sair
                </button>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 lg:pl-64 flex flex-col min-h-screen">

            <!-- Mobile header -->
            <div class="lg:hidden flex items-center gap-4 bg-white border-b border-gray-200 px-4 py-3">
                <button @click="sidebarOpen = true" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <span class="font-semibold text-[#2d5294] text-sm">{{ tenant.name ?? 'Portal' }}</span>
            </div>

            <!-- Flash messages -->
            <div v-if="flash.success || flash.error" class="px-6 pt-4">
                <div v-if="flash.success"
                    class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm">
                    <span>✅</span> {{ flash.success }}
                </div>
                <div v-if="flash.error"
                    class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm">
                    <span>❌</span> {{ flash.error }}
                </div>
            </div>

            <!-- Page content -->
            <main class="flex-1 px-6 py-6">
                <slot />
            </main>
        </div>
    </div>
</template>
