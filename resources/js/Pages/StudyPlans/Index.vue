<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { formatLocalDate } from '@/utils/date'

defineProps({
    plans: Array
})

const currentUserId = usePage().props.auth.user?.id

const progressPercent = (plan) => {
    if (!plan.items_count) return 0
    return Math.round((plan.completed_items_count / plan.items_count) * 100)
}

const typeLabel = (plan) => plan.type === 'scripture' ? 'Scriptures' : 'Talks'
</script>

<template>
    <AppLayout title="Study Plans">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-navy leading-tight">
                    Study Plans
                </h2>
                <Link
                    :href="route('study-plans.create')"
                    class="px-4 py-2 bg-amber text-white rounded-lg hover:bg-amber-600"
                >
                    New Study Plan
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div v-if="plans?.length" class="space-y-4">
                    <Link
                        v-for="plan in plans"
                        :key="plan.id"
                        :href="route('study-plans.show', plan.id)"
                        class="block bg-white rounded-lg shadow p-6 border border-navy-50 hover:shadow-lg transition-shadow"
                    >
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="text-lg font-semibold text-navy">{{ plan.name }}</h3>
                                <p class="text-sm text-teal mt-0.5">{{ plan.criteria_summary }}</p>
                                <div class="flex items-center gap-3 mt-1 text-sm text-teal">
                                    <span class="px-2 py-0.5 bg-navy-50 rounded-full text-xs font-medium">
                                        {{ typeLabel(plan) }}
                                    </span>
                                    <span
                                        v-if="plan.is_new"
                                        class="px-2 py-0.5 bg-amber text-white rounded-full text-xs font-semibold"
                                    >
                                        New
                                    </span>
                                    <span
                                        v-if="plan.user_id !== currentUserId"
                                        class="px-2 py-0.5 bg-aqua-50 text-navy rounded-full text-xs font-medium"
                                    >
                                        Shared by {{ plan.user?.name }}
                                    </span>
                                    <span
                                        v-else-if="plan.members_count > 0"
                                        class="px-2 py-0.5 bg-aqua-50 text-navy rounded-full text-xs font-medium"
                                    >
                                        Studying with {{ plan.members_count }} {{ plan.members_count === 1 ? 'friend' : 'friends' }}
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
                            <span
                                v-if="plan.items_count && plan.completed_items_count === plan.items_count"
                                class="inline-flex items-center gap-1 text-sm font-medium text-green-600"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                Completed
                            </span>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex-1 bg-navy-50 rounded-full h-2.5">
                                <div
                                    class="bg-teal h-2.5 rounded-full transition-all"
                                    :style="{ width: progressPercent(plan) + '%' }"
                                />
                            </div>
                            <span class="text-sm text-teal whitespace-nowrap">
                                {{ plan.completed_items_count }} / {{ plan.items_count }}
                            </span>
                        </div>
                    </Link>
                </div>

                <div v-else class="bg-white rounded-lg shadow p-12 text-center border border-navy-50">
                    <h3 class="text-lg font-semibold text-navy mb-2">No study plans yet</h3>
                    <p class="text-teal mb-6">
                        Set up a reading schedule for scriptures or conference talks and track your progress.
                    </p>
                    <Link
                        :href="route('study-plans.create')"
                        class="px-4 py-2 bg-amber text-white rounded-lg hover:bg-amber-600"
                    >
                        Create your first study plan
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
