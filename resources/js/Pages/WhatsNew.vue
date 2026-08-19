<script setup>
// The archive behind the dashboard's "What's new" card: every entry from
// config/whats_new.php, grouped by ship date. Add features there and they
// appear here automatically — see docs/HELP_CONTENT.md.
import { computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    releases: Array,
    latestDate: String
})

const page = usePage()
const user = page.props.auth?.user

const newCount = computed(() => props.releases
    .filter(release => release.is_new)
    .reduce((count, release) => count + release.entries.length, 0))

// Records the newest date, the same way the dashboard card's "Got it" does,
// so both places agree on what this user has already seen.
const markAllRead = () => {
    router.put(route('user-settings.update'), { whats_new_seen_through: props.latestDate }, {
        preserveScroll: true
    })
}

const formatDate = (date) => new Date(date + 'T00:00:00')
    .toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
</script>

<template>
    <AppLayout title="What's New">
        <template #header>
            <h2 class="font-semibold text-xl text-navy leading-tight">
                ✨ What's New
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="px-4 sm:px-0 mb-8 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <p class="text-teal">
                        Everything we've added to Erhevo, newest first. Want the full details on how
                        a feature works? Each one links into
                        <Link :href="route('help')" class="text-amber hover:text-amber-600 font-medium">Help &amp; Training</Link>.
                    </p>
                    <button
                        v-if="user && newCount"
                        type="button"
                        class="shrink-0 text-sm text-teal-300 hover:text-teal whitespace-nowrap"
                        @click="markAllRead"
                    >
                        Mark all as read
                    </button>
                </div>

                <div v-if="releases.length" class="space-y-10">
                    <section v-for="release in releases" :key="release.date">
                        <div class="flex items-center gap-3 mb-3 px-4 sm:px-0">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-teal-300">
                                {{ formatDate(release.date) }}
                            </h3>
                            <span
                                v-if="release.is_new"
                                class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-900"
                            >
                                New to you
                            </span>
                        </div>

                        <div class="space-y-4">
                            <article
                                v-for="entry in release.entries"
                                :key="entry.title"
                                class="bg-white rounded-lg shadow p-6"
                                :class="release.is_new ? 'border border-amber-200' : 'border border-navy-50'"
                            >
                                <h4 class="text-lg font-semibold text-navy mb-2">{{ entry.title }}</h4>
                                <p class="text-teal">
                                    {{ entry.body }}
                                    <a
                                        v-if="entry.help_anchor"
                                        :href="`${route('help')}#${entry.help_anchor}`"
                                        class="text-amber hover:text-amber-600 font-medium whitespace-nowrap"
                                    >
                                        Learn more &rarr;
                                    </a>
                                </p>
                            </article>
                        </div>
                    </section>
                </div>

                <p v-else class="bg-white rounded-lg shadow border border-navy-50 p-6 text-teal">
                    Nothing to announce just yet — check back after the next update.
                </p>

                <p v-if="!user" class="mt-10 text-center text-sm text-teal-300">
                    <Link :href="route('register')" class="text-amber hover:text-amber-600 font-medium">Create an account</Link>
                    to use these features, or take the
                    <Link :href="route('guide')" class="text-amber hover:text-amber-600 font-medium">quick tour</Link> first.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
