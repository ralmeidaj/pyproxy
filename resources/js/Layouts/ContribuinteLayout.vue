<template>
    <div class="min-h-screen bg-slate-50">
        <header class="bg-white border-b border-slate-200 shadow-sm">
            <div class="px-6 py-3 flex items-center justify-between">
                <!-- Logo Payproxy -->
                <div class="flex items-center gap-2.5">
                    <svg width="32" height="32" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <polygon points="50,18 77.7,34 77.7,66 50,82 22.3,66 22.3,34" fill="#e0e8ff"/>
                        <line x1="50" y1="50" x2="50"   y2="18"  stroke="#5068d0" stroke-width="1.5" stroke-linecap="round"/>
                        <line x1="50" y1="50" x2="77.7" y2="34"  stroke="#5068d0" stroke-width="1.5" stroke-linecap="round"/>
                        <line x1="50" y1="50" x2="77.7" y2="66"  stroke="#5068d0" stroke-width="1.5" stroke-linecap="round"/>
                        <line x1="50" y1="50" x2="50"   y2="82"  stroke="#5068d0" stroke-width="1.5" stroke-linecap="round"/>
                        <line x1="50" y1="50" x2="22.3" y2="66"  stroke="#5068d0" stroke-width="1.5" stroke-linecap="round"/>
                        <line x1="50" y1="50" x2="22.3" y2="34"  stroke="#5068d0" stroke-width="1.5" stroke-linecap="round"/>
                        <circle cx="50"   cy="18" r="4" fill="#2a3db5"/>
                        <circle cx="77.7" cy="34" r="4" fill="#2a3db5"/>
                        <circle cx="77.7" cy="66" r="4" fill="#2a3db5"/>
                        <circle cx="50"   cy="82" r="4" fill="#2a3db5"/>
                        <circle cx="22.3" cy="66" r="4" fill="#2a3db5"/>
                        <circle cx="22.3" cy="34" r="4" fill="#2a3db5"/>
                        <circle cx="50"   cy="50" r="10" fill="#1ec86e"/>
                        <polygon points="50,5 89,27.5 89,72.5 50,95 11,72.5 11,27.5"
                                 fill="none" stroke="#4855c4" stroke-width="4" stroke-linejoin="round"/>
                    </svg>
                    <div>
                        <div class="text-base font-bold text-slate-800 leading-tight">Payproxy</div>
                        <div class="text-xs text-slate-400 leading-tight">Portal do Contribuinte</div>
                    </div>
                </div>
                <div class="text-xs text-slate-400">Arrecadação Municipal</div>
            </div>
        </header>

        <main class="px-6 py-8 max-w-4xl mx-auto">
            <div v-if="$page.props.flash?.success" class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.info" class="mb-6 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg text-sm">
                {{ $page.props.flash.info }}
            </div>
            <div v-if="$page.props.errors?.token" class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                {{ $page.props.errors.token }}
            </div>
            <slot />
        </main>

        <footer class="border-t border-slate-200 bg-white mt-12 px-6 py-4 text-center text-xs text-slate-400">
            Payproxy &mdash; Plataforma de Arrecadação Municipal &bull;
            Este portal é protegido por link de acesso único e temporário.
        </footer>
    </div>
</template>
