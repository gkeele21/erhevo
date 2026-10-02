<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import HelpTip from '@/Components/HelpTip.vue'
import FamilyHistoryNav from '@/Components/FamilyHistory/FamilyHistoryNav.vue'
import AncestorBadges from '@/Components/FamilyHistory/AncestorBadges.vue'

const props = defineProps({
    tree: Object,
    ancestors: Object,
    filters: Object,
    stats: Object,
    firstBaptized: Object,
})

const filterOptions = [
    { value: 'all', label: 'All', count: () => props.stats.total },
    { value: 'unresearched', label: 'Not yet researched', count: () => props.stats.total - props.stats.researched },
    { value: 'researched', label: 'Researched', count: () => props.stats.researched },
    { value: 'baptized', label: 'Baptized while living', count: () => props.stats.baptized },
    { value: 'pioneers', label: 'Pioneers', count: () => props.stats.pioneers },
    { value: 'church_places', label: 'Church history places', count: () => props.stats.church_places },
]

const search = ref(props.filters.q)
const filter = ref(props.filters.filter)
const sort = ref(props.filters.sort)

const apply = () => {
    router.get(route('family-history.index'), {
        q: search.value || undefined,
        filter: filter.value !== 'all' ? filter.value : undefined,
        sort: sort.value !== 'generation' ? sort.value : undefined,
    }, { preserveState: true, preserveScroll: true, replace: true })
}

let debounce = null
watch(search, () => {
    clearTimeout(debounce)
    debounce = setTimeout(apply, 400)
})
watch([filter, sort], apply)

const lifespan = (p) => (p.birth_year || p.death_year) ? `${p.birth_year ?? '?'}–${p.death_year ?? ''}` : ''
const percent = (n) => props.stats.total ? Math.round((n / props.stats.total) * 100) : 0
</script>

<template>
    <AppLayout title="Family History">
        <template #header>
            <h2 class="flex items-center gap-1.5 font-semibold text-xl text-stone-800 leading-tight">
                Family History
                <HelpTip anchor="family-history" tip="Your direct-line ancestors, imported from FamilySearch. Open Help to learn more." />
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <FamilyHistoryNav>
                    <Link
                        :href="route('family-history.random', { filter: filter !== 'all' ? filter : undefined })"
                        class="ms-auto px-4 py-2 bg-amber text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors"
                    >
                        Surprise me
                    </Link>
                </FamilyHistoryNav>

                <!-- Progress + highlights -->
                <div class="grid gap-4 sm:grid-cols-3 mb-6">
                    <div class="bg-white rounded-lg shadow border border-stone-100 p-5">
                        <p class="text-sm text-stone-500">Researched</p>
                        <p class="text-2xl font-semibold text-navy">
                            {{ stats.researched.toLocaleString() }}
                            <span class="text-base font-normal text-stone-400">of {{ stats.total.toLocaleString() }}</span>
                        </p>
                        <div class="mt-2 h-1.5 rounded-full bg-stone-100 overflow-hidden">
                            <div class="h-full bg-teal-400" :style="{ width: `${Math.max(percent(stats.researched), stats.researched ? 1 : 0)}%` }" />
                        </div>
                        <p class="mt-2 text-xs text-stone-400">{{ tree.generation_count }} generations back from {{ tree.root_name }}</p>
                    </div>

                    <div class="bg-white rounded-lg shadow border border-stone-100 p-5">
                        <p class="text-sm text-stone-500">First baptized while living</p>
                        <template v-if="firstBaptized">
                            <Link :href="route('family-history.show', firstBaptized.fs_id)" class="block text-lg font-semibold text-navy hover:text-teal">
                                {{ firstBaptized.name }}
                            </Link>
                            <p class="text-sm text-stone-600">{{ firstBaptized.baptism_date }} · {{ firstBaptized.relationship }}</p>
                        </template>
                        <p v-else class="text-sm text-stone-500 mt-1">No baptism dates yet.</p>
                        <Link
                            v-if="stats.baptized"
                            :href="route('family-history.baptized')"
                            class="mt-2 inline-block text-sm font-medium text-teal hover:text-navy"
                        >
                            See all {{ stats.baptized }} baptized while living →
                        </Link>
                        <p class="mt-2 text-xs text-stone-400">
                            From the baptism dates in your tree, plus any you add while researching.
                        </p>
                    </div>

                    <div class="bg-white rounded-lg shadow border border-stone-100 p-5">
                        <p class="text-sm text-stone-500">Church history</p>
                        <p class="text-sm text-stone-700 mt-1">
                            <Link :href="route('family-history.pioneers')" class="font-semibold text-teal hover:text-navy">{{ stats.pioneers }} pioneers →</Link>
                        </p>
                        <ul class="mt-1 space-y-0.5 text-sm text-stone-600">
                            <li v-for="place in stats.places" :key="place.key">
                                <Link :href="`${route('family-history.church-sites')}#${place.key}`" class="hover:text-navy">
                                    {{ place.count }} in or near {{ place.label }}
                                </Link>{{ ' ' }}<span class="text-stone-400">({{ place.years }})</span>
                            </li>
                        </ul>
                        <Link :href="route('family-history.church-sites')" class="mt-2 inline-block text-sm font-medium text-teal hover:text-navy">
                            Key Church sites →
                        </Link>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow border border-stone-100 p-6">
                    <div class="flex flex-col sm:flex-row gap-3 mb-4">
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search by name"
                            class="flex-1 rounded-md border-stone-300 text-sm focus:border-teal focus:ring-teal"
                        />
                        <select v-model="sort" class="rounded-md border-stone-300 text-sm focus:border-teal focus:ring-teal">
                            <option value="generation">Closest first</option>
                            <option value="name">Surname</option>
                            <option value="birth">Birth year</option>
                            <option value="baptism">Baptism date</option>
                        </select>
                    </div>

                    <div class="flex flex-wrap gap-2 mb-4">
                        <button
                            v-for="option in filterOptions"
                            :key="option.value"
                            type="button"
                            class="px-3 py-1 rounded-full text-sm transition-colors"
                            :class="filter === option.value ? 'bg-navy text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200'"
                            @click="filter = option.value"
                        >
                            {{ option.label }} <span class="opacity-70">{{ option.count().toLocaleString() }}</span>
                        </button>
                    </div>

                    <ul v-if="ancestors.data.length" class="divide-y divide-stone-100">
                        <li v-for="person in ancestors.data" :key="person.fs_id">
                            <Link :href="route('family-history.show', person.fs_id)" class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 py-3 hover:bg-stone-50 -mx-2 px-2 rounded">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-navy truncate">
                                        {{ person.name }}
                                        <span class="text-sm font-normal text-stone-400">{{ lifespan(person) }}</span>
                                    </p>
                                    <p class="text-sm text-stone-500 truncate">
                                        {{ person.relationship }}<span v-if="person.side"> · {{ person.side }}'s side</span>
                                        <span v-if="person.birth_place"> · {{ person.birth_place }}</span>
                                    </p>
                                </div>
                                <AncestorBadges :person="person" compact />
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="text-stone-500 text-sm py-4">No ancestors match.</p>

                    <div v-if="ancestors.links?.length > 3" class="mt-6 flex flex-wrap gap-1">
                        <component
                            :is="link.url ? Link : 'span'"
                            v-for="link in ancestors.links"
                            :key="link.label"
                            :href="link.url"
                            v-html="link.label"
                            class="rounded px-3 py-1 text-sm"
                            :class="link.active ? 'bg-amber-600 text-white' : (link.url ? 'text-stone-600 hover:bg-stone-100' : 'text-stone-300')"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
