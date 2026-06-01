<script setup>
import { ref, computed } from 'vue'
import { useAi } from '@/Composables/useAi'

const props = defineProps({
    content: {
        type: String,
        default: ''
    },
    existingCategories: {
        type: Array,
        default: () => []
    },
    type: {
        type: String,
        default: 'user', // 'user' or 'public'
        validator: (v) => ['user', 'public'].includes(v)
    }
})

const emit = defineEmits(['selectCategory'])

const suggestion = ref(null)
const loading = ref(false)
const error = ref('')
const showSuggestion = ref(false)

// Flatten category names for the API
const categoryNames = computed(() => {
    const names = []
    props.existingCategories.forEach(cat => {
        names.push(cat.name)
        if (cat.children) {
            cat.children.forEach(child => names.push(child.name))
        }
    })
    return names
})

const { aiConnected, ensureConnected } = useAi()

const fetchSuggestion = async () => {
    if (!ensureConnected(error)) return

    if (!props.content || props.content.length < 20) {
        error.value = 'Content must be at least 20 characters'
        return
    }

    loading.value = true
    error.value = ''

    try {
        const response = await fetch('/api/ai/suggest-category', {
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
                type: props.type,
                existing_categories: categoryNames.value,
            })
        })

        const data = await response.json()

        if (data.success && data.suggestion?.name) {
            suggestion.value = data.suggestion
            showSuggestion.value = true
        } else {
            error.value = data.error || 'No suggestion available'
        }
    } catch (e) {
        error.value = 'Failed to connect to AI service'
    } finally {
        loading.value = false
    }
}

const applySuggestion = () => {
    emit('selectCategory', suggestion.value)
    showSuggestion.value = false
    suggestion.value = null
}

const closeSuggestion = () => {
    showSuggestion.value = false
    suggestion.value = null
}
</script>

<template>
    <div class="relative">
        <button
            type="button"
            @click="fetchSuggestion"
            :disabled="loading"
            class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-md hover:bg-amber-100 disabled:opacity-50"
            :class="{ 'opacity-60': !aiConnected }"
            :title="aiConnected ? 'Suggest category with AI' : 'Connect an AI account in Profile settings to use AI features'"
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
        <p v-if="error" class="absolute top-full left-0 mt-1 text-xs text-red-600 whitespace-nowrap">
            {{ error }}
        </p>

        <!-- Suggestion popup -->
        <div
            v-if="showSuggestion && suggestion"
            class="absolute z-20 top-full right-0 mt-2 p-3 bg-white border border-stone-200 rounded-lg shadow-lg min-w-[220px]"
        >
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-stone-500">AI Suggestion</span>
                <button
                    type="button"
                    @click="closeSuggestion"
                    class="text-stone-400 hover:text-stone-600"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-2 bg-stone-50 rounded mb-2">
                <p class="font-medium text-sm text-stone-800">{{ suggestion.name }}</p>
                <p class="text-xs text-stone-600 mt-1">{{ suggestion.reason }}</p>
                <span
                    v-if="suggestion.is_existing"
                    class="inline-block mt-1 px-1.5 py-0.5 text-xs bg-green-100 text-green-700 rounded"
                >
                    Existing category
                </span>
                <span
                    v-else
                    class="inline-block mt-1 px-1.5 py-0.5 text-xs bg-amber-100 text-amber-700 rounded"
                >
                    New category
                </span>
            </div>

            <button
                type="button"
                @click="applySuggestion"
                class="w-full px-3 py-1.5 text-xs font-medium text-white bg-amber-600 rounded hover:bg-amber-700"
            >
                {{ suggestion.is_existing ? 'Use This Category' : 'Create This Category' }}
            </button>
        </div>
    </div>
</template>
