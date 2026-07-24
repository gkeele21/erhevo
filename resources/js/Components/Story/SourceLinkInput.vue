<script setup>
import { computed, ref } from 'vue'
import axios from 'axios'

// Paste a link to where the content came from (a Facebook post, a tweet, an
// article). Shows the detected platform and can try to pull the post's text.
const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['update:modelValue', 'text-fetched'])

const fetching = ref(false)
const error = ref('')
const fetched = ref(false)

// Client-side mirror of SourceLink::platformFor, for instant feedback.
const PLATFORMS = {
    'facebook.com': 'Facebook',
    'fb.com': 'Facebook',
    'fb.watch': 'Facebook',
    'instagram.com': 'Instagram',
    'twitter.com': 'X (Twitter)',
    'x.com': 'X (Twitter)',
    'threads.net': 'Threads',
    'youtube.com': 'YouTube',
    'youtu.be': 'YouTube',
    'tiktok.com': 'TikTok',
    'linkedin.com': 'LinkedIn',
    'pinterest.com': 'Pinterest',
    'reddit.com': 'Reddit',
    'medium.com': 'Medium',
    'substack.com': 'Substack',
}

const platform = computed(() => {
    try {
        const host = new URL(props.modelValue).hostname.toLowerCase()
        for (const [domain, label] of Object.entries(PLATFORMS)) {
            if (host === domain || host.endsWith(`.${domain}`)) return label
        }
        return host.replace(/^www\./, '')
    } catch {
        return null
    }
})

const onInput = (event) => {
    error.value = ''
    fetched.value = false
    emit('update:modelValue', event.target.value)
}

const fetchText = async () => {
    error.value = ''
    fetched.value = false
    fetching.value = true
    try {
        const { data } = await axios.post(route('posts.fetch-source-link'), {
            url: props.modelValue,
        })
        emit('text-fetched', { text: data.text, title: data.title })
        fetched.value = true
    } catch (e) {
        error.value = e.response?.data?.error
            ?? e.response?.data?.errors?.url?.[0]
            ?? 'Something went wrong fetching that link.'
    } finally {
        fetching.value = false
    }
}
</script>

<template>
    <div>
        <label class="block text-sm font-medium text-stone-700 mb-1">
            Source link (optional)
        </label>
        <div class="flex gap-2">
            <input
                :value="modelValue"
                @input="onInput"
                type="url"
                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                placeholder="Paste a link — e.g. a Facebook post..."
            >
            <button
                type="button"
                @click="fetchText"
                :disabled="!modelValue || fetching"
                class="shrink-0 rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50 disabled:opacity-50"
            >
                {{ fetching ? 'Fetching...' : 'Get text' }}
            </button>
        </div>
        <p v-if="platform" class="mt-1 text-xs text-stone-500">
            Source:
            <span class="rounded bg-stone-100 px-1.5 py-0.5 font-medium text-stone-600">{{ platform }}</span>
        </p>
        <p v-if="fetched" class="mt-1 text-xs text-green-700">
            Text pulled from the link — review and edit it below.
        </p>
        <p v-if="error" class="mt-1 text-xs text-amber-700">{{ error }}</p>
    </div>
</template>
