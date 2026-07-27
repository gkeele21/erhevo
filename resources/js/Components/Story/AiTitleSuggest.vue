<script setup>
import { ref } from 'vue'
import { useAi } from '@/Composables/useAi'

const props = defineProps({
    content: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['selectTitle'])

const suggestions = ref([])
const loading = ref(false)
const error = ref('')
const showSuggestions = ref(false)

const { aiConnected, ensureConnected } = useAi()

const fetchSuggestions = async () => {
    if (!ensureConnected(error)) return

    if (!props.content || props.content.length < 20) {
        error.value = 'Content must be at least 20 characters'
        return
    }

    loading.value = true
    error.value = ''

    try {
        const response = await fetch('/api/ai/suggest-titles', {
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
            body: JSON.stringify({
                content: props.content,
            })
        })

        const data = await response.json()

        if (data.success && data.titles?.length) {
            suggestions.value = data.titles
            showSuggestions.value = true
        } else {
            error.value = data.error || 'No suggestions available'
        }
    } catch (e) {
        error.value = 'Failed to connect to AI service'
    } finally {
        loading.value = false
    }
}

const applySuggestion = (title) => {
    emit('selectTitle', title)
    showSuggestions.value = false
    suggestions.value = []
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
            :class="{ 'opacity-60': !aiConnected }"
            :title="aiConnected ? 'Suggest titles with AI' : 'Connect an AI account in Profile settings to use AI features'"
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
        <p v-if="error" class="absolute z-20 top-full right-0 mt-2 w-max max-w-xs rounded-lg border border-red-200 bg-white p-2 text-xs text-red-600 shadow-lg">
            {{ error }}
        </p>

        <!-- Suggestions popup -->
        <div
            v-if="showSuggestions && suggestions.length"
            class="absolute z-20 top-full right-0 mt-2 p-3 bg-white border border-stone-200 rounded-lg shadow-lg w-72"
        >
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-stone-500">AI Title Suggestions</span>
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

            <div class="space-y-1">
                <button
                    v-for="(title, index) in suggestions"
                    :key="index"
                    type="button"
                    @click="applySuggestion(title)"
                    class="w-full p-2 text-left text-sm text-stone-800 bg-stone-50 rounded hover:bg-amber-50 hover:text-amber-900"
                >
                    {{ title }}
                </button>
            </div>
        </div>
    </div>
</template>
