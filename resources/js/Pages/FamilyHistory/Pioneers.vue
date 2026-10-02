<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import FamilyHistoryNav from '@/Components/FamilyHistory/FamilyHistoryNav.vue'

const props = defineProps({
    people: Array,
    sort: String,
    direction: String,
})

const columns = [
    { key: 'birth', label: 'Born' },
    { key: 'arrival', label: 'Reached Utah' },
]

// Clicking the active column flips direction; a new column starts earliest first.
const sortLink = (key) => route('family-history.pioneers', {
    sort: key,
    direction: props.sort === key && props.direction === 'asc' ? 'desc' : 'asc',
})

const arrow = (key) => (props.sort === key ? (props.direction === 'asc' ? '↑' : '↓') : '')

const confirmedCount = props.people.filter((p) => p.confirmed).length
</script>

<template>
    <AppLayout title="Pioneers">
        <template #header>
            <h2 class="font-semibold text-xl text-stone-800 leading-tight">Pioneers</h2>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <FamilyHistoryNav />

                <div class="bg-white rounded-lg shadow border border-stone-100 p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <p class="text-sm text-stone-500">
                            {{ people.length }} ancestor{{ people.length === 1 ? '' : 's' }} who likely crossed the plains to Utah before the railroad (1847–1869)<span v-if="confirmedCount">, {{ confirmedCount }} confirmed by you</span>.
                            Clues come from your tree. Confirm or rule out each one on their page.
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
                                <th class="py-2">Why we think so</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-for="person in people" :key="person.fs_id" class="flex flex-col gap-1 py-3 sm:table-row sm:py-0 align-top">
                                <td class="sm:py-3 sm:pe-4 align-top">
                                    <Link :href="route('family-history.show', person.fs_id)" class="font-medium text-navy hover:text-teal">{{ person.name }}</Link>
                                    <p class="text-xs text-stone-500">
                                        {{ person.relationship }}<span v-if="person.side"> · {{ person.side }}'s side</span>
                                    </p>
                                    <p class="mt-0.5 flex flex-wrap gap-1">
                                        <span v-if="person.confirmed" class="inline-flex rounded-full bg-teal-50 text-teal-700 px-1.5 text-xs">✓ Confirmed</span>
                                        <span v-if="person.baptized_while_living" class="inline-flex rounded-full bg-gold-100 text-gold-800 px-1.5 text-xs">Baptized {{ person.baptism_date }}</span>
                                    </p>
                                </td>
                                <td class="sm:py-3 sm:pe-4 text-stone-700 align-top">
                                    <span class="sm:hidden text-stone-400">Born </span>{{ person.birth_date || '—' }}
                                    <p v-if="person.birth_place" class="text-xs text-stone-400">{{ person.birth_place }}</p>
                                </td>
                                <td class="sm:py-3 sm:pe-4 text-stone-700 align-top">
                                    <span class="sm:hidden text-stone-400">Reached Utah </span>
                                    <template v-if="person.arrival">
                                        <span class="font-medium text-teal-700">{{ person.arrival.year }}</span>
                                        <p v-if="person.arrival.company" class="text-xs text-stone-500">{{ person.arrival.company }}</p>
                                    </template>
                                    <template v-else>—</template>
                                </td>
                                <td class="sm:py-3 text-xs text-stone-500 align-top">
                                    <ul class="space-y-0.5">
                                        <li v-for="signal in person.signals" :key="signal">{{ signal }}</li>
                                        <li v-if="!person.signals.length && person.confirmed">You marked this ancestor as a pioneer.</li>
                                    </ul>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="text-sm text-stone-500 py-4">
                        No pioneers found in your tree yet. Mark one from an ancestor's page if you know of one.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
