<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    },
    cfmWeeks: {
        type: Array,
        default: () => []
    },
    currentCfmWeek: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['update:modelValue'])

const isOpen = ref(false)
const searchQuery = ref('')

const selectedIds = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

const groupedWeeks = computed(() => {
    const groups = {}
    props.cfmWeeks.forEach(week => {
        const yearKey = week.study_year?.year || 'Unknown'
        if (!groups[yearKey]) {
            groups[yearKey] = {
                year: yearKey,
                title: week.study_year?.title || '',
                weeks: []
            }
        }
        groups[yearKey].weeks.push(week)
    })
    return Object.values(groups).sort((a, b) => b.year - a.year)
})

const filteredGroupedWeeks = computed(() => {
    if (!searchQuery.value) return groupedWeeks.value

    const query = searchQuery.value.toLowerCase()
    return groupedWeeks.value.map(group => ({
        ...group,
        weeks: group.weeks.filter(week =>
            week.title?.toLowerCase().includes(query) ||
            `week ${week.week_number}`.includes(query)
        )
    })).filter(group => group.weeks.length > 0)
})

const selectedWeeks = computed(() => {
    return props.cfmWeeks.filter(week => selectedIds.value.includes(week.id))
})

const toggleWeek = (weekId) => {
    const newValue = [...selectedIds.value]
    const index = newValue.indexOf(weekId)
    if (index === -1) {
        newValue.push(weekId)
    } else {
        newValue.splice(index, 1)
    }
    selectedIds.value = newValue
}

const addCurrentWeek = () => {
    if (props.currentCfmWeek && !selectedIds.value.includes(props.currentCfmWeek.id)) {
        selectedIds.value = [...selectedIds.value, props.currentCfmWeek.id]
    }
}

const removeWeek = (weekId) => {
    selectedIds.value = selectedIds.value.filter(id => id !== weekId)
}

const formatDateRange = (week) => {
    if (!week.start_date || !week.end_date) return ''
    const start = new Date(week.start_date)
    const end = new Date(week.end_date)
    const options = { month: 'short', day: 'numeric' }
    return `${start.toLocaleDateString('en-US', options)} - ${end.toLocaleDateString('en-US', options)}`
}

const isCurrentWeek = (week) => {
    return props.currentCfmWeek?.id === week.id
}
</script>

<template>
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <label class="block text-sm font-medium text-stone-700">
                Come Follow Me Weeks
            </label>
            <button
                v-if="currentCfmWeek && !selectedIds.includes(currentCfmWeek.id)"
                type="button"
                @click="addCurrentWeek"
                class="text-xs text-amber-600 hover:text-amber-700 font-medium flex items-center gap-1"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add Current Week
            </button>
        </div>

        <!-- Selected Weeks Pills -->
        <div v-if="selectedWeeks.length > 0" class="flex flex-wrap gap-2">
            <span
                v-for="week in selectedWeeks"
                :key="week.id"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-sm"
                :class="isCurrentWeek(week) ? 'bg-amber-100 text-amber-800' : 'bg-stone-100 text-stone-700'"
            >
                <span class="font-medium">Week {{ week.week_number }}</span>
                <span class="text-stone-500">{{ week.study_year?.year }}</span>
                <button
                    type="button"
                    @click="removeWeek(week.id)"
                    class="ml-1 hover:text-red-600"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </span>
        </div>

        <!-- Week Selector Dropdown -->
        <div class="relative">
            <button
                type="button"
                @click="isOpen = !isOpen"
                class="w-full px-4 py-2.5 text-left rounded-lg border border-stone-300 bg-white hover:border-stone-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
            >
                <span class="text-stone-500">
                    {{ selectedWeeks.length > 0 ? `${selectedWeeks.length} week(s) selected` : 'Select CFM weeks...' }}
                </span>
                <svg
                    class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-stone-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- Dropdown Panel -->
            <div
                v-show="isOpen"
                class="absolute z-10 mt-1 w-full bg-white rounded-lg shadow-lg border border-stone-200 max-h-72 overflow-y-auto"
            >
                <!-- Search -->
                <div class="sticky top-0 bg-white p-2 border-b border-stone-100">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search weeks..."
                        class="w-full px-3 py-2 text-sm rounded-md border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                    />
                </div>

                <!-- Grouped Weeks -->
                <div v-for="group in filteredGroupedWeeks" :key="group.year" class="py-1">
                    <div class="px-3 py-1.5 text-xs font-semibold text-stone-500 uppercase tracking-wide bg-stone-50">
                        {{ group.year }} - {{ group.title }}
                    </div>
                    <div
                        v-for="week in group.weeks"
                        :key="week.id"
                        @click="toggleWeek(week.id)"
                        class="px-3 py-2 cursor-pointer hover:bg-stone-50 flex items-center justify-between"
                        :class="{ 'bg-amber-50': selectedIds.includes(week.id) }"
                    >
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-stone-900">Week {{ week.week_number }}</span>
                                <span
                                    v-if="isCurrentWeek(week)"
                                    class="text-xs px-1.5 py-0.5 bg-amber-100 text-amber-700 rounded"
                                >
                                    Current
                                </span>
                            </div>
                            <div class="text-sm text-stone-600 truncate">{{ week.title }}</div>
                            <div class="text-xs text-stone-400">{{ formatDateRange(week) }}</div>
                        </div>
                        <svg
                            v-if="selectedIds.includes(week.id)"
                            class="w-5 h-5 text-amber-600 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>

                <div v-if="filteredGroupedWeeks.length === 0" class="px-3 py-4 text-center text-stone-500 text-sm">
                    No weeks found
                </div>
            </div>
        </div>

        <!-- Click outside to close -->
        <div v-if="isOpen" class="fixed inset-0 z-0" @click="isOpen = false"></div>
    </div>
</template>
