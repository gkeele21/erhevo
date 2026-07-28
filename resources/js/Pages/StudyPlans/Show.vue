<script setup>
import { computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { formatLocalDate, parseLocalDate } from '@/utils/date'

const props = defineProps({
    plan: Object
})

const sessions = computed(() => {
    const groups = new Map()
    for (const item of props.plan.items) {
        if (!groups.has(item.session_number)) {
            groups.set(item.session_number, { number: item.session_number, date: item.scheduled_date, items: [] })
        }
        groups.get(item.session_number).items.push(item)
    }
    return [...groups.values()]
})

const completedCount = computed(() => props.plan.items.filter(i => i.completed_at).length)
const progressPercent = computed(() =>
    props.plan.items.length ? Math.round((completedCount.value / props.plan.items.length) * 100) : 0
)

const today = new Date()
today.setHours(0, 0, 0, 0)

const sessionState = (session) => {
    if (session.items.every(i => i.completed_at)) return 'done'
    if (session.date && parseLocalDate(session.date) < today) return 'overdue'
    return 'pending'
}

const formatDate = (value) => formatLocalDate(value, {
    weekday: 'short', month: 'short', day: 'numeric', year: 'numeric'
})

const itemLabel = (item) => {
    if (item.chapter) return `${item.chapter.book.name} ${item.chapter.chapter_number}`
    if (item.talk) return item.talk.title
    return 'Reading'
}

const toggle = (item) => {
    router.patch(route('study-plans.items.toggle', [props.plan.id, item.id]), {}, {
        preserveScroll: true
    })
}

const destroy = () => {
    if (confirm('Delete this study plan? Your progress will be lost.')) {
        router.delete(route('study-plans.destroy', props.plan.id))
    }
}
</script>

<template>
    <AppLayout :title="plan.name">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-navy leading-tight">
                        {{ plan.name }}
                    </h2>
                    <p class="text-sm text-teal mt-0.5">{{ plan.criteria_summary }}</p>
                    <div class="flex items-center gap-3 mt-1 text-sm text-teal">
                        <span class="px-2 py-0.5 bg-navy-50 rounded-full text-xs font-medium">
                            {{ plan.type === 'scripture' ? 'Scriptures' : 'Talks' }}
                        </span>
                        <span v-if="plan.start_date">
                            {{ formatLocalDate(plan.start_date) }}
                            <template v-if="plan.end_date">
                                &ndash; {{ formatLocalDate(plan.end_date) }}
                            </template>
                        </span>
                        <span v-if="plan.frequency" class="capitalize">{{ plan.frequency }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('study-plans.edit', plan.id)"
                        class="text-sm text-teal hover:text-navy transition-colors"
                    >
                        Edit plan
                    </Link>
                    <button
                        class="text-sm text-red-400 hover:text-red-600 transition-colors"
                        @click="destroy"
                    >
                        Delete plan
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <!-- Progress -->
                <div class="bg-white rounded-lg shadow border border-navy-50 p-6 mb-8">
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-medium text-navy">Progress</span>
                        <span class="text-sm text-teal">{{ completedCount }} of {{ plan.items.length }} &middot; {{ progressPercent }}%</span>
                    </div>
                    <div class="bg-navy-50 rounded-full h-3">
                        <div
                            class="bg-teal h-3 rounded-full transition-all"
                            :style="{ width: progressPercent + '%' }"
                        />
                    </div>
                </div>

                <!-- Schedule -->
                <div class="space-y-3">
                    <div
                        v-for="session in sessions"
                        :key="session.number"
                        class="bg-white rounded-lg shadow border p-4"
                        :class="sessionState(session) === 'done' ? 'border-green-200 bg-green-50/50' : 'border-navy-50'"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium" :class="sessionState(session) === 'overdue' ? 'text-amber' : 'text-teal'">
                                <template v-if="session.date">{{ formatDate(session.date) }}</template>
                                <template v-else>Session {{ session.number }}</template>
                            </span>
                            <svg
                                v-if="sessionState(session) === 'done'"
                                class="w-5 h-5 text-green-500"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>

                        <ul class="space-y-2">
                            <li
                                v-for="item in session.items"
                                :key="item.id"
                                class="flex items-center gap-3"
                            >
                                <button
                                    type="button"
                                    class="shrink-0 w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors"
                                    :class="item.completed_at
                                        ? 'bg-green-500 border-green-500 text-white'
                                        : 'border-navy-100 hover:border-teal text-transparent'"
                                    :title="item.completed_at ? 'Mark as unread' : 'Mark as read'"
                                    @click="toggle(item)"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>

                                <div class="min-w-0">
                                    <component
                                        :is="item.talk?.url ? 'a' : 'span'"
                                        :href="item.talk?.url"
                                        :target="item.talk?.url ? '_blank' : undefined"
                                        class="font-medium text-navy"
                                        :class="[
                                            item.talk?.url ? 'hover:text-teal' : '',
                                            item.completed_at ? 'line-through text-teal-300' : ''
                                        ]"
                                    >
                                        {{ itemLabel(item) }}
                                    </component>
                                    <p v-if="item.talk" class="text-sm text-teal truncate">
                                        {{ item.talk.speaker_display_name }}
                                        <span v-if="item.talk.calling?.display_label"> &middot; {{ item.talk.calling.display_label }}</span>
                                        <span v-if="item.talk.talk_date"> &middot; {{ formatLocalDate(item.talk.talk_date, { month: 'long', year: 'numeric' }) }}</span>
                                    </p>
                                    <p v-if="item.talk?.summary" class="text-sm text-teal-300 mt-1 line-clamp-2">
                                        {{ item.talk.summary }}
                                    </p>
                                    <div v-if="item.talk?.tags?.length" class="flex flex-wrap gap-1.5 mt-1.5">
                                        <Link
                                            v-for="tag in item.talk.tags"
                                            :key="tag.id"
                                            :href="route('talks.index', { tag: tag.slug })"
                                            class="px-2 py-0.5 bg-navy-50 text-teal rounded-full text-xs hover:bg-aqua-50 hover:text-navy transition-colors"
                                        >
                                            #{{ tag.name }}
                                        </Link>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mt-8">
                    <Link
                        :href="route('study-plans.index')"
                        class="text-amber hover:text-amber-600 font-medium"
                    >
                        &larr; Back to study plans
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
