<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import HelpTip from '@/Components/HelpTip.vue'
import TalkCard from '@/Components/TalkCard.vue'
import AuthorFilterPicker from '@/Components/AuthorFilterPicker.vue'
import CallingWindowPicker from '@/Components/CallingWindowPicker.vue'

const props = defineProps({
    talks: Object,
    randomTalk: Object,
    sources: Array,
    conferenceFilters: Object,
    sessionTypes: Array,
    churchCallings: Array,
    conferences: Array,
    selectedAuthor: Object,
    authorCallings: Array,
    filters: Object,
    activeTag: Object,
    canEngage: Boolean
})

const search = ref(props.filters?.search || '')
const selectedSource = ref(props.filters?.source || '')
const selectedYear = ref(props.filters?.year || '')
const selectedMonth = ref(props.filters?.month || '')
const selectedSession = ref(props.filters?.session || '')
const selectedSessionType = ref(props.filters?.session_type || '')
const selectedTag = ref(props.filters?.tag || '')
const selectedSort = ref(props.filters?.sort || 'oldest')
const selectedMinRating = ref(props.filters?.min_rating || '')
const favoritesOnly = ref(Boolean(props.filters?.favorites))
const selectedAuthorId = ref(props.filters?.author_id || '')
const selectedAuthorCallings = ref(props.filters?.author_calling_ids ?? [])
const selectedChurchCalling = ref(props.filters?.church_calling_id || '')
const selectedConference = ref(props.filters?.general_conference_id || '')
const yearsBack = ref(props.filters?.years_back || '')

const isGeneralConference = computed(() => selectedSource.value === 'general-conference')

const applyFilters = () => {
    router.get(route('talks.index'), {
        search: search.value || undefined,
        source: selectedSource.value || undefined,
        year: selectedYear.value || undefined,
        month: selectedMonth.value || undefined,
        session: selectedSession.value || undefined,
        session_type: selectedSessionType.value || undefined,
        tag: selectedTag.value || undefined,
        min_rating: selectedMinRating.value || undefined,
        favorites: favoritesOnly.value ? 1 : undefined,
        author_id: selectedAuthorId.value || undefined,
        author_calling_ids: selectedAuthorCallings.value.length ? selectedAuthorCallings.value : undefined,
        church_calling_id: selectedChurchCalling.value || undefined,
        general_conference_id: selectedConference.value || undefined,
        years_back: yearsBack.value || undefined,
        sort: selectedSort.value !== 'oldest' ? selectedSort.value : undefined
    }, {
        preserveState: true,
        replace: true
    })
}

const filterByTag = (tag) => {
    selectedTag.value = tag.slug
    applyFilters()
}

const clearTag = () => {
    selectedTag.value = ''
    applyFilters()
}

// Each level of the cascade clears the levels below it before refetching,
// so a single request goes out with a consistent set of filters.
const onSourceChange = () => {
    selectedYear.value = ''
    selectedMonth.value = ''
    selectedSession.value = ''
    selectedSessionType.value = ''
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

// Picking a new author invalidates the calling window chosen for the old one.
const onAuthorSelected = (author) => {
    selectedAuthorId.value = author.id
    selectedAuthorCallings.value = []
    applyFilters()
}

const onAuthorCleared = () => {
    selectedAuthorId.value = ''
    selectedAuthorCallings.value = []
    applyFilters()
}

// Set the ref then refetch, rather than leaning on v-model's handler ordering.
const onCallingWindowsChanged = (ids) => {
    selectedAuthorCallings.value = ids
    applyFilters()
}

// Everything except the free-text search, which has its own visible input.
const activeFilterCount = computed(() => [
    selectedSource.value,
    selectedYear.value,
    selectedMonth.value,
    selectedSession.value,
    selectedSessionType.value,
    selectedTag.value,
    selectedMinRating.value,
    favoritesOnly.value,
    selectedAuthorId.value,
    selectedAuthorCallings.value.length,
    selectedChurchCalling.value,
    selectedConference.value,
    yearsBack.value
].filter(Boolean).length)

const clearAllFilters = () => {
    router.get(route('talks.index'), {}, { preserveState: false, replace: true })
}

// Re-roll just the random pick — the result list and filter options are
// already on screen and don't need to come down the wire again.
const shuffleRandomTalk = () => {
    router.reload({ only: ['randomTalk'], preserveScroll: true })
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
            <h2 class="flex items-center gap-1.5 font-semibold text-xl text-stone-800 leading-tight">
                {{ favoritesOnly ? 'Library · My Favorites' : 'Library' }}
                <HelpTip anchor="library" tip="Thousands of General Conference talks and BYU Speeches — filter, search, and click topic tags. Open Help to learn more." />
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <p class="text-stone-600 mb-6">
                    Browse talks and addresses from General Conference, BYU Speeches, and other sources.
                </p>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow p-4 mb-8 border border-stone-100">
                    <!-- With this many filters available, a way back to "everything" matters -->
                    <div v-if="activeFilterCount || search" class="flex items-center justify-between gap-4 mb-3 pb-3 border-b border-stone-100">
                        <p class="text-sm text-stone-500">
                            {{ activeFilterCount
                                ? `${activeFilterCount} filter${activeFilterCount === 1 ? '' : 's'} applied`
                                : 'Searching all talks' }}
                        </p>
                        <button
                            type="button"
                            class="shrink-0 text-sm text-amber-700 hover:text-amber-900 underline"
                            @click="clearAllFilters"
                        >
                            Clear all
                        </button>
                    </div>

                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search by title or summary..."
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
                        <div class="w-full md:w-52">
                            <select
                                v-model="selectedSort"
                                @change="applyFilters"
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                            >
                                <option value="oldest">Oldest first</option>
                                <option value="newest">Newest first</option>
                                <option value="title">Title A&ndash;Z</option>
                                <option value="speaker">Speaker A&ndash;Z</option>
                                <option value="rating">Highest rated</option>
                            </select>
                        </div>
                    </div>

                    <!-- Speaker: the same author / calling narrowing the study plan builder offers -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label class="block text-xs font-medium text-stone-500 mb-1" for="author-filter">Speaker</label>
                            <AuthorFilterPicker
                                id="author-filter"
                                :selected="selectedAuthor"
                                @select="onAuthorSelected"
                                @clear="onAuthorCleared"
                            />
                        </div>

                        <div v-if="selectedAuthor && authorCallings.length">
                            <span class="block text-xs font-medium text-stone-500 mb-1">While serving as</span>
                            <CallingWindowPicker
                                :model-value="selectedAuthorCallings"
                                :callings="authorCallings"
                                @update:model-value="onCallingWindowsChanged"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-stone-500 mb-1" for="church-calling">Calling</label>
                            <select
                                id="church-calling"
                                v-model="selectedChurchCalling"
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                                @change="applyFilters"
                            >
                                <option value="">Any calling</option>
                                <option v-for="calling in churchCallings" :key="calling.id" :value="calling.id">
                                    {{ calling.label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Timeframe: one named conference, or a recency window -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label class="block text-xs font-medium text-stone-500 mb-1" for="conference">Conference</label>
                            <select
                                id="conference"
                                v-model="selectedConference"
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                                @change="applyFilters"
                            >
                                <option value="">Any conference</option>
                                <option v-for="conference in conferences" :key="conference.id" :value="conference.id">
                                    {{ conference.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-stone-500 mb-1" for="years-back">Within the last</label>
                            <div class="flex items-center gap-2">
                                <input
                                    id="years-back"
                                    v-model="yearsBack"
                                    type="number"
                                    min="1"
                                    max="100"
                                    placeholder="Any"
                                    class="w-24 rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                                    @change="applyFilters"
                                >
                                <span class="text-sm text-stone-500">years</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-stone-500 mb-1" for="min-rating">Minimum rating</label>
                            <select
                                id="min-rating"
                                v-model="selectedMinRating"
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                                @change="applyFilters"
                            >
                                <option value="">Any rating</option>
                                <option v-for="stars in [5, 4, 3, 2, 1]" :key="stars" :value="stars">
                                    {{ stars }}{{ stars === 5 ? ' stars' : '+ stars' }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <label v-if="canEngage" class="flex items-center gap-2 mt-4 text-sm text-stone-600 w-fit">
                        <input
                            v-model="favoritesOnly"
                            type="checkbox"
                            class="rounded border-stone-300 text-amber-600 focus:ring-amber-500"
                            @change="applyFilters"
                        >
                        My favorites only
                    </label>

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

                        <div class="w-full md:w-64">
                            <select
                                v-model="selectedSessionType"
                                @change="applyFilters"
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                            >
                                <option value="">All Session Types</option>
                                <option v-for="type in sessionTypes" :key="type.id" :value="type.id">
                                    {{ type.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Active tag filter -->
                    <div v-if="activeTag" class="mt-4">
                        <span class="inline-flex items-center gap-2 px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-sm">
                            #{{ activeTag.name }}
                            <button
                                type="button"
                                class="hover:text-amber-950 font-bold"
                                title="Clear tag filter"
                                @click="clearTag"
                            >
                                &times;
                            </button>
                        </span>
                    </div>
                </div>

                <!-- Result count, so the effect of a filter is legible -->
                <p v-if="talks.total" class="text-sm text-stone-600 mb-4">
                    <span class="font-semibold text-stone-800">{{ talks.total.toLocaleString() }}</span>
                    {{ talks.total === 1 ? 'talk' : 'talks' }}
                    <span v-if="talks.last_page > 1" class="text-stone-400">
                        &middot; showing {{ talks.from.toLocaleString() }}&ndash;{{ talks.to.toLocaleString() }}
                    </span>
                </p>

                <!-- A random talk from the same filtered set, for when you
                     don't have a particular talk in mind. -->
                <div v-if="randomTalk" class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-stone-700">
                            Random pick from these results
                        </h3>
                        <button
                            type="button"
                            class="text-sm text-amber-700 hover:text-amber-900 underline"
                            @click="shuffleRandomTalk"
                        >
                            Shuffle
                        </button>
                    </div>
                    <TalkCard
                        :talk="randomTalk"
                        :can-engage="canEngage"
                        highlight
                        @filter-tag="filterByTag"
                    />
                </div>

                <!-- Keeps the random pick from reading as the first result -->
                <hr v-if="randomTalk && talks.data.length" class="mb-8 border-stone-300">

                <!-- Talks List -->
                <div v-if="talks.data.length" class="space-y-4">
                    <TalkCard
                        v-for="talk in talks.data"
                        :key="talk.id"
                        :talk="talk"
                        :can-engage="canEngage"
                        @filter-tag="filterByTag"
                    />
                </div>

                <div v-else class="bg-white rounded-lg shadow p-12 text-center border border-stone-100">
                    <h3 class="text-lg font-semibold text-stone-800 mb-2">
                        {{ favoritesOnly ? 'No favorite talks yet' : 'No talks found' }}
                    </h3>
                    <p class="text-stone-500">
                        {{ favoritesOnly
                            ? 'Favorite a talk from the library and it will show up here.'
                            : 'Try adjusting your search or filters.' }}
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
