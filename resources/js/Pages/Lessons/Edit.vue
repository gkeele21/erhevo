<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Head, useForm, Link, router } from '@inertiajs/vue3'
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
    churchCallings: Array,
})

// Existing elements start collapsed so a long lesson is easy to scan;
// each card (and Expand all) opens them as needed.
const mapLeaf = (item) => ({
    type: item.type,
    content: item.content ?? '',
    config: item.config ?? {},
    post_id: item.post_id ?? null,
    _collapsed: true,
})

const mapNode = (item) => item.type === 'group'
    ? {
        type: 'group',
        content: '',
        config: item.config ?? {},
        children: (item.children ?? []).map(mapLeaf),
    }
    : mapLeaf(item)

// A published lesson with pending edits stores them in draft_data; the editor
// works on that draft, and the live lesson only changes on publish.
const source = props.lesson.draft_data ?? props.lesson

const form = useForm({
    title: source.title,
    description: source.description ?? '',
    cfm_week_id: source.cfm_week_id ?? null,
    visibility: source.visibility,
    publish: !!props.lesson.published_at,
    items: (source.items ?? []).map(mapNode),
})

const isPublished = computed(() => !!props.lesson.published_at)
const hasDraft = computed(() => !!props.lesson.has_draft)

const discardDraft = () => {
    if (!confirm('Discard your draft changes and go back to the published version?')) return
    router.delete(route('lessons.discard-draft', props.lesson.slug))
}

const unpublish = () => {
    if (!confirm('Unpublish this lesson? It will no longer be visible to anyone but you until you publish it again.')) return
    router.put(route('lessons.unpublish', props.lesson.slug))
}

const submit = (publish) => {
    form.publish = publish
    form.transform((data) => data)
    form.put(route('lessons.update', props.lesson.slug))
}

// --- Auto-save every 30s so nobody loses work ---
// Sends publish: false: on a never-published lesson that saves the draft
// directly; on a published lesson it saves into the pending draft revision,
// so auto-save can never publish or alter what readers currently see.
const lastAutosavedAt = ref(null)
let autosaveTimer = null
let lastSavedSnapshot = JSON.stringify(form.data())

const autosave = () => {
    if (form.processing) return

    // Only save when something actually changed since the last save.
    const snapshot = JSON.stringify(form.data())
    if (snapshot === lastSavedSnapshot) return

    form.transform((data) => ({ ...data, publish: false, autosave: true }))
    form.put(route('lessons.update', props.lesson.slug), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            lastSavedSnapshot = snapshot
            lastAutosavedAt.value = new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })
        },
        // Stay quiet on half-finished input; the manual save will surface errors.
        onError: () => form.clearErrors(),
    })
}

onMounted(() => { autosaveTimer = setInterval(autosave, 30_000) })
onUnmounted(() => clearInterval(autosaveTimer))
</script>

<template>
    <Head title="Edit Lesson" />
    <AppLayout title="Edit Lesson">
        <template #header>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold leading-tight text-stone-800">
                    Edit Lesson
                </h2>
                <span
                    v-if="!isPublished"
                    class="rounded-full bg-stone-200 px-2.5 py-0.5 text-xs font-medium text-stone-600"
                >
                    Draft
                </span>
                <template v-else>
                    <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">
                        Published
                    </span>
                    <span
                        v-if="hasDraft"
                        class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700"
                    >
                        Unpublished changes
                    </span>
                </template>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div
                    v-if="hasDraft"
                    class="mb-6 flex items-center justify-between gap-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                >
                    <p>
                        You're editing a draft revision — readers still see the published version
                        until you click Publish Changes.
                    </p>
                    <button
                        type="button"
                        @click="discardDraft"
                        class="shrink-0 font-medium text-amber-700 underline hover:text-amber-900"
                    >
                        Discard draft changes
                    </button>
                </div>

                <form @submit.prevent="submit(true)" class="space-y-8">
                    <LessonFormFields
                        :form="form"
                        :item-types="itemTypes"
                        :visibility-options="visibilityOptions"
                        :cfm-weeks="cfmWeeks"
                        :scripture-books="scriptureBooks"
                        :upload-limits="uploadLimits"
                        :church-callings="churchCallings"
                    />

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-6">
                            <Link :href="route('lessons.show', lesson.slug)" class="text-stone-600 hover:text-stone-800">
                                Cancel
                            </Link>
                            <button
                                v-if="isPublished"
                                type="button"
                                @click="unpublish"
                                class="text-sm text-red-600 hover:text-red-800"
                            >
                                Unpublish
                            </button>
                        </div>
                        <div class="flex items-center gap-4">
                            <span v-if="lastAutosavedAt" class="text-sm text-stone-400">
                                Auto-saved at {{ lastAutosavedAt }}
                            </span>
                            <button
                                type="button"
                                @click="submit(false)"
                                :disabled="form.processing"
                                class="rounded-lg border border-stone-300 px-6 py-3 text-stone-700 hover:bg-stone-50 disabled:opacity-50"
                            >
                                {{ isPublished ? 'Save Draft' : 'Save as Draft' }}
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-lg bg-amber-600 px-6 py-3 text-white hover:bg-amber-700 disabled:opacity-50"
                            >
                                {{ isPublished ? 'Publish Changes' : 'Publish' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
