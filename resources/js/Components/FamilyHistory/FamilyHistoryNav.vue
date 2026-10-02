<script setup>
import { Link } from '@inertiajs/vue3'

const tabs = [
    { label: 'Ancestors', route: 'family-history.index', current: ['family-history.index', 'family-history.show'] },
    { label: 'Pedigree', route: 'family-history.pedigree', current: ['family-history.pedigree'] },
    { label: 'Import', route: 'family-history.import', current: ['family-history.import'] },
]

const isActive = (tab) => tab.current.some((name) => route().current(name))
</script>

<template>
    <div class="flex flex-wrap items-center gap-2 mb-6">
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
        <slot />
    </div>
</template>
