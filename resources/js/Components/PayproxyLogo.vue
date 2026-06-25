<script setup>
import { computed } from 'vue'

const props = defineProps({
    width: { type: Number, default: 200 },
})

const height = computed(() => Math.round(props.width * 160 / 220))
</script>

<template>
    <svg :width="props.width" :height="height" viewBox="0 0 220 160" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="pp-hb" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#3d5afe"/>
                <stop offset="100%" stop-color="#7c4dff"/>
            </linearGradient>
        </defs>

        <!-- Inner hex light fill (center=110,52 r=33) -->
        <polygon points="110,19 138.6,35.5 138.6,68.5 110,85 81.4,68.5 81.4,35.5" fill="#e0e8ff"/>

        <!-- Hub lines -->
        <line x1="110" y1="52" x2="110"   y2="19"   stroke="#5068d0" stroke-width="2" stroke-linecap="round"/>
        <line x1="110" y1="52" x2="138.6" y2="35.5" stroke="#5068d0" stroke-width="2" stroke-linecap="round"/>
        <line x1="110" y1="52" x2="138.6" y2="68.5" stroke="#5068d0" stroke-width="2" stroke-linecap="round"/>
        <line x1="110" y1="52" x2="110"   y2="85"   stroke="#5068d0" stroke-width="2" stroke-linecap="round"/>
        <line x1="110" y1="52" x2="81.4"  y2="68.5" stroke="#5068d0" stroke-width="2" stroke-linecap="round"/>
        <line x1="110" y1="52" x2="81.4"  y2="35.5" stroke="#5068d0" stroke-width="2" stroke-linecap="round"/>

        <!-- Corner nodes -->
        <circle cx="110"   cy="19"   r="4.5" fill="#2a3db5"/>
        <circle cx="138.6" cy="35.5" r="4.5" fill="#2a3db5"/>
        <circle cx="138.6" cy="68.5" r="4.5" fill="#2a3db5"/>
        <circle cx="110"   cy="85"   r="4.5" fill="#2a3db5"/>
        <circle cx="81.4"  cy="68.5" r="4.5" fill="#2a3db5"/>
        <circle cx="81.4"  cy="35.5" r="4.5" fill="#2a3db5"/>

        <!-- Center green hub -->
        <circle cx="110" cy="52" r="11" fill="#1ec86e"/>

        <!-- Outer hexagon border (center=110,52 r=46) -->
        <polygon points="110,6 149.8,29 149.8,75 110,98 70.2,75 70.2,29"
                 fill="none" stroke="url(#pp-hb)" stroke-width="4" stroke-linejoin="round"/>

        <!-- "PayProxy" logotype -->
        <text text-anchor="middle" x="110" y="128"
              font-family="Arial, Helvetica, sans-serif"
              font-size="31" font-weight="800" letter-spacing="-0.5">
            <tspan fill="#1b2a6b">Pay</tspan><tspan fill="#4855c4">Proxy</tspan>
        </text>

        <!-- Subtitle -->
        <text text-anchor="middle" x="110" y="149"
              font-family="Arial, Helvetica, sans-serif"
              font-size="7.5" fill="#8899bb" letter-spacing="2">PAYMENT INFRASTRUCTURE</text>
    </svg>
</template>
