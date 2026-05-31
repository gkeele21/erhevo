<script setup>
import { ref } from 'vue'

const props = defineProps({
    content: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['addScripture'])

const suggestions = ref([])
const loading = ref(false)
const error = ref('')
const showSuggestions = ref(false)

const fetchSuggestions = async () => {
    if (!props.content || props.content.length < 20) {
        error.value = 'Content must be at least 20 characters'
        return
    }

    loading.value = true
    error.value = ''

    try {
        const response = await fetch('/api/ai/suggest-scriptures', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie
                        .split('; ')
                        .find(row => row.startsWith('XSRF-TOKEN='))
                        ?.split('=')[1] || ''
                )
            },
            body: JSON.stringify({ content: props.content })
        })

        const data = await response.json()

        if (data.success) {
            suggestions.value = data.suggestions
            showSuggestions.value = true
        } else {
            error.value = data.error || 'Failed to get suggestions'
        }
    } catch (e) {
        error.value = 'Failed to connect to AI service'
    } finally {
        loading.value = false
    }
}

const addScripture = (suggestion) => {
    emit('addScripture', suggestion)
    // Remove from suggestions
    suggestions.value = suggestions.value.filter(s => s.reference !== suggestion.reference)
}

const closeSuggestions = () => {
    showSuggestions.value = false
    suggestions.value = []
}
</script>

<template>
    <div class="relative">
        <button
            type="button"
            @click="fetchSuggestions"
            :disabled="loading"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100 disabled:opacity-50"
            title="Find related scriptures"
        >
            <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <span>Find Scriptures</span>
        </button>

        <!-- Error message -->
        <p v-if="error" class="absolute top-full left-0 mt-1 text-xs text-red-600">
            {{ error }}
        </p>

        <!-- Suggestions panel -->
        <div
            v-if="showSuggestions"
            class="absolute z-20 top-full left-0 mt-2 p-4 bg-white border border-stone-200 rounded-lg shadow-lg w-80 max-h-96 overflow-y-auto"
        >
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-stone-700">Scripture Suggestions</span>
                <button
                    type="button"
                    @click="closeSuggestions"
                    class="text-stone-400 hover:text-stone-600"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div v-if="suggestions.length" class="space-y-3">
                <div
                    v-for="suggestion in suggestions"
                    :key="suggestion.reference"
                    class="p-3 bg-stone-50 rounded-lg"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-medium text-sm text-stone-800">
                                {{ suggestion.reference }}
                                <span
                                    v-if="!suggestion.valid"
                                    class="text-xs text-orange-600"
                                    title="Reference not found in database"
                                >
                                    (unverified)
                                </span>
                            </p>
                            <p class="text-xs text-stone-600 mt-1">{{ suggestion.reason }}</p>
                        </div>
                        <button
                            type="button"
                            @click="addScripture(suggestion)"
                            class="shrink-0 px-2 py-1 text-xs font-medium text-amber-700 bg-amber-100 rounded hover:bg-amber-200"
                        >
                            Add
                        </button>
                    </div>
                </div>
            </div>

            <p v-else class="text-sm text-stone-500 text-center py-2">
                No scripture suggestions found
            </p>
        </div>
    </div>
</template>
