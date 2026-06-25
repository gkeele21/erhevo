<script setup>
import { computed } from 'vue'
import draggable from 'vuedraggable'
import LessonItemCard from '@/Components/Lesson/LessonItemCard.vue'

const props = defineProps({
    items: {
        type: Array,
        default: () => []
    },
    itemTypes: {
        type: Array,
        default: () => []
    },
    scriptureBooks: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['update:items'])

// Transient client-side key so dragging stays stable. Stripped server-side
// (the controller only reads type/content/config).
let uidCounter = 0
const nextUid = () => `li-${uidCounter++}`

// Ensure every incoming item has a _uid (e.g. when seeded from the server on edit).
props.items.forEach((item) => {
    if (!item._uid) {
        item._uid = nextUid()
    }
})

const list = computed({
    get: () => props.items,
    set: (value) => emit('update:items', value),
})

const blockIcons = {
    'book-open': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
    'chat-bubble-bottom-center-text': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>',
    'film': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 4h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1z"/>',
    'document-text': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
    'question-mark-circle': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
}

const typeMetaFor = (type) => props.itemTypes.find((t) => t.value === type) || {}

const addBlock = (type) => {
    emit('update:items', [
        ...props.items,
        { _uid: nextUid(), type, content: '', config: {} },
    ])
}

const removeBlock = (index) => {
    const next = [...props.items]
    next.splice(index, 1)
    emit('update:items', next)
}
</script>

<template>
    <div class="space-y-4">
        <!-- Add block toolbar -->
        <div class="rounded-lg border border-stone-100 bg-white p-4 shadow">
            <p class="mb-3 text-sm font-medium text-stone-700">Add to your lesson</p>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                <button
                    v-for="type in itemTypes"
                    :key="type.value"
                    type="button"
                    @click="addBlock(type.value)"
                    class="flex flex-col items-center rounded-lg border-2 border-stone-200 p-3 text-center text-stone-600 transition-all hover:border-amber-400 hover:bg-amber-50"
                    :title="type.description"
                >
                    <svg class="mb-1 h-6 w-6 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="blockIcons[type.icon]"></svg>
                    <span class="text-xs font-medium">{{ type.label }}</span>
                </button>
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-if="!items.length"
            class="rounded-lg border-2 border-dashed border-stone-200 p-10 text-center text-stone-400"
        >
            Your lesson is empty. Add blocks above, then drag to arrange them.
        </div>

        <!-- Sortable list of blocks -->
        <draggable
            v-else
            v-model="list"
            item-key="_uid"
            handle=".lesson-drag-handle"
            :animation="150"
            ghost-class="opacity-50"
            class="space-y-3"
        >
            <template #item="{ element, index }">
                <LessonItemCard
                    :item="element"
                    :type-meta="typeMetaFor(element.type)"
                    :scripture-books="scriptureBooks"
                    @remove="removeBlock(index)"
                />
            </template>
        </draggable>
    </div>
</template>
