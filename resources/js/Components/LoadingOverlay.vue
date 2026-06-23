<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const loading = ref(false);

router.on('start', () => { loading.value = true; });
router.on('finish', () => { loading.value = false; });
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="loading"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-white/50 backdrop-blur-[2px]"
            >
                <div class="flex flex-col items-center gap-3">
                    <div class="w-10 h-10 border-[3px] border-[#d0dae8] border-t-[#3a9fd8] rounded-full animate-spin" />
                    <span class="text-xs text-gray-400 font-medium tracking-wide">Aguarde…</span>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
