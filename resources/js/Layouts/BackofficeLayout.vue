<script setup>
import { computed, ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

const page  = usePage()
const user  = computed(() => page.props.auth?.user ?? {})
const flash = computed(() => page.props.flash ?? {})
const url   = computed(() => page.url)

const logoutForm = useForm({})
function logout() { logoutForm.post(route('backoffice.auth.logout')) }

const sidebarOpen = ref(false)

const navItems = [
    { label: 'Dashboard',  route: 'backoffice.dashboard',      icon: '📊' },
    { label: 'Tenants',    route: 'backoffice.tenants.index',  icon: '🏢' },
    { label: 'Relatórios', route: 'backoffice.reports.index',  icon: '📈' },
]

function isActive(routeName) {
    try {
        return url.value.startsWith(route(routeName).replace(window.location.origin, ''))
    } catch {
        return false
    }
}

const ROLE_LABELS = {
    super_admin: 'Super Admin',
    admin:       'Admin',
    support:     'Suporte',
}
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">

        <!-- Overlay mobile -->
        <div v-if="sidebarOpen" @click="sidebarOpen = false"
            class="fixed inset-0 z-20 bg-black/40 lg:hidden" />

        <!-- Sidebar -->
        <aside :class="[
            'fixed inset-y-0 left-0 z-30 flex w-64 flex-col bg-[#2d5294] transition-transform duration-200 ease-in-out',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        ]">

            <!-- Logo -->
            <div class="flex h-16 items-center px-6 border-b border-white/10">
                <span class="text-xl font-bold text-white tracking-tight">Payproxy</span>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto px-3 py-5 space-y-1">
                <a v-for="item in navItems" :key="item.route"
                    :href="route(item.route)"
                    :class="[
                        'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors',
                        isActive(item.route)
                            ? 'bg-white/15 text-white'
                            : 'text-white/60 hover:bg-white/10 hover:text-white'
                    ]">
                    <span class="text-base">{{ item.icon }}</span>
                    {{ item.label }}
                    <span v-if="isActive(item.route)"
                        class="ml-auto h-1.5 w-1.5 rounded-full bg-white" />
                </a>
            </nav>

            <!-- User info -->
            <div class="border-t border-white/10 p-4">
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#3a9fd8] text-white text-sm font-bold flex-shrink-0">
                        {{ (user.name ?? 'U')[0].toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ user.name }}</p>
                        <p class="text-xs text-white/50 truncate">{{ user.email }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs bg-white/10 text-white/70 px-2 py-0.5 rounded-full">
                        {{ ROLE_LABELS[user.role] ?? user.role }}
                    </span>
                    <button @click="logout"
                        class="text-xs text-white/50 hover:text-red-400 transition-colors">
                        Sair
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main area -->
        <div class="flex flex-1 flex-col lg:pl-64">

            <!-- Mobile topbar -->
            <div class="sticky top-0 z-10 flex h-14 items-center gap-4 border-b border-gray-200 bg-white px-4 shadow-sm lg:hidden">
                <button @click="sidebarOpen = !sidebarOpen"
                    class="text-gray-500 hover:text-gray-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <span class="text-base font-bold text-[#2d5294]">Payproxy</span>
            </div>

            <!-- Flash messages -->
            <div v-if="flash.success || flash.error" class="px-6 pt-5">
                <div v-if="flash.success"
                    class="flex items-center gap-3 rounded-2xl bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3">
                    <span class="text-base">✅</span>
                    {{ flash.success }}
                </div>
                <div v-if="flash.error"
                    class="flex items-center gap-3 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3">
                    <span class="text-base">❌</span>
                    {{ flash.error }}
                </div>
            </div>

            <!-- Page content -->
            <main class="flex-1 px-6 py-6">
                <slot />
            </main>
        </div>
    </div>
</template>
