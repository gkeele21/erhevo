<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    steps: Array
})

const completed = computed(() => props.steps.filter(s => s.done).length)
const allDone = computed(() => completed.value === props.steps.length)
const progressPercent = computed(() => Math.round((completed.value / props.steps.length) * 100))

const dismiss = () => {
    router.put(route('user-settings.update'), { getting_started_dismissed: true }, {
        preserveScroll: true
    })
}
</script>

<template>
    <div class="bg-white rounded-lg shadow border border-navy-50 p-6">
        <div class="flex items-start justify-between gap-4 mb-1">
            <h3 class="text-lg font-semibold text-navy">
                {{ allDone ? "You're all set! 🎉" : 'Getting started on Erhevo' }}
            </h3>
            <button
                type="button"
                class="text-sm text-teal-300 hover:text-teal whitespace-nowrap"
                @click="dismiss"
            >
                {{ allDone ? 'Dismiss' : 'Hide this' }}
            </button>
        </div>
        <p class="text-sm text-teal mb-4">
            {{ allDone
                ? "You've tried everything — enjoy!"
                : 'A quick tour of what you can do here.' }}
            <Link :href="route('guide')" class="text-amber hover:text-amber-600 font-medium">
                Read the full guide &rarr;
            </Link>
        </p>

        <div class="flex items-center gap-3 mb-5">
            <div class="flex-1 bg-navy-50 rounded-full h-2">
                <div
                    class="bg-teal h-2 rounded-full transition-all"
                    :style="{ width: progressPercent + '%' }"
                />
            </div>
            <span class="text-sm text-teal whitespace-nowrap">{{ completed }} of {{ steps.length }}</span>
        </div>

        <ul class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3">
            <li v-for="step in steps" :key="step.label" class="flex gap-3">
                <span
                    class="shrink-0 mt-0.5 w-6 h-6 rounded-full border-2 flex items-center justify-center"
                    :class="step.done ? 'bg-green-500 border-green-500 text-white' : 'border-navy-100 text-transparent'"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <Link
                        :href="step.href"
                        class="font-medium"
                        :class="step.done ? 'text-teal-300 hover:text-teal' : 'text-navy hover:text-teal'"
                    >
                        {{ step.label }}
                    </Link>
                    <p class="text-sm text-teal">{{ step.description }}</p>
                </div>
            </li>
        </ul>
    </div>
</template>
