<script setup>
import { ref } from 'vue'
import { useAi } from '@/Composables/useAi'

const insights = ref(null)
const loading = ref(false)
const error = ref('')
const expanded = ref(false)
const postCount = ref(0)
const message = ref('')

const { ensureConnected } = useAi()

const fetchInsights = async () => {
    if (!ensureConnected(error)) return

    loading.value = true
    error.value = ''

    try {
        const response = await fetch('/api/ai/insights', {
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
            insights.value = data.insights
            postCount.value = data.post_count
            message.value = data.message || ''
            expanded.value = true
        } else {
            error.value = data.error || 'Failed to analyze insights'
        }
    } catch (e) {
        error.value = 'Failed to connect to AI service'
    } finally {
        loading.value = false
    }
}

const toggleExpanded = () => {
    if (!expanded.value && !insights.value) {
        if (!ensureConnected(error)) {
            expanded.value = true
            return
        }
        fetchInsights()
    } else {
        expanded.value = !expanded.value
    }
}

const emotionColors = {
    'grateful': 'bg-amber-100 text-amber-800',
    'hopeful': 'bg-blue-100 text-blue-800',
    'peaceful': 'bg-green-100 text-green-800',
    'joyful': 'bg-yellow-100 text-yellow-800',
    'reflective': 'bg-purple-100 text-purple-800',
    'determined': 'bg-red-100 text-red-800',
    'loving': 'bg-pink-100 text-pink-800',
    'default': 'bg-stone-100 text-stone-800'
}

const getEmotionColor = (emotion) => {
    const normalized = emotion?.toLowerCase() || ''
    for (const [key, value] of Object.entries(emotionColors)) {
        if (normalized.includes(key)) return value
    }
    return emotionColors.default
}
</script>

<template>
    <div class="bg-white rounded-lg shadow border border-navy-50">
        <button
            type="button"
            @click="toggleExpanded"
            class="w-full px-4 py-4 flex items-center justify-between text-left"
        >
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-teal to-aqua rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-navy">Journal Insights</h3>
                    <p class="text-sm text-teal">AI-powered analysis of your writing</p>
                </div>
            </div>
            <svg
                class="w-5 h-5 text-teal transition-transform"
                :class="{ 'rotate-180': expanded }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div v-if="expanded" class="px-4 pb-4 border-t border-navy-50">
            <div v-if="loading" class="flex items-center justify-center py-6">
                <svg class="w-5 h-5 animate-spin text-teal" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                </svg>
                <span class="ml-2 text-sm text-teal">Analyzing your journal...</span>
            </div>

            <p v-else-if="error" class="text-sm text-red-600 py-4">{{ error }}</p>

            <div v-else-if="message" class="py-4 text-center">
                <p class="text-sm text-teal">{{ message }}</p>
                <p class="text-xs text-teal-300 mt-1">Posts analyzed: {{ postCount }}</p>
            </div>

            <div v-else-if="insights" class="py-4 space-y-5">
                <!-- Themes -->
                <div v-if="insights.themes?.length">
                    <h4 class="text-sm font-medium text-navy mb-2">Recurring Themes</h4>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="theme in insights.themes"
                            :key="theme"
                            class="px-3 py-1 text-sm bg-navy-50 text-navy rounded-full"
                        >
                            {{ theme }}
                        </span>
                    </div>
                </div>

                <!-- Emotions -->
                <div v-if="insights.emotions?.length">
                    <h4 class="text-sm font-medium text-navy mb-2">Emotional Tones</h4>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="emotion in insights.emotions"
                            :key="emotion"
                            class="px-3 py-1 text-sm rounded-full"
                            :class="getEmotionColor(emotion)"
                        >
                            {{ emotion }}
                        </span>
                    </div>
                </div>

                <!-- Growth Observation -->
                <div v-if="insights.growth">
                    <h4 class="text-sm font-medium text-navy mb-2">Growth Observation</h4>
                    <p class="text-sm text-teal bg-teal-50 p-3 rounded-lg border border-teal-100">
                        {{ insights.growth }}
                    </p>
                </div>

                <!-- Recommendations -->
                <div v-if="insights.recommendations?.length">
                    <h4 class="text-sm font-medium text-navy mb-2">Suggestions for Reflection</h4>
                    <ul class="space-y-1">
                        <li
                            v-for="(rec, index) in insights.recommendations"
                            :key="index"
                            class="text-sm text-teal flex items-start gap-2"
                        >
                            <svg class="w-4 h-4 text-amber shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            {{ rec }}
                        </li>
                    </ul>
                </div>

                <div class="pt-2 flex items-center justify-between text-xs text-teal-300">
                    <span>Based on {{ postCount }} posts</span>
                    <button
                        type="button"
                        @click="fetchInsights"
                        :disabled="loading"
                        class="text-amber hover:text-amber-600 font-medium disabled:opacity-50"
                    >
                        Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
