<script setup>
import { Link } from '@inertiajs/vue3'
import { formatLocalDate } from '@/utils/date.js'
import OrdinanceBadges from '@/Components/Temples/OrdinanceBadges.vue'

defineProps({
    visits: { type: Array, default: () => [] },
    // Show each visit's temple name (My Visits page); off on a temple's
    // own page.
    showTemple: { type: Boolean, default: false },
})

defineEmits(['edit', 'delete'])
</script>

<template>
    <ul class="divide-y divide-stone-100">
        <li v-for="visit in visits" :key="visit.id" class="py-3 flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-sm text-stone-800">
                    <Link
                        v-if="showTemple && visit.temple"
                        :href="route('temples.show', visit.temple.slug)"
                        class="font-semibold hover:text-teal-700"
                    >
                        {{ visit.temple.name }}
                    </Link>
                    <span :class="{ 'text-stone-500': showTemple }">
                        {{ formatLocalDate(visit.visited_on, { year: 'numeric', month: 'long', day: 'numeric' }) }}
                    </span>
                </p>
                <OrdinanceBadges :ordinances="visit.ordinances" class="mt-1.5" />
                <p v-if="visit.notes" class="text-sm text-stone-500 mt-1.5 whitespace-pre-line">{{ visit.notes }}</p>
            </div>
            <div class="shrink-0 flex gap-3 text-sm">
                <button type="button" class="text-stone-500 hover:text-teal-700" @click="$emit('edit', visit)">
                    Edit
                </button>
                <button type="button" class="text-stone-500 hover:text-red-600" @click="$emit('delete', visit)">
                    Delete
                </button>
            </div>
        </li>
    </ul>
</template>
