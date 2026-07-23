<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    value:    { type: String, default: null },
    masked:   { type: String, required: true },
    field:    { type: String, required: true },
    auditUrl: { type: String, default: null },
})

const revealed = ref(false)

function reveal() {
    revealed.value = true
    if (props.auditUrl) {
        axios.post(props.auditUrl, { field: props.field }).catch(() => {})
    }
}
</script>

<template>
    <span class="inline-flex items-center gap-1.5">
        <span class="font-mono text-sm">{{ revealed ? value : masked }}</span>
        <button v-if="!revealed && value"
            @click.stop="reveal"
            type="button"
            class="text-xs text-[#3a9fd8] hover:underline font-medium whitespace-nowrap">
            Exibir
        </button>
    </span>
</template>
