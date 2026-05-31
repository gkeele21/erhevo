<script setup>
import CfmWeekSelector from '@/Components/Story/CfmWeekSelector.vue'

defineProps({
    cfmWeekIds: {
        type: Array,
        default: () => []
    },
    cfmWeeks: {
        type: Array,
        default: () => []
    },
    currentCfmWeek: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['update:cfmWeekIds'])
</script>

<template>
    <div class="bg-amber-50 rounded-lg shadow p-6 space-y-4 border border-amber-200">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <h3 class="font-semibold text-amber-900">LDS Content</h3>
        </div>

        <p class="text-sm text-amber-800">
            Link your post to Come Follow Me study weeks so others studying the same material can discover it.
        </p>

        <CfmWeekSelector
            :model-value="cfmWeekIds"
            @update:model-value="emit('update:cfmWeekIds', $event)"
            :cfm-weeks="cfmWeeks"
            :current-cfm-week="currentCfmWeek"
        />

        <p class="text-xs text-amber-700 italic">
            More LDS features coming soon: General Conference session linking, temple experience posts, and more.
        </p>
    </div>
</template>
