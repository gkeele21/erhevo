<script setup>
import { computed, ref } from 'vue'
import axios from 'axios'
import HelpTip from '@/Components/HelpTip.vue'

// Paste a link to where the content came from (a Facebook post, a tweet, an
// article). Shows the detected platform and can try to pull the post's text.
const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['update:modelValue', 'text-fetched', 'transcribed'])

const fetching = ref(false)
const transcribing = ref(false)
const error = ref('')
const fetched = ref(false)
const transcribed = ref(false)

// Platforms whose links are usually videos we can download + transcribe.
const VIDEO_PLATFORMS = ['Instagram', 'TikTok', 'YouTube', 'Facebook']

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

const canTranscribe = computed(() => VIDEO_PLATFORMS.includes(platform.value))

const onInput = (event) => {
    error.value = ''
    fetched.value = false
    transcribed.value = false
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

const transcribe = async () => {
    error.value = ''
    fetched.value = false
    transcribed.value = false
    transcribing.value = true
    try {
        const { data } = await axios.post(
            route('ai.transcribe-link'),
            { url: props.modelValue },
            { timeout: 300000 },
        )
        emit('transcribed', data)
        transcribed.value = true
    } catch (e) {
        error.value = e.response?.data?.error
            ?? e.response?.data?.errors?.url?.[0]
            ?? 'Something went wrong transcribing that video.'
    } finally {
        transcribing.value = false
    }
}
</script>

<template>
    <div>
        <label class="mb-1 flex items-center gap-1 text-sm font-medium text-stone-700">
            Source link (optional)
            <HelpTip
                anchor="posts"
                tip="Tip: paste an Instagram, TikTok, YouTube, or Facebook video link and Transcribe video turns the spoken words into text, author attributed automatically."
            />
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
                :disabled="!modelValue || fetching || transcribing"
                class="shrink-0 rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50 disabled:opacity-50"
            >
                {{ fetching ? 'Fetching...' : 'Get text' }}
            </button>
            <button
                v-if="canTranscribe"
                type="button"
                @click="transcribe"
                :disabled="!modelValue || fetching || transcribing"
                class="shrink-0 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100 disabled:opacity-50"
            >
                {{ transcribing ? 'Transcribing...' : 'Transcribe video' }}
            </button>
        </div>
        <p v-if="platform" class="mt-1 text-xs text-stone-500">
            Source:
            <span class="rounded bg-stone-100 px-1.5 py-0.5 font-medium text-stone-600">{{ platform }}</span>
        </p>
        <p v-if="transcribing" class="mt-1 text-xs text-stone-500">
            Downloading the video and transcribing the audio — this can take a minute...
        </p>
        <p v-if="fetched" class="mt-1 text-xs text-green-700">
            Text pulled from the link — review and edit it below.
        </p>
        <p v-if="transcribed" class="mt-1 text-xs text-green-700">
            Video transcribed — review the text and author below.
        </p>
        <p v-if="error" class="mt-1 text-xs text-amber-700">{{ error }}</p>
    </div>
</template>
