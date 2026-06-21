<script setup>
defineProps({
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
</script>

<template>
    <!-- Scripture -->
    <div v-if="item.type === 'scripture'">
        <p class="font-semibold text-stone-800" :class="teaching ? 'text-xl' : ''">
            {{ item.config?.reference }}
        </p>
        <blockquote
            v-if="item.config?.passage"
            class="mt-2 border-l-4 border-amber-300 pl-4 italic text-stone-700"
            :class="teaching ? 'text-lg' : ''"
        >
            {{ item.config.passage }}
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

    <!-- Video / Link -->
    <div v-else-if="item.type === 'video'">
        <a
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
        </a>
        <p v-if="item.config?.note" class="mt-1 text-sm text-stone-500">{{ item.config.note }}</p>
    </div>

    <!-- My Words (rich text) -->
    <div
        v-else-if="item.type === 'text'"
        class="prose prose-stone max-w-none"
        :class="teaching ? 'prose-lg' : ''"
        v-html="item.content"
    ></div>

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
</template>
