<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import FamilyHistoryNav from '@/Components/FamilyHistory/FamilyHistoryNav.vue'
import FamilyHistoryHeader from '@/Components/FamilyHistory/FamilyHistoryHeader.vue'

const props = defineProps({
    people: Array,
    sort: String,
    direction: String,
})

const columns = [
    { key: 'birth', label: 'Born' },
    { key: 'baptism', label: 'Baptized' },
]

// Clicking the active column flips direction; a new column starts oldest first.
const sortLink = (key) => route('family-history.baptized', {
    sort: key,
    direction: props.sort === key && props.direction === 'asc' ? 'desc' : 'asc',
})

const arrow = (key) => (props.sort === key ? (props.direction === 'asc' ? '↑' : '↓') : '')

const age = (p) => p.age_at_baptism ? `${p.age_at_baptism.approximate ? '~' : ''}${p.age_at_baptism.years}` : '—'
</script>

<template>
    <AppLayout title="Baptized While Living">
        <template #header>
            <FamilyHistoryHeader title="Baptized while living" />
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <FamilyHistoryNav />

                <div class="bg-white rounded-lg shadow border border-stone-100 p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <p class="text-sm text-stone-500">
                            {{ people.length }} ancestor{{ people.length === 1 ? '' : 's' }} joined the Church during their lifetime.
                        </p>
                        <!-- Sort control for phones, where the column headers are hidden. -->
                        <div class="flex gap-2 sm:hidden">
                            <Link
                                v-for="column in columns"
                                :key="column.key"
                                :href="sortLink(column.key)"
                                preserve-scroll
                                class="px-3 py-1 rounded-full text-sm"
                                :class="sort === column.key ? 'bg-navy text-white' : 'bg-stone-100 text-stone-600'"
                            >
                                {{ column.label }} {{ arrow(column.key) }}
                            </Link>
                        </div>
                    </div>

                    <table v-if="people.length" class="w-full text-sm">
                        <thead class="hidden sm:table-header-group">
                            <tr class="border-b border-stone-200 text-left text-xs font-semibold uppercase tracking-wide text-stone-400">
                                <th class="py-2 pe-4">Ancestor</th>
                                <th v-for="column in columns" :key="column.key" class="py-2 pe-4">
                                    <Link :href="sortLink(column.key)" preserve-scroll class="inline-flex items-center gap-1 hover:text-navy" :class="sort === column.key ? 'text-navy' : ''">
                                        {{ column.label }} <span class="w-3">{{ arrow(column.key) }}</span>
                                    </Link>
                                </th>
                                <th class="py-2 pe-4 text-right">Age</th>
                                <th class="py-2">Died</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-for="person in people" :key="person.fs_id" class="flex flex-col py-3 sm:table-row sm:py-0">
                                <td class="sm:py-3 sm:pe-4">
                                    <Link :href="route('family-history.show', person.fs_id)" class="font-medium text-navy hover:text-teal">{{ person.name }}</Link>
                                    <p class="text-xs text-stone-500">
                                        {{ person.relationship }}<span v-if="person.side"> · {{ person.side }}'s side</span>
                                        <span v-if="person.pioneer" class="ms-1 inline-flex rounded-full bg-teal-50 text-teal-700 px-1.5 text-xs">Pioneer</span>
                                    </p>
                                </td>
                                <td class="sm:py-3 sm:pe-4 text-stone-700">
                                    <span class="sm:hidden text-stone-400">Born </span>{{ person.birth_date || '—' }}
                                    <p v-if="person.birth_place" class="text-xs text-stone-400">{{ person.birth_place }}</p>
                                </td>
                                <td class="sm:py-3 sm:pe-4 text-gold-800 font-medium">
                                    <span class="sm:hidden text-stone-400 font-normal">Baptized </span>{{ person.baptism_date || '—' }}
                                </td>
                                <td class="sm:py-3 sm:pe-4 sm:text-right text-stone-600">
                                    <span class="sm:hidden text-stone-400">Age at baptism </span>{{ age(person) }}
                                </td>
                                <td class="sm:py-3 text-stone-600">
                                    <span class="sm:hidden text-stone-400">Died </span>{{ person.death_date || (person.death_year ?? '—') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="text-sm text-stone-500 py-4">
                        Nobody yet. Add baptism dates as you research, or import a tree exported with LDS data.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
