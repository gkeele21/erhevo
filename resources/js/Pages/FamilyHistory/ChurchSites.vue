<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import FamilyHistoryNav from '@/Components/FamilyHistory/FamilyHistoryNav.vue'

const props = defineProps({
    sites: Array,
})

const total = computed(() => new Set(props.sites.flatMap((s) => s.people.map((p) => p.fs_id))).size)
const years = (p) => (p.from === p.to ? `${p.from}` : `${p.from}–${p.to}`)
</script>

<template>
    <AppLayout title="Key Church Sites">
        <template #header>
            <h2 class="font-semibold text-xl text-stone-800 leading-tight">Key Church sites</h2>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <FamilyHistoryNav />

                <p class="mb-4 text-sm text-stone-500">
                    {{ total }} of your ancestors lived in or near a key Church site while the Church was there.
                    These are clues from dated places in your tree, including where children were born.
                </p>

                <!-- Jump links -->
                <nav class="mb-6 flex flex-wrap gap-2">
                    <a
                        v-for="site in sites"
                        :key="site.key"
                        :href="`#${site.key}`"
                        class="px-3 py-1 rounded-full text-sm"
                        :class="site.people.length ? 'bg-navy-50 text-navy hover:bg-navy-100' : 'bg-stone-100 text-stone-400'"
                    >
                        {{ site.label.split(',')[0].split(' &')[0] }} <span class="opacity-70">{{ site.people.length }}</span>
                    </a>
                </nav>

                <div class="space-y-6">
                    <section
                        v-for="site in sites"
                        :id="site.key"
                        :key="site.key"
                        class="scroll-mt-6 bg-white rounded-lg shadow border border-stone-100 p-6"
                    >
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <h3 class="font-semibold text-lg text-navy">{{ site.label }}</h3>
                            <span class="text-sm text-stone-400">{{ site.years }}</span>
                        </div>
                        <p class="mt-1 text-sm text-stone-600">{{ site.about }}</p>
                        <p class="mt-1 text-xs text-stone-400">"Near" means {{ site.near_label }}.</p>

                        <ul v-if="site.people.length" class="mt-4 divide-y divide-stone-100">
                            <li v-for="person in site.people" :key="person.fs_id" class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 py-3">
                                <div class="flex-1 min-w-0">
                                    <Link :href="route('family-history.show', person.fs_id)" class="font-medium text-navy hover:text-teal">{{ person.name }}</Link>
                                    <p class="text-xs text-stone-500">
                                        {{ person.relationship }}<span v-if="person.side"> · {{ person.side }}'s side</span>
                                    </p>
                                    <p v-if="person.where" class="text-xs text-stone-400 truncate">{{ person.where }}</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5 text-sm">
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="person.proximity === 'in' ? 'bg-navy text-white' : 'bg-navy-50 text-navy'"
                                    >
                                        {{ person.proximity === 'in' ? 'In' : 'Near' }} · {{ years(person) }}
                                    </span>
                                    <span v-if="person.baptized_while_living" class="inline-flex rounded-full bg-gold-100 text-gold-800 px-2 py-0.5 text-xs">Baptized {{ person.baptism_date }}</span>
                                    <span v-if="person.pioneer" class="inline-flex rounded-full bg-teal-50 text-teal-700 px-2 py-0.5 text-xs">Pioneer</span>
                                </div>
                            </li>
                        </ul>
                        <p v-else class="mt-4 text-sm text-stone-400">None of your ancestors found here yet.</p>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
