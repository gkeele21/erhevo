<script setup>
import TalkEngagement from '@/Components/TalkEngagement.vue'

defineProps({
    talk: { type: Object, required: true },
    canEngage: { type: Boolean, default: false },
    // Used for the random pick, to set it apart from the result list.
    highlight: { type: Boolean, default: false }
})

defineEmits(['filter-tag'])
</script>

<template>
    <div
        class="bg-white rounded-lg shadow border"
        :class="highlight ? 'border-amber-300 ring-1 ring-amber-200' : 'border-stone-100'"
    >
        <!-- Only the body is the outbound link — the engagement controls below
             sit outside the anchor so buttons and date pickers behave. -->
        <component
            :is="talk.url ? 'a' : 'div'"
            :href="talk.url || undefined"
            :target="talk.url ? '_blank' : undefined"
            :rel="talk.url ? 'noopener noreferrer' : undefined"
            class="block p-5 rounded-t-lg"
            :class="talk.url ? 'hover:bg-stone-50 transition' : ''"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-stone-800">
                        {{ talk.title }}
                    </h3>
                    <p class="text-sm text-stone-600 mt-1">
                        {{ talk.speaker_display_name }}
                        <span v-if="talk.calling" class="text-stone-400"> &middot; {{ talk.calling }}</span>
                    </p>
                </div>
                <span v-if="talk.source" class="shrink-0 px-2 py-1 bg-amber-100 text-amber-800 rounded text-xs whitespace-nowrap">
                    {{ talk.source }}
                </span>
            </div>
            <p v-if="talk.summary" class="text-sm text-stone-500 mt-3 line-clamp-3">
                {{ talk.summary }}
            </p>
            <div v-if="talk.tags?.length" class="flex flex-wrap gap-1.5 mt-3">
                <button
                    v-for="tag in talk.tags"
                    :key="tag.id"
                    type="button"
                    class="px-2 py-0.5 bg-stone-100 text-stone-600 rounded-full text-xs hover:bg-amber-100 hover:text-amber-800 transition-colors"
                    :title="`Show talks tagged ${tag.name}`"
                    @click.prevent.stop="$emit('filter-tag', tag)"
                >
                    #{{ tag.name }}
                </button>
            </div>
            <p v-if="talk.talk_date || talk.session" class="text-xs text-stone-400 mt-3">
                {{ [talk.talk_date, talk.session].filter(Boolean).join(' · ') }}
            </p>
        </component>

        <TalkEngagement :talk="talk" :can-engage="canEngage" />
    </div>
</template>
