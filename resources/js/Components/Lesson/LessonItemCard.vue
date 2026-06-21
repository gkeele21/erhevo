<script setup>
import StoryEditor from '@/Components/Story/StoryEditor.vue'
import TalkPicker from '@/Components/Lesson/TalkPicker.vue'

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    typeMeta: {
        type: Object,
        default: () => ({})
    }
})

defineEmits(['remove'])

// Ensure config is always an object for the config-backed block types.
if (!props.item.config) {
    props.item.config = {}
}
</script>

<template>
    <div class="rounded-lg border border-stone-200 bg-white shadow-sm">
        <!-- Card header: drag handle, type label, remove -->
        <div class="flex items-center justify-between border-b border-stone-100 bg-stone-50 px-3 py-2">
            <div class="flex items-center gap-2">
                <span class="lesson-drag-handle cursor-grab text-stone-400 hover:text-stone-600" title="Drag to reorder">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h.01M8 12h.01M8 18h.01M16 6h.01M16 12h.01M16 18h.01"/>
                    </svg>
                </span>
                <span class="text-sm font-medium text-stone-700">{{ typeMeta.label || item.type }}</span>
            </div>
            <button
                type="button"
                @click="$emit('remove')"
                class="text-stone-400 hover:text-red-600"
                title="Remove block"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="p-4">
            <!-- Scripture -->
            <div v-if="item.type === 'scripture'" class="space-y-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Reference</label>
                    <input
                        v-model="item.config.reference"
                        type="text"
                        class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        placeholder="e.g. 1 Nephi 3:7"
                    >
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Passage (optional)</label>
                    <textarea
                        v-model="item.config.passage"
                        rows="3"
                        class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        placeholder="Paste the verse text if you want it shown in the lesson..."
                    ></textarea>
                </div>
            </div>

            <!-- Talk / Quote -->
            <div v-else-if="item.type === 'talk'" class="space-y-3">
                <TalkPicker v-model="item.config" />
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Quote or note (optional)</label>
                    <textarea
                        v-model="item.content"
                        rows="3"
                        class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        placeholder="Pull a quote from the talk, or add a note about how you'll use it..."
                    ></textarea>
                </div>
            </div>

            <!-- Video / Link -->
            <div v-else-if="item.type === 'video'" class="space-y-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">URL</label>
                    <input
                        v-model="item.config.url"
                        type="url"
                        class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        placeholder="https://..."
                    >
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Title (optional)</label>
                    <input
                        v-model="item.config.title"
                        type="text"
                        class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        placeholder="What is this video/link?"
                    >
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Note (optional)</label>
                    <textarea
                        v-model="item.config.note"
                        rows="2"
                        class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        placeholder="e.g. Show from 2:30 to 4:00"
                    ></textarea>
                </div>
            </div>

            <!-- My Words (rich text) -->
            <div v-else-if="item.type === 'text'">
                <StoryEditor v-model="item.content" placeholder="Write your own words..." />
            </div>

            <!-- Question -->
            <div v-else-if="item.type === 'question'">
                <label class="mb-1 block text-sm font-medium text-stone-700">Question to ask</label>
                <textarea
                    v-model="item.content"
                    rows="2"
                    class="w-full rounded-lg border-stone-300 bg-amber-50/40 focus:border-amber-500 focus:ring-amber-500"
                    placeholder="e.g. How has this principle blessed your life?"
                ></textarea>
            </div>
        </div>
    </div>
</template>
