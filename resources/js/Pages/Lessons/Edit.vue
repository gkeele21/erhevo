<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import LessonFormFields from '@/Components/Lesson/LessonFormFields.vue'

const props = defineProps({
    lesson: Object,
    itemTypes: Array,
    visibilityOptions: Array,
    cfmWeeks: Array,
    currentCfmWeek: Object,
    scriptureBooks: Array,
    uploadLimits: Object,
})

const mapLeaf = (item) => ({
    type: item.type,
    content: item.content ?? '',
    config: item.config ?? {},
})

const mapNode = (item) => item.type === 'group'
    ? {
        type: 'group',
        content: '',
        config: item.config ?? {},
        children: (item.children ?? []).map(mapLeaf),
    }
    : mapLeaf(item)

const form = useForm({
    title: props.lesson.title,
    description: props.lesson.description ?? '',
    cfm_week_id: props.lesson.cfm_week_id ?? null,
    visibility: props.lesson.visibility,
    publish: !!props.lesson.published_at,
    items: (props.lesson.items ?? []).map(mapNode),
})

const submit = (publish) => {
    form.publish = publish
    form.put(route('lessons.update', props.lesson.slug))
}
</script>

<template>
    <Head title="Edit Lesson" />
    <AppLayout title="Edit Lesson">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-stone-800">
                Edit Lesson
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <form @submit.prevent="submit(true)" class="space-y-8">
                    <LessonFormFields
                        :form="form"
                        :item-types="itemTypes"
                        :visibility-options="visibilityOptions"
                        :cfm-weeks="cfmWeeks"
                        :scripture-books="scriptureBooks"
                        :upload-limits="uploadLimits"
                    />

                    <div class="flex items-center justify-between">
                        <Link :href="route('lessons.show', lesson.slug)" class="text-stone-600 hover:text-stone-800">
                            Cancel
                        </Link>
                        <div class="flex gap-4">
                            <button
                                type="button"
                                @click="submit(false)"
                                :disabled="form.processing"
                                class="rounded-lg border border-stone-300 px-6 py-3 text-stone-700 hover:bg-stone-50 disabled:opacity-50"
                            >
                                Save as Draft
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-lg bg-amber-600 px-6 py-3 text-white hover:bg-amber-700 disabled:opacity-50"
                            >
                                {{ lesson.published_at ? 'Save' : 'Publish' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
