<script setup>
// A small "?" that links into the Help section (new tab) — the title
// doubles as an instant one-line insight on hover.
defineProps({
    anchor: { type: String, required: true },
    tip: { type: String, default: 'Open the guide for this feature' }
})
</script>

<template>
    <a
        :href="`${route('help')}#${anchor}`"
        target="_blank"
        rel="noopener"
        :title="tip"
        class="inline-flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-full text-stone-300 transition-colors hover:text-amber-600"
        @click.stop
    >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </a>
</template>
