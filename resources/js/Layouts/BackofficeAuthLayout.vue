<script setup>
import PayproxyLogo from '@/Components/PayproxyLogo.vue'
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4">
        <div class="w-full max-w-md">
            <div class="flex flex-col items-center mb-8">
                <PayproxyLogo :width="180" />
                <p class="text-sm text-gray-400 mt-2 font-medium tracking-wide uppercase text-xs">Backoffice</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                <slot />
            </div>
        </div>
    </div>
</template>
