<script setup>
import { ref } from 'vue'

const props = defineProps({
    content: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['excerptGenerated'])

const generatedExcerpt = ref('')
const loading = ref(false)
const error = ref('')
const showPreview = ref(false)

const generateExcerpt = async () => {
    if (!props.content || props.content.length < 50) {
        error.value = 'Content must be at least 50 characters'
        return
    }

    loading.value = true
    error.value = ''
    showPreview.value = false

    try {
        const response = await fetch('/api/ai/generate-excerpt', {
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
            generatedExcerpt.value = data.excerpt
            showPreview.value = true
        } else {
            error.value = data.error || 'Failed to generate excerpt'
        }
    } catch (e) {
        error.value = 'Failed to connect to AI service'
    } finally {
        loading.value = false
    }
}

const useExcerpt = () => {
    emit('excerptGenerated', generatedExcerpt.value)
    showPreview.value = false
    generatedExcerpt.value = ''
}

const closePreview = () => {
    showPreview.value = false
    generatedExcerpt.value = ''
}
</script>

<template>
    <div class="relative inline-block">
        <button
            type="button"
            @click="generateExcerpt"
            :disabled="loading"
            class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-md hover:bg-amber-100 disabled:opacity-50"
            title="Generate excerpt with AI"
        >
            <svg v-if="loading" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
            </svg>
            <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
            </svg>
            <span>Generate</span>
        </button>

        <!-- Error message -->
        <p v-if="error" class="absolute top-full left-0 mt-1 text-xs text-red-600 whitespace-nowrap">
            {{ error }}
        </p>

        <!-- Preview popup -->
        <div
            v-if="showPreview"
            class="absolute z-20 top-full right-0 mt-2 p-4 bg-white border border-stone-200 rounded-lg shadow-lg w-80"
        >
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-stone-500">AI Generated Excerpt</span>
                <button
                    type="button"
                    @click="closePreview"
                    class="text-stone-400 hover:text-stone-600"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <p class="text-sm text-stone-700 mb-4 p-2 bg-stone-50 rounded">
                {{ generatedExcerpt }}
            </p>

            <div class="flex gap-2">
                <button
                    type="button"
                    @click="useExcerpt"
                    class="flex-1 px-3 py-1.5 text-xs font-medium text-white bg-amber-600 rounded hover:bg-amber-700"
                >
                    Use This
                </button>
                <button
                    type="button"
                    @click="generateExcerpt"
                    :disabled="loading"
                    class="flex-1 px-3 py-1.5 text-xs font-medium text-stone-700 bg-stone-100 rounded hover:bg-stone-200 disabled:opacity-50"
                >
                    Regenerate
                </button>
            </div>
        </div>
    </div>
</template>
