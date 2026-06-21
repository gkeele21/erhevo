<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import LessonItemDisplay from '@/Components/Lesson/LessonItemDisplay.vue'

const props = defineProps({
    lesson: Object,
    canEdit: Boolean,
})

const destroy = () => {
    if (confirm('Delete this lesson? This cannot be undone.')) {
        router.delete(route('lessons.destroy', props.lesson.slug))
    }
}
</script>

<template>
    <Head :title="lesson.title" />
    <AppLayout :title="lesson.title">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-stone-800">{{ lesson.title }}</h2>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('lessons.teach', lesson.slug)"
                        class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700"
                    >
                        Teach
                    </Link>
                    <Link
                        v-if="canEdit"
                        :href="route('lessons.edit', lesson.slug)"
                        class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50"
                    >
                        Edit
                    </Link>
                    <button
                        v-if="canEdit"
                        type="button"
                        @click="destroy"
                        class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="rounded-lg border border-stone-100 bg-white p-8 shadow">
                    <p v-if="lesson.description" class="mb-2 text-stone-600">{{ lesson.description }}</p>
                    <p v-if="lesson.cfm_week" class="mb-6 text-sm text-amber-700">
                        Come Follow Me · {{ lesson.cfm_week.title }}
                    </p>

                    <div v-if="lesson.items.length" class="space-y-8">
                        <div v-for="item in lesson.items" :key="item.id">
                            <LessonItemDisplay :item="item" />
                        </div>
                    </div>
                    <p v-else class="text-stone-400">This lesson has no content yet.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
