<script setup>
import { ref } from 'vue'
import { useAi } from '@/Composables/useAi'

const props = defineProps({
    content: {
        type: String,
        default: ''
    },
    currentVisibility: {
        type: String,
        default: 'public'
    }
})

const emit = defineEmits(['updateVisibility'])

const analysis = ref(null)
const loading = ref(false)
const error = ref('')
const showAnalysis = ref(false)

const { aiConnected, ensureConnected } = useAi()

const analyzeContent = async () => {
    if (!ensureConnected(error)) return

    if (!props.content || props.content.length < 20) {
        error.value = 'Content must be at least 20 characters'
        return
    }

    loading.value = true
    error.value = ''

    try {
        const response = await fetch('/api/ai/analyze-sensitivity', {
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
            analysis.value = data.analysis
            showAnalysis.value = true
        } else {
            error.value = data.error || 'Failed to analyze content'
        }
    } catch (e) {
        error.value = 'Failed to connect to AI service'
    } finally {
        loading.value = false
    }
}

const applySuggestion = () => {
    if (analysis.value?.suggested_visibility) {
        emit('updateVisibility', analysis.value.suggested_visibility)
        showAnalysis.value = false
    }
}

const closeAnalysis = () => {
    showAnalysis.value = false
}

const sensitivityColors = {
    'low': 'bg-green-100 text-green-800 border-green-200',
    'medium': 'bg-yellow-100 text-yellow-800 border-yellow-200',
    'high': 'bg-red-100 text-red-800 border-red-200'
}

const getSensitivityColor = (level) => {
    return sensitivityColors[level?.toLowerCase()] || sensitivityColors.low
}

const sensitivityIcons = {
    'low': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'medium': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
    'high': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
}
</script>

<template>
    <div class="relative">
        <button
            type="button"
            @click="analyzeContent"
            :disabled="loading"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-stone-700 bg-stone-100 border border-stone-200 rounded-lg hover:bg-stone-200 disabled:opacity-50"
            :class="{ 'opacity-60': !aiConnected }"
            :title="aiConnected ? 'Check privacy with AI' : 'Connect an AI account in Profile settings to use AI features'"
        >
            <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <span>Check Privacy</span>
        </button>

        <!-- Error message -->
        <p v-if="error" class="absolute top-full left-0 mt-1 text-xs text-red-600">
            {{ error }}
        </p>

        <!-- Analysis panel -->
        <div
            v-if="showAnalysis && analysis"
            class="absolute z-20 top-full right-0 mt-2 p-4 bg-white border border-stone-200 rounded-lg shadow-lg w-80"
        >
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-stone-700">Privacy Analysis</span>
                <button
                    type="button"
                    @click="closeAnalysis"
                    class="text-stone-400 hover:text-stone-600"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Sensitivity Badge -->
            <div
                class="flex items-center gap-2 px-3 py-2 rounded-lg border mb-3"
                :class="getSensitivityColor(analysis.sensitivity)"
            >
                <svg
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    v-html="sensitivityIcons[analysis.sensitivity?.toLowerCase()] || sensitivityIcons.low"
                ></svg>
                <span class="font-medium capitalize">{{ analysis.sensitivity }} Sensitivity</span>
            </div>

            <!-- Reasons -->
            <div v-if="analysis.reasons?.length" class="mb-3">
                <h4 class="text-xs font-medium text-stone-500 mb-1">Concerns Found:</h4>
                <ul class="space-y-1">
                    <li
                        v-for="(reason, index) in analysis.reasons"
                        :key="index"
                        class="text-xs text-stone-600 flex items-start gap-1"
                    >
                        <span class="text-stone-400">•</span>
                        {{ reason }}
                    </li>
                </ul>
            </div>

            <!-- Names Detected -->
            <div v-if="analysis.names_detected?.length" class="mb-3">
                <h4 class="text-xs font-medium text-stone-500 mb-1">Names Detected:</h4>
                <div class="flex flex-wrap gap-1">
                    <span
                        v-for="name in analysis.names_detected"
                        :key="name"
                        class="px-2 py-0.5 text-xs bg-stone-100 text-stone-700 rounded"
                    >
                        {{ name }}
                    </span>
                </div>
            </div>

            <!-- Recommendation -->
            <p v-if="analysis.recommendation" class="text-xs text-stone-600 mb-3 p-2 bg-stone-50 rounded">
                {{ analysis.recommendation }}
            </p>

            <!-- Apply Suggestion Button -->
            <div
                v-if="analysis.suggested_visibility && analysis.suggested_visibility !== currentVisibility"
                class="flex items-center justify-between p-2 bg-amber-50 rounded-lg border border-amber-200"
            >
                <span class="text-xs text-amber-800">
                    Suggested: <span class="font-medium capitalize">{{ analysis.suggested_visibility }}</span>
                </span>
                <button
                    type="button"
                    @click="applySuggestion"
                    class="px-2 py-1 text-xs font-medium text-white bg-amber-600 rounded hover:bg-amber-700"
                >
                    Apply
                </button>
            </div>

            <div
                v-else-if="analysis.suggested_visibility === currentVisibility"
                class="text-xs text-green-700 text-center p-2 bg-green-50 rounded"
            >
                Current visibility setting looks good!
            </div>
        </div>
    </div>
</template>
