<script setup>
import { computed } from 'vue'

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    // When true, render larger type for the teaching view.
    teaching: {
        type: Boolean,
        default: false
    }
})

// 'key' (make sure to cover) | 'optional' (if time) | null
const emphasis = computed(() => props.item.config?.emphasis || null)

// date_given is a date-only string (YYYY-MM-DD); anchor to local midnight so
// it doesn't shift a day in negative timezones.
const formatGivenDate = (date) => {
    if (!date) return ''
    return new Date(`${date}T00:00:00`).toLocaleDateString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric',
    })
}
</script>

<template>
    <div
        :class="{
            'rounded-r-lg border-l-4 border-amber-400 bg-amber-50/40 py-2 pl-4 pr-2': emphasis === 'key',
            'opacity-60': emphasis === 'optional'
        }"
    >
    <!-- Emphasis badge -->
    <p v-if="emphasis === 'key'" class="mb-2 inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-amber-700">
        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
        </svg>
        Key point
    </p>
    <p v-else-if="emphasis === 'optional'" class="mb-2 inline-flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-stone-400">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        If time allows
    </p>

    <!-- Scripture -->
    <div v-if="item.type === 'scripture'">
        <p class="font-semibold text-stone-800" :class="teaching ? 'text-xl' : ''">
            {{ item.config?.reference }}
        </p>
        <blockquote
            v-if="item.content || item.config?.passage"
            class="mt-2 border-l-4 border-amber-300 pl-4 text-stone-700"
        >
            <div
                class="prose prose-stone max-w-none"
                :class="teaching ? 'prose-lg' : ''"
                v-html="item.content || item.config?.passage"
            ></div>
        </blockquote>
    </div>

    <!-- Talk / Quote -->
    <div v-else-if="item.type === 'talk'">
        <p class="font-semibold text-stone-800" :class="teaching ? 'text-xl' : ''">
            {{ item.config?.title }}
        </p>
        <p v-if="item.config?.speaker" class="text-sm text-stone-500">{{ item.config.speaker }}</p>
        <blockquote
            v-if="item.content"
            class="mt-2 border-l-4 border-amber-300 pl-4 italic text-stone-700"
            :class="teaching ? 'text-lg' : ''"
        >
            {{ item.content }}
        </blockquote>
        <a
            v-if="item.config?.url"
            :href="item.config.url"
            target="_blank"
            rel="noopener"
            class="mt-2 inline-block text-sm text-amber-600 underline hover:text-amber-800"
        >
            Read the talk →
        </a>
    </div>

    <!-- Quote (references a saved Quote post) -->
    <div v-else-if="item.type === 'quote'">
        <blockquote
            v-if="item.content"
            class="border-l-4 border-amber-300 pl-4 italic text-stone-700"
        >
            <div
                class="prose prose-stone max-w-none"
                :class="teaching ? 'prose-lg' : ''"
                v-html="item.content"
            ></div>
        </blockquote>
        <p
            v-if="item.config?.author || item.config?.source_title || item.config?.date_given || item.config?.church_calling"
            class="mt-2 text-sm text-stone-500"
            :class="teaching ? 'text-base' : ''"
        >
            <span v-if="item.config?.author" class="font-medium text-stone-700">{{ item.config.author }}</span>
            <span v-if="item.config?.church_calling" class="text-stone-400"> ({{ item.config.church_calling }})</span>
            <span v-if="item.config?.source_title">{{ item.config?.author ? ', ' : '' }}{{ item.config.source_title }}</span>
            <span v-if="item.config?.date_given"> · {{ formatGivenDate(item.config.date_given) }}</span>
        </p>
        <div v-if="item.config?.tags && item.config.tags.length" class="mt-2 flex flex-wrap gap-1">
            <span
                v-for="tag in item.config.tags"
                :key="tag"
                class="rounded bg-amber-50 px-2 py-0.5 text-xs text-amber-700"
            >#{{ tag }}</span>
        </div>
    </div>

    <!-- My Post (the creator's own story/thought/note) -->
    <div v-else-if="item.type === 'post'">
        <div
            v-if="item.content"
            class="prose prose-stone max-w-none border-l-4 border-teal-300 pl-4 text-stone-700"
            :class="teaching ? 'prose-lg' : ''"
            v-html="item.content"
        ></div>
        <p v-if="item.config?.post_title" class="mt-2 text-sm text-stone-500" :class="teaching ? 'text-base' : ''">
            from my post <span class="font-medium text-stone-700">“{{ item.config.post_title }}”</span>
        </p>
    </div>

    <!-- Video / Link -->
    <div v-else-if="item.type === 'video'">
        <!-- Uploaded local video -->
        <div v-if="item.config?.file_url">
            <p v-if="item.config?.title" class="mb-2 font-medium text-stone-800" :class="teaching ? 'text-xl' : ''">
                {{ item.config.title }}
                <span v-if="item.config?.duration" class="text-sm font-normal text-stone-400">({{ item.config.duration }})</span>
            </p>
            <video :src="item.config.file_url" controls class="w-full rounded-lg bg-black" :class="teaching ? 'max-h-[70vh]' : 'max-h-96'"></video>
        </div>

        <!-- External link -->
        <a
            v-else
            :href="item.config?.url"
            target="_blank"
            rel="noopener"
            class="inline-flex items-center gap-2 font-medium text-amber-600 underline hover:text-amber-800"
            :class="teaching ? 'text-xl' : ''"
        >
            <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ item.config?.title || item.config?.url }}
            <span v-if="item.config?.duration" class="text-sm font-normal text-stone-400">({{ item.config.duration }})</span>
        </a>
        <p v-if="item.config?.note" class="mt-1 text-sm text-stone-500">{{ item.config.note }}</p>
    </div>

    <!-- Image -->
    <figure v-else-if="item.type === 'image'">
        <img
            v-if="item.config?.file_url || item.config?.url"
            :src="item.config.file_url || item.config.url"
            :alt="item.config?.caption || ''"
            class="rounded-lg"
            :class="teaching ? 'max-h-[70vh]' : 'max-h-96'"
        >
        <figcaption v-if="item.config?.caption" class="mt-2 text-sm italic text-stone-500">
            {{ item.config.caption }}
        </figcaption>
    </figure>

    <!-- My Words (rich text) -->
    <div
        v-else-if="item.type === 'text'"
        class="prose prose-stone max-w-none"
        :class="teaching ? 'prose-lg' : ''"
        v-html="item.content"
    ></div>

    <!-- Scripture Help -->
    <div v-else-if="item.type === 'scripture_help'">
        <p class="mb-1 inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-teal-700">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
            Scripture help
        </p>
        <div
            class="prose prose-stone max-w-none rounded-lg border border-teal-100 bg-teal-50/40 p-4"
            :class="teaching ? 'prose-lg' : ''"
            v-html="item.content"
        ></div>
    </div>

    <!-- Question -->
    <div
        v-else-if="item.type === 'question'"
        class="rounded-lg border border-amber-200 bg-amber-50 p-4"
    >
        <div class="flex items-start gap-2">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="font-medium text-amber-900" :class="teaching ? 'text-xl' : ''">{{ item.content }}</p>
        </div>
    </div>
    </div>
</template>
