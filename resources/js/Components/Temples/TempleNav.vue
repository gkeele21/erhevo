<script setup>
import { Link } from '@inertiajs/vue3'

const tabs = [
    { label: 'Temples', route: 'temples.index', current: ['temples.index', 'temples.show'] },
    { label: 'Explore', route: 'temples.explore', current: ['temples.explore'] },
    { label: 'My Visits', route: 'temple-visits.index', current: ['temple-visits.index'] },
    { label: 'Trips', route: 'temple-trips.index', current: ['temple-trips.*'] },
]

const isActive = (tab) => tab.current.some((name) => route().current(name))
</script>

<template>
    <div class="flex flex-wrap gap-2 mb-6">
        <Link
            v-for="tab in tabs"
            :key="tab.route"
            :href="route(tab.route)"
            class="px-3 py-1.5 rounded-full text-sm font-medium transition-colors"
            :class="isActive(tab)
                ? 'bg-teal-600 text-white'
                : 'bg-white text-stone-600 border border-stone-200 hover:bg-stone-50'"
        >
            {{ tab.label }}
        </Link>
    </div>
</template>
