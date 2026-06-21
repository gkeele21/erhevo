<script setup>
import { Head, Link } from '@inertiajs/vue3'
import LessonItemDisplay from '@/Components/Lesson/LessonItemDisplay.vue'

defineProps({
    lesson: Object,
})
</script>

<template>
    <Head :title="`Teaching: ${lesson.title}`" />

    <div class="min-h-screen bg-stone-50">
        <!-- Minimal top bar -->
        <header class="sticky top-0 z-10 border-b border-stone-200 bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-3">
                <h1 class="truncate text-lg font-semibold text-stone-800">{{ lesson.title }}</h1>
                <Link
                    :href="route('lessons.show', lesson.slug)"
                    class="flex-shrink-0 text-sm text-stone-500 hover:text-stone-800"
                >
                    Done
                </Link>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-10">
            <p v-if="lesson.cfm_week" class="mb-8 text-sm text-amber-700">
                Come Follow Me · {{ lesson.cfm_week.title }}
            </p>

            <div v-if="lesson.items.length" class="space-y-12">
                <section v-for="item in lesson.items" :key="item.id">
                    <LessonItemDisplay :item="item" teaching />
                </section>
            </div>
            <p v-else class="text-stone-400">This lesson has no content yet.</p>
        </main>
    </div>
</template>
