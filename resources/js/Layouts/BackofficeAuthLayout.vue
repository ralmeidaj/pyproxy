<script setup>
//
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-[#2d5294]">Payproxy</h1>
                <p class="text-sm text-gray-500 mt-1">Backoffice</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                <slot />
            </div>
        </div>
    </div>
</template>
