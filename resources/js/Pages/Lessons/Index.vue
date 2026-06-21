<script setup>
import { ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    lessons: Object,
    filters: Object,
})

const page = usePage()
const search = ref(props.filters?.search ?? '')

const runSearch = () => {
    router.get(route('lessons.index'), { search: search.value }, { preserveState: true, replace: true })
}
</script>

<template>
    <Head title="Lessons" />
    <AppLayout title="Lessons">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-stone-800">Lessons</h2>
                <Link
                    v-if="page.props.auth.user"
                    :href="route('lessons.create')"
                    class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700"
                >
                    Create a Lesson
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <!-- Search -->
                <form @submit.prevent="runSearch" class="mb-6">
                    <input
                        v-model="search"
                        type="text"
                        class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        placeholder="Search lessons..."
                    >
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
                                <h3 class="text-lg font-semibold text-stone-800">{{ lesson.title }}</h3>
                                <p v-if="lesson.description" class="mt-1 text-sm text-stone-500">
                                    {{ lesson.description }}
                                </p>
                                <p class="mt-2 text-xs text-stone-400">
                                    {{ lesson.items_count }} {{ lesson.items_count === 1 ? 'block' : 'blocks' }}
                                    <span v-if="lesson.cfm_week"> · {{ lesson.cfm_week.title }}</span>
                                    <span v-if="!lesson.published_at" class="ml-2 rounded bg-stone-100 px-2 py-0.5 text-stone-500">Draft</span>
                                </p>
                            </div>
                        </div>
                    </Link>
                </div>

                <div v-else class="rounded-lg border-2 border-dashed border-stone-200 p-12 text-center">
                    <p class="text-stone-500">No lessons yet.</p>
                    <Link
                        v-if="page.props.auth.user"
                        :href="route('lessons.create')"
                        class="mt-3 inline-block text-amber-600 hover:text-amber-800"
                    >
                        Create your first lesson →
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
