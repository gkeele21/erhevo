<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    lessons: Object,
    filters: Object,
})

const page = usePage()
const search = ref(props.filters?.search ?? '')
const sort = ref(props.filters?.sort ?? 'first_published')
const kind = ref(props.filters?.kind ?? '')
const mineOnly = ref(props.filters?.mine === '1' || props.filters?.mine === 1 || props.filters?.mine === true)

const kindTabs = [
    { value: '', label: 'All' },
    { value: 'lesson', label: 'Lessons' },
    { value: 'talk', label: 'Talks' },
]

const setKind = (value) => {
    kind.value = value
    runSearch()
}

const sortOptions = [
    { value: 'first_published', label: 'First published' },
    { value: 'last_published', label: 'Recently published' },
    { value: 'updated', label: 'Recently updated' },
]

const runSearch = () => {
    router.get(route('lessons.index'), {
        search: search.value || undefined,
        sort: sort.value,
        kind: kind.value || undefined,
        mine: mineOnly.value ? '1' : undefined
    }, { preserveState: true, replace: true })
}

watch(sort, runSearch)

const formatDateTime = (value) => {
    if (!value) return ''
    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    })
}

const formatDate = (value) => {
    if (!value) return ''
    return new Date(value).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    })
}

const cfmDateRange = (week) => {
    if (!week?.start_date) return ''
    const start = formatDate(week.start_date)
    const end = week.end_date ? formatDate(week.end_date) : ''
    return end && end !== start ? `${start} – ${end}` : start
}
</script>

<template>
    <Head title="Lessons" />
    <AppLayout title="Lessons">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-stone-800">Lessons &amp; Talks</h2>
                <div v-if="page.props.auth.user" class="flex gap-3">
                    <Link
                        :href="route('lessons.create', { kind: 'talk' })"
                        class="rounded-lg border border-amber-600 px-4 py-2 text-sm font-medium text-amber-700 hover:bg-amber-50"
                    >
                        Write a Talk
                    </Link>
                    <Link
                        :href="route('lessons.create')"
                        class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700"
                    >
                        Create a Lesson
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <!-- Kind tabs -->
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <div class="flex gap-1 rounded-lg bg-stone-100 p-1 w-fit">
                        <button
                            v-for="tab in kindTabs"
                            :key="tab.value"
                            type="button"
                            class="rounded-md px-4 py-1.5 text-sm font-medium transition-colors"
                            :class="kind === tab.value ? 'bg-white text-stone-800 shadow-sm' : 'text-stone-500 hover:text-stone-700'"
                            @click="setKind(tab.value)"
                        >
                            {{ tab.label }}
                        </button>
                    </div>

                    <span
                        v-if="mineOnly"
                        class="inline-flex items-center gap-2 px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-sm"
                    >
                        Mine only
                        <button
                            type="button"
                            class="hover:text-amber-950 font-bold"
                            title="Show everyone's lessons and talks"
                            @click="mineOnly = false; runSearch()"
                        >
                            &times;
                        </button>
                    </span>
                </div>

                <!-- Search + sort -->
                <form @submit.prevent="runSearch" class="mb-6 flex gap-3">
                    <input
                        v-model="search"
                        type="text"
                        class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        placeholder="Search lessons..."
                    >
                    <select
                        v-model="sort"
                        class="shrink-0 rounded-lg border-stone-300 text-sm text-stone-600 focus:border-amber-500 focus:ring-amber-500"
                        aria-label="Sort lessons by"
                    >
                        <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
                </form>

                <!-- List -->
                <div v-if="lessons.data.length" class="space-y-3">
                    <Link
                        v-for="lesson in lessons.data"
                        :key="lesson.id"
                        :href="route('lessons.show', lesson.slug)"
                        class="block rounded-lg border border-stone-100 bg-white p-5 shadow-sm transition-shadow hover:shadow"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-semibold text-stone-800">{{ lesson.title }}</h3>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="lesson.kind === 'talk' ? 'bg-teal-100 text-teal-800' : 'bg-amber-100 text-amber-800'"
                                    >
                                        {{ lesson.kind === 'talk' ? 'Talk' : 'Lesson' }}
                                    </span>
                                    <!-- Status is only meaningful on lessons you can edit -->
                                    <template v-if="lesson.user_id === page.props.auth.user?.id">
                                        <span
                                            v-if="!lesson.published_at"
                                            class="rounded-full bg-stone-200 px-2.5 py-0.5 text-xs font-medium text-stone-600"
                                        >
                                            Draft
                                        </span>
                                        <template v-else>
                                            <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">
                                                Published
                                            </span>
                                            <span
                                                v-if="lesson.has_draft"
                                                class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700"
                                            >
                                                Unpublished changes
                                            </span>
                                        </template>
                                    </template>
                                </div>
                                <p v-if="lesson.description" class="mt-1 text-sm text-stone-500">
                                    {{ lesson.description }}
                                </p>
                                <p class="mt-2 text-xs text-stone-400">
                                    {{ lesson.items_count }} {{ lesson.items_count === 1 ? 'block' : 'blocks' }}
                                    <span> · Created {{ formatDateTime(lesson.created_at) }}</span>
                                    <span v-if="lesson.first_published_at"> · Published {{ formatDateTime(lesson.first_published_at) }}</span>
                                </p>
                                <p v-if="lesson.cfm_week" class="mt-1 text-xs text-amber-700">
                                    Come Follow Me · {{ lesson.cfm_week.title }}
                                    <span v-if="cfmDateRange(lesson.cfm_week)" class="text-amber-600">
                                        ({{ cfmDateRange(lesson.cfm_week) }})
                                    </span>
                                </p>
                            </div>
                        </div>
                    </Link>
                </div>

                <div v-else class="rounded-lg border-2 border-dashed border-stone-200 p-12 text-center">
                    <p class="text-stone-500">
                        {{ kind === 'talk' ? 'No talks yet.' : kind === 'lesson' ? 'No lessons yet.' : 'No lessons or talks yet.' }}
                    </p>
                    <Link
                        v-if="page.props.auth.user"
                        :href="kind === 'talk' ? route('lessons.create', { kind: 'talk' }) : route('lessons.create')"
                        class="mt-3 inline-block text-amber-600 hover:text-amber-800"
                    >
                        {{ kind === 'talk' ? 'Write your first talk →' : 'Create your first lesson →' }}
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="lessons.last_page > 1" class="mt-8 flex justify-center gap-2">
                    <Link
                        v-if="lessons.prev_page_url"
                        :href="lessons.prev_page_url"
                        class="rounded bg-stone-200 px-4 py-2 text-stone-700 hover:bg-stone-300"
                    >
                        Previous
                    </Link>
                    <span class="px-4 py-2 text-stone-600">Page {{ lessons.current_page }} of {{ lessons.last_page }}</span>
                    <Link
                        v-if="lessons.next_page_url"
                        :href="lessons.next_page_url"
                        class="rounded bg-stone-200 px-4 py-2 text-stone-700 hover:bg-stone-300"
                    >
                        Next
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
