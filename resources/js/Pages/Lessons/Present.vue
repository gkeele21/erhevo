<script setup>
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import LessonItemDisplay from '@/Components/Lesson/LessonItemDisplay.vue'

const props = defineProps({
    lesson: Object,
    item: Object,
})

// Emphasis chrome ("Key point" / "If time allows") is for the teacher's
// eyes — strip it from what the class sees.
const stripEmphasis = (item) => ({
    ...item,
    config: { ...(item.config || {}), emphasis: null },
})

const blocks = computed(() =>
    props.item.type === 'group'
        ? (props.item.children || []).map(stripEmphasis)
        : [stripEmphasis(props.item)]
)

const isFullscreen = ref(false)

const toggleFullscreen = async () => {
    if (document.fullscreenElement) {
        await document.exitFullscreen()
        isFullscreen.value = false
    } else {
        await document.documentElement.requestFullscreen()
        isFullscreen.value = true
    }
}
</script>

<template>
    <Head :title="`Presenting: ${lesson.title}`" />

    <div class="flex min-h-screen flex-col bg-white">
        <main class="flex flex-1 items-center justify-center px-8 py-12 sm:px-16">
            <!-- zoom scales the whole block up for across-the-room reading -->
            <div class="w-full max-w-5xl space-y-12" style="zoom: 1.5">
                <div v-if="item.type === 'group' && item.config?.title">
                    <h1 class="border-b border-stone-200 pb-3 text-3xl font-bold text-stone-800">
                        {{ item.config.title }}
                    </h1>
                </div>
                <LessonItemDisplay
                    v-for="block in blocks"
                    :key="block.id"
                    :item="block"
                    teaching
                />
            </div>
        </main>

        <footer class="flex items-center justify-between px-6 py-4 text-sm text-stone-300">
            <span class="truncate">{{ lesson.title }}</span>
            <button
                type="button"
                class="flex items-center gap-2 text-stone-400 hover:text-stone-600"
                @click="toggleFullscreen"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path v-if="!isFullscreen" stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                    <path v-else stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25" />
                </svg>
                {{ isFullscreen ? 'Exit full screen' : 'Full screen' }}
            </button>
        </footer>
    </div>
</template>
