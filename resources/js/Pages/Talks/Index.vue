<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    talks: Object,
    sources: Array,
    conferenceFilters: Object,
    filters: Object
})

const search = ref(props.filters?.search || '')
const selectedSource = ref(props.filters?.source || '')
const selectedYear = ref(props.filters?.year || '')
const selectedMonth = ref(props.filters?.month || '')
const selectedSession = ref(props.filters?.session || '')

const isGeneralConference = computed(() => selectedSource.value === 'general-conference')

const applyFilters = () => {
    router.get(route('talks.index'), {
        search: search.value || undefined,
        source: selectedSource.value || undefined,
        year: selectedYear.value || undefined,
        month: selectedMonth.value || undefined,
        session: selectedSession.value || undefined
    }, {
        preserveState: true,
        replace: true
    })
}

// Each level of the cascade clears the levels below it before refetching,
// so a single request goes out with a consistent set of filters.
const onSourceChange = () => {
    selectedYear.value = ''
    selectedMonth.value = ''
    selectedSession.value = ''
    applyFilters()
}

const onYearChange = () => {
    selectedMonth.value = ''
    selectedSession.value = ''
    applyFilters()
}

const onMonthChange = () => {
    selectedSession.value = ''
    applyFilters()
}

let debounceTimer = null
watch(search, () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(applyFilters, 500)
})
</script>

<template>
    <AppLayout title="Library">
        <template #header>
            <h2 class="font-semibold text-xl text-stone-800 leading-tight">
                Library
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <p class="text-stone-600 mb-6">
                    Browse talks and addresses from General Conference, BYU Speeches, and other sources.
                </p>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow p-4 mb-8 border border-stone-100">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search by title, speaker, or summary..."
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                            >
                        </div>
                        <div class="w-full md:w-64">
                            <select
                                v-model="selectedSource"
                                @change="onSourceChange"
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                            >
                                <option value="">All Sources</option>
                                <option v-for="source in sources" :key="source.id" :value="source.slug">
                                    {{ source.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- General Conference cascade: year → month → session -->
                    <div v-if="isGeneralConference" class="flex flex-col md:flex-row gap-4 mt-4">
                        <div class="w-full md:w-48">
                            <select
                                v-model="selectedYear"
                                @change="onYearChange"
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                            >
                                <option value="">All Years</option>
                                <option v-for="year in conferenceFilters.years" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                        </div>

                        <div v-if="selectedYear" class="w-full md:w-48">
                            <select
                                v-model="selectedMonth"
                                @change="onMonthChange"
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                            >
                                <option value="">All Months</option>
                                <option v-for="month in conferenceFilters.months" :key="month.value" :value="month.value">
                                    {{ month.label }}
                                </option>
                            </select>
                        </div>

                        <div v-if="selectedMonth" class="w-full md:w-64">
                            <select
                                v-model="selectedSession"
                                @change="applyFilters"
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                            >
                                <option value="">All Sessions</option>
                                <option v-for="session in conferenceFilters.sessions" :key="session.id" :value="session.id">
                                    {{ session.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Talks List -->
                <div v-if="talks.data.length" class="space-y-4">
                    <component
                        :is="talk.url ? 'a' : 'div'"
                        v-for="talk in talks.data"
                        :key="talk.id"
                        :href="talk.url || undefined"
                        :target="talk.url ? '_blank' : undefined"
                        :rel="talk.url ? 'noopener noreferrer' : undefined"
                        class="block bg-white rounded-lg shadow p-5 border border-stone-100"
                        :class="talk.url ? 'hover:shadow-md hover:border-amber-200 transition' : ''"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-stone-800">
                                    {{ talk.title }}
                                </h3>
                                <p class="text-sm text-stone-600 mt-1">
                                    {{ talk.speaker_display_name }}
                                </p>
                            </div>
                            <span v-if="talk.source" class="shrink-0 px-2 py-1 bg-amber-100 text-amber-800 rounded text-xs whitespace-nowrap">
                                {{ talk.source }}
                            </span>
                        </div>
                        <p v-if="talk.summary" class="text-sm text-stone-500 mt-3 line-clamp-3">
                            {{ talk.summary }}
                        </p>
                        <p v-if="talk.talk_date" class="text-xs text-stone-400 mt-3">
                            {{ talk.talk_date }}
                        </p>
                    </component>
                </div>

                <div v-else class="bg-white rounded-lg shadow p-12 text-center border border-stone-100">
                    <h3 class="text-lg font-semibold text-stone-800 mb-2">
                        No talks found
                    </h3>
                    <p class="text-stone-500">
                        Try adjusting your search or filters.
                    </p>
                </div>

                <!-- Pagination -->
                <div v-if="talks.last_page > 1" class="mt-8 flex justify-center gap-2">
                    <Link
                        v-if="talks.prev_page_url"
                        :href="talks.prev_page_url"
                        class="px-4 py-2 bg-stone-200 text-stone-700 rounded hover:bg-stone-300"
                    >
                        Previous
                    </Link>
                    <span class="px-4 py-2 text-stone-600">
                        Page {{ talks.current_page }} of {{ talks.last_page }}
                    </span>
                    <Link
                        v-if="talks.next_page_url"
                        :href="talks.next_page_url"
                        class="px-4 py-2 bg-stone-200 text-stone-700 rounded hover:bg-stone-300"
                    >
                        Next
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
