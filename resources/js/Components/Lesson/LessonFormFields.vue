<script setup>
import { usePage } from '@inertiajs/vue3'
import VisibilitySelector from '@/Components/Story/VisibilitySelector.vue'
import LessonBuilder from '@/Components/Lesson/LessonBuilder.vue'

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    itemTypes: {
        type: Array,
        default: () => []
    },
    visibilityOptions: {
        type: Array,
        default: () => []
    },
    cfmWeeks: {
        type: Array,
        default: () => []
    },
    scriptureBooks: {
        type: Array,
        default: () => []
    },
})

const page = usePage()

const weekLabel = (week) => {
    const year = week.study_year?.year ?? week.studyYear?.year
    return [year, week.title].filter(Boolean).join(' · ')
}
</script>

<template>
    <div class="space-y-8">
        <!-- Title & description -->
        <div class="space-y-6 rounded-lg border border-stone-100 bg-white p-6 shadow">
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Lesson title</label>
                <input
                    v-model="form.title"
                    type="text"
                    required
                    class="w-full rounded-lg border-stone-300 text-lg focus:border-amber-500 focus:ring-amber-500"
                    placeholder="e.g. Faith in Jesus Christ"
                >
                <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Description (optional)</label>
                <textarea
                    v-model="form.description"
                    rows="2"
                    class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                    placeholder="A short summary of what this lesson is about..."
                ></textarea>
                <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
            </div>
        </div>

        <!-- Lesson builder -->
        <div>
            <h3 class="mb-3 text-sm font-medium text-stone-700">Lesson content</h3>
            <LessonBuilder
                :items="form.items"
                :item-types="itemTypes"
                :scripture-books="scriptureBooks"
                @update:items="form.items = $event"
            />
            <p v-if="form.errors.items" class="mt-1 text-sm text-red-600">{{ form.errors.items }}</p>
        </div>

        <!-- CFM week link -->
        <div
            v-if="page.props.userSettings?.show_lds_content"
            class="space-y-3 rounded-lg border border-amber-200 bg-amber-50 p-6 shadow"
        >
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <h3 class="font-semibold text-amber-900">Come Follow Me week (optional)</h3>
            </div>
            <p class="text-sm text-amber-800">Tie this lesson to a study week so it's easy to find later.</p>
            <select
                v-model="form.cfm_week_id"
                class="w-full rounded-lg border-amber-300 focus:border-amber-500 focus:ring-amber-500"
            >
                <option :value="null">Not tied to a week</option>
                <option v-for="week in cfmWeeks" :key="week.id" :value="week.id">
                    {{ weekLabel(week) }}
                </option>
            </select>
        </div>

        <!-- Visibility -->
        <div class="rounded-lg border border-stone-100 bg-white p-6 shadow">
            <VisibilitySelector
                v-model="form.visibility"
                :options="visibilityOptions"
            />
        </div>
    </div>
</template>
