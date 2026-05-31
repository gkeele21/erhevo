<script setup>
import { ref } from 'vue'

const props = defineProps({
    content: {
        type: String,
        default: ''
    },
    existingTags: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['addTag'])

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
        const response = await fetch('/api/ai/suggest-tags', {
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
            // Filter out tags that are already added
            suggestions.value = data.tags.filter(
                tag => !props.existingTags.includes(tag.toLowerCase())
            )
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

const addTag = (tag) => {
    emit('addTag', tag)
    // Remove from suggestions
    suggestions.value = suggestions.value.filter(t => t !== tag)
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
            class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-md hover:bg-amber-100 disabled:opacity-50"
            title="Suggest tags with AI"
        >
            <svg v-if="loading" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
            </svg>
            <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
            </svg>
            <span>Suggest</span>
        </button>

        <!-- Error message -->
        <p v-if="error" class="absolute top-full left-0 mt-1 text-xs text-red-600">
            {{ error }}
        </p>

        <!-- Suggestions popup -->
        <div
            v-if="showSuggestions && suggestions.length"
            class="absolute z-20 top-full left-0 mt-2 p-3 bg-white border border-stone-200 rounded-lg shadow-lg min-w-[200px]"
        >
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-stone-500">AI Suggestions</span>
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
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="tag in suggestions"
                    :key="tag"
                    type="button"
                    @click="addTag(tag)"
                    class="px-2 py-1 text-xs bg-stone-100 text-stone-700 rounded hover:bg-amber-100 hover:text-amber-800 transition-colors"
                >
                    + {{ tag }}
                </button>
            </div>
        </div>

        <!-- No suggestions message -->
        <div
            v-if="showSuggestions && !suggestions.length && !loading"
            class="absolute z-20 top-full left-0 mt-2 p-3 bg-white border border-stone-200 rounded-lg shadow-lg"
        >
            <p class="text-xs text-stone-500">No additional tags suggested</p>
        </div>
    </div>
</template>
