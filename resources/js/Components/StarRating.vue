<script setup>
import { computed, ref } from 'vue'

// Star outline path, shared by the readonly and interactive renderings.
const STAR_PATH = 'M11.48 3.5a.56.56 0 011.04 0l2.13 4.31c.08.17.24.29.43.31l4.76.7c.46.06.64.63.31.96l-3.44 3.36a.56.56 0 00-.16.5l.81 4.74c.08.46-.4.81-.82.6l-4.26-2.24a.56.56 0 00-.52 0l-4.26 2.24c-.41.21-.9-.14-.82-.6l.81-4.74a.56.56 0 00-.16-.5L3.85 9.78a.56.56 0 01.31-.96l4.76-.7a.56.56 0 00.43-.31l2.13-4.31z'

const props = defineProps({
    // The rating to show. Fractional values are allowed when readonly, so an
    // average like 4.3 renders as four and a bit stars.
    modelValue: { type: Number, default: null },
    readonly: { type: Boolean, default: false },
    size: { type: String, default: 'md' }
})

const emit = defineEmits(['update:modelValue'])

const stars = [1, 2, 3, 4, 5]
const hovered = ref(null)

const sizeClass = computed(() => (props.size === 'sm' ? 'size-4' : 'size-5'))

// Hovering previews the rating a click would set.
const previewed = computed(() => hovered.value ?? props.modelValue ?? 0)

const fillWidth = computed(() => {
    const value = Math.min(Math.max(props.modelValue || 0, 0), 5)

    return `${(value / 5) * 100}%`
})
</script>

<template>
    <!-- Readonly: an outline row with a filled row clipped over it, so a
         fractional average can fill part of a star. -->
    <span v-if="readonly" class="relative inline-flex shrink-0" :aria-label="`${modelValue ?? 0} out of 5 stars`">
        <span class="inline-flex text-stone-300">
            <svg v-for="star in stars" :key="star" :class="[sizeClass, 'shrink-0']" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path :d="STAR_PATH" />
            </svg>
        </span>
        <span
            class="absolute inset-y-0 left-0 inline-flex overflow-hidden text-amber-500"
            :style="{ width: fillWidth }"
        >
            <svg v-for="star in stars" :key="star" :class="[sizeClass, 'shrink-0']" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path :d="STAR_PATH" />
            </svg>
        </span>
    </span>

    <span v-else class="inline-flex shrink-0" @mouseleave="hovered = null">
        <button
            v-for="star in stars"
            :key="star"
            type="button"
            class="p-0.5 transition-transform hover:scale-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 rounded"
            :aria-label="`Rate ${star} ${star === 1 ? 'star' : 'stars'}`"
            :title="`Rate ${star} ${star === 1 ? 'star' : 'stars'}`"
            @mouseenter="hovered = star"
            @click.prevent.stop="emit('update:modelValue', star)"
        >
            <svg
                :class="[sizeClass, star <= previewed ? 'text-amber-500' : 'text-stone-300']"
                viewBox="0 0 24 24"
                fill="currentColor"
                aria-hidden="true"
            >
                <path :d="STAR_PATH" />
            </svg>
        </button>
    </span>
</template>
