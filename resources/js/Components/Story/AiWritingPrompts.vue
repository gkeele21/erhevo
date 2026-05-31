<script setup>
import { ref } from 'vue'

const emit = defineEmits(['selectPrompt'])

const prompts = ref([])
const loading = ref(false)
const error = ref('')
const expanded = ref(false)

const fetchPrompts = async () => {
    loading.value = true
    error.value = ''

    try {
        const response = await fetch('/api/ai/writing-prompts', {
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
            body: JSON.stringify({})
        })

        const data = await response.json()

        if (data.success) {
            prompts.value = data.prompts
            expanded.value = true
        } else {
            error.value = data.error || 'Failed to get prompts'
        }
    } catch (e) {
        error.value = 'Failed to connect to AI service'
    } finally {
        loading.value = false
    }
}

const selectPrompt = (prompt) => {
    emit('selectPrompt', prompt.prompt)
    expanded.value = false
}

const toggleExpanded = () => {
    if (!expanded.value && prompts.value.length === 0) {
        fetchPrompts()
    } else {
        expanded.value = !expanded.value
    }
}

const themeColors = {
    'gratitude': 'bg-amber-100 text-amber-800',
    'faith': 'bg-blue-100 text-blue-800',
    'family': 'bg-green-100 text-green-800',
    'growth': 'bg-purple-100 text-purple-800',
    'service': 'bg-teal-100 text-teal-800',
    'reflection': 'bg-indigo-100 text-indigo-800',
    'hope': 'bg-yellow-100 text-yellow-800',
    'love': 'bg-pink-100 text-pink-800',
    'default': 'bg-stone-100 text-stone-800'
}

const getThemeColor = (theme) => {
    const normalizedTheme = theme?.toLowerCase() || ''
    return themeColors[normalizedTheme] || themeColors.default
}
</script>

<template>
    <div class="border border-amber-200 rounded-lg bg-amber-50/50">
        <button
            type="button"
            @click="toggleExpanded"
            class="w-full px-4 py-3 flex items-center justify-between text-left"
        >
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                <span class="text-sm font-medium text-amber-800">Need inspiration?</span>
            </div>
            <svg
                class="w-5 h-5 text-amber-600 transition-transform"
                :class="{ 'rotate-180': expanded }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div v-if="expanded" class="px-4 pb-4">
            <div v-if="loading" class="flex items-center justify-center py-4">
                <svg class="w-5 h-5 animate-spin text-amber-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                </svg>
                <span class="ml-2 text-sm text-amber-700">Generating prompts...</span>
            </div>

            <p v-else-if="error" class="text-sm text-red-600 py-2">{{ error }}</p>

            <div v-else-if="prompts.length" class="space-y-2">
                <div
                    v-for="(prompt, index) in prompts"
                    :key="index"
                    @click="selectPrompt(prompt)"
                    class="p-3 bg-white rounded-lg border border-stone-200 cursor-pointer hover:border-amber-400 hover:shadow-sm transition-all"
                >
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm text-stone-700 flex-1">{{ prompt.prompt }}</p>
                        <span
                            class="shrink-0 px-2 py-0.5 text-xs font-medium rounded"
                            :class="getThemeColor(prompt.theme)"
                        >
                            {{ prompt.theme }}
                        </span>
                    </div>
                </div>

                <button
                    type="button"
                    @click="fetchPrompts"
                    :disabled="loading"
                    class="mt-2 text-sm text-amber-700 hover:text-amber-900 font-medium disabled:opacity-50"
                >
                    Get new prompts
                </button>
            </div>

            <p v-else class="text-sm text-stone-500 py-2">
                Click to get personalized writing prompts
            </p>
        </div>
    </div>
</template>
