<script setup>
import { ordinanceLabel } from '@/utils/ordinances.js'

defineProps({
    ordinances: { type: Array, default: () => [] },
})
</script>

<template>
    <span class="inline-flex flex-wrap gap-1.5">
        <template v-if="ordinances.length">
            <span
                v-for="ordinance in ordinances"
                :key="ordinance"
                class="px-2 py-0.5 bg-navy-50 text-navy-600 rounded-full text-xs whitespace-nowrap"
            >
                {{ ordinanceLabel(ordinance) }}
            </span>
        </template>
        <span v-else class="px-2 py-0.5 bg-stone-100 text-stone-500 rounded-full text-xs">
            Visit
        </span>
    </span>
</template>
