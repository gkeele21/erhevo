<script setup>
import { Link, router } from '@inertiajs/vue3'

// Announcements from config/whats_new.php the user hasn't seen (newest
// first). Dismissing records the newest entry's date, so the card stays
// gone until something newer ships.
const props = defineProps({
    entries: Array
})

const dismiss = () => {
    router.put(route('user-settings.update'), { whats_new_seen_through: props.entries[0].date }, {
        preserveScroll: true
    })
}

const formatDate = (date) => new Date(date + 'T00:00:00')
    .toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
</script>

<template>
    <div class="bg-white rounded-lg shadow border border-amber-200 p-6">
        <div class="flex items-start justify-between gap-4 mb-4">
            <h3 class="text-lg font-semibold text-navy">
                ✨ What's new since your last visit
            </h3>
            <div class="flex items-center gap-4">
                <Link :href="route('whats-new')" class="text-sm text-amber hover:text-amber-600 whitespace-nowrap">
                    See all updates
                </Link>
                <button
                    type="button"
                    class="text-sm text-teal-300 hover:text-teal whitespace-nowrap"
                    @click="dismiss"
                >
                    Got it
                </button>
            </div>
        </div>

        <ul class="space-y-4">
            <li v-for="entry in entries" :key="entry.date + entry.title">
                <p class="text-xs text-teal-300 mb-0.5">{{ formatDate(entry.date) }}</p>
                <p class="font-medium text-navy">{{ entry.title }}</p>
                <p class="text-sm text-teal">
                    {{ entry.body }}
                    <a
                        v-if="entry.help_anchor"
                        :href="`${route('help')}#${entry.help_anchor}`"
                        target="_blank"
                        rel="noopener"
                        class="text-amber hover:text-amber-600 font-medium whitespace-nowrap"
                    >
                        Learn more &rarr;
                    </a>
                </p>
            </li>
        </ul>
    </div>
</template>
