<script setup>
import { computed, ref, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import HelpTip from '@/Components/HelpTip.vue'
import TempleNav from '@/Components/Temples/TempleNav.vue'
import TempleCard from '@/Components/Temples/TempleCard.vue'
import TempleMap from '@/Components/Temples/TempleMap.vue'
import StarIcon from '@/Components/Temples/StarIcon.vue'

const props = defineProps({
    temples: Array,
    visitedTempleIds: Array,
    favoriteTempleIds: Array,
    filters: Object,
})

const search = ref(props.filters?.q || '')
const selectedCountry = ref(props.filters?.country || '')
const selectedState = ref(props.filters?.state || '')
const view = ref(props.filters?.view === 'map' ? 'map' : 'list')

const visited = new Set(props.visitedTempleIds)

// Recomputed (unlike `visited`) because starring updates the prop in place.
const favorites = computed(() => new Set(props.favoriteTempleIds))

const countries = computed(() =>
    [...new Set(props.temples.map((t) => t.country))].sort()
)

const states = computed(() =>
    [...new Set(
        props.temples
            .filter((t) => !selectedCountry.value || t.country === selectedCountry.value)
            .map((t) => t.state)
            .filter(Boolean)
    )].sort()
)

const onCountryChange = () => {
    selectedState.value = ''
}

// All filtering is client-side (the full ~220-temple list is already
// loaded); the URL is kept in sync for shareable links without refetching.
const filteredTemples = computed(() =>
    props.temples
        .filter((t) => !selectedCountry.value || t.country === selectedCountry.value)
        .filter((t) => !selectedState.value || t.state === selectedState.value)
        .filter((t) => !search.value || t.name.toLowerCase().includes(search.value.toLowerCase()))
        .map((t) => ({ ...t, visited: visited.has(t.id), favorite: favorites.value.has(t.id) }))
)

const favoriteTemples = computed(() => filteredTemples.value.filter((t) => t.favorite))
const otherTemples = computed(() => filteredTemples.value.filter((t) => !t.favorite))

const toggleFavorite = (temple) => {
    router.post(
        route('temples.favorite', temple.slug),
        {},
        { preserveScroll: true, preserveState: true, only: ['favoriteTempleIds'] }
    )
}

watch([search, selectedCountry, selectedState, view], () => {
    const params = new URLSearchParams()
    if (search.value) params.set('q', search.value)
    if (selectedCountry.value) params.set('country', selectedCountry.value)
    if (selectedState.value) params.set('state', selectedState.value)
    if (view.value !== 'list') params.set('view', view.value)
    const query = params.toString()
    window.history.replaceState({}, '', route('temples.index') + (query ? `?${query}` : ''))
})

const visitedShown = computed(() => filteredTemples.value.filter((t) => t.visited).length)
</script>

<template>
    <AppLayout title="Temples">
        <template #header>
            <h2 class="flex items-center gap-1.5 font-semibold text-xl text-stone-800 leading-tight">
                Temple Tracker
                <HelpTip anchor="temple-tracker" tip="Browse every dedicated temple, log your visits, and plan trips. Open Help to learn more." />
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <TempleNav />

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow p-4 mb-6 border border-stone-100">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search temples by name..."
                                class="w-full rounded-lg border-stone-300 focus:border-teal-500 focus:ring-teal-500"
                            >
                        </div>
                        <div class="w-full md:w-56">
                            <select
                                v-model="selectedCountry"
                                @change="onCountryChange"
                                class="w-full rounded-lg border-stone-300 focus:border-teal-500 focus:ring-teal-500"
                            >
                                <option value="">All Countries</option>
                                <option v-for="country in countries" :key="country" :value="country">
                                    {{ country }}
                                </option>
                            </select>
                        </div>
                        <div class="w-full md:w-56">
                            <select
                                v-model="selectedState"
                                :disabled="!states.length"
                                class="w-full rounded-lg border-stone-300 focus:border-teal-500 focus:ring-teal-500 disabled:opacity-50"
                            >
                                <option value="">All States/Provinces</option>
                                <option v-for="state in states" :key="state" :value="state">
                                    {{ state }}
                                </option>
                            </select>
                        </div>
                        <div class="shrink-0 inline-flex rounded-lg border border-stone-200 overflow-hidden self-start">
                            <button
                                type="button"
                                class="px-4 py-2 text-sm font-medium"
                                :class="view === 'list' ? 'bg-teal-600 text-white' : 'bg-white text-stone-600 hover:bg-stone-50'"
                                @click="view = 'list'"
                            >
                                List
                            </button>
                            <button
                                type="button"
                                class="px-4 py-2 text-sm font-medium"
                                :class="view === 'map' ? 'bg-teal-600 text-white' : 'bg-white text-stone-600 hover:bg-stone-50'"
                                @click="view = 'map'"
                            >
                                Map
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-stone-400 mt-3">
                        {{ filteredTemples.length }} temple{{ filteredTemples.length === 1 ? '' : 's' }}
                        <span v-if="visitedShown"> · {{ visitedShown }} visited</span>
                        <span v-if="favoriteTemples.length"> · {{ favoriteTemples.length }} favorite{{ favoriteTemples.length === 1 ? '' : 's' }}</span>
                    </p>
                </div>

                <!-- Map view -->
                <div v-if="view === 'map'">
                    <TempleMap :temples="filteredTemples" height-class="h-[36rem]" />
                    <p class="text-xs text-stone-400 mt-2">
                        <span class="inline-block w-3 h-3 rounded-full bg-teal-500 border border-teal-700 align-middle"></span>
                        Visited
                        <span class="inline-block w-3 h-3 rounded-full bg-white border-2 border-navy-600 align-middle ms-3"></span>
                        Not yet visited
                        <span class="inline-block w-3 h-3 rounded-full bg-white border-2 border-gold-600 align-middle ms-3"></span>
                        Favorite
                    </p>
                </div>

                <!-- List view: favorites first, then everything else -->
                <template v-else-if="filteredTemples.length">
                    <section v-if="favoriteTemples.length" class="mb-8">
                        <h3 class="flex items-center gap-1.5 text-sm font-semibold text-stone-600 uppercase tracking-wide mb-3">
                            <StarIcon filled class="w-4 h-4 text-gold-600" />
                            Favorites
                            <span class="font-normal normal-case tracking-normal text-stone-400">({{ favoriteTemples.length }})</span>
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <TempleCard
                                v-for="temple in favoriteTemples"
                                :key="temple.id"
                                :temple="temple"
                                :visited="temple.visited"
                                favorite
                                @toggle-favorite="toggleFavorite"
                            />
                        </div>
                    </section>

                    <section v-if="otherTemples.length">
                        <h3 v-if="favoriteTemples.length" class="text-sm font-semibold text-stone-600 uppercase tracking-wide mb-3">
                            All temples
                            <span class="font-normal normal-case tracking-normal text-stone-400">({{ otherTemples.length }})</span>
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <TempleCard
                                v-for="temple in otherTemples"
                                :key="temple.id"
                                :temple="temple"
                                :visited="temple.visited"
                                @toggle-favorite="toggleFavorite"
                            />
                        </div>
                    </section>
                </template>

                <div v-else class="bg-white rounded-lg shadow p-12 text-center border border-stone-100">
                    <h3 class="text-lg font-semibold text-stone-800 mb-2">No temples found</h3>
                    <p class="text-stone-500">Try adjusting your search or filters.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
