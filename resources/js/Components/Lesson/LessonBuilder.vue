<script setup>
import { nextTick, provide, reactive, ref } from 'vue'
import draggable from 'vuedraggable'
import LessonItemCard from '@/Components/Lesson/LessonItemCard.vue'
import LessonGroupCard from '@/Components/Lesson/LessonGroupCard.vue'

// Broadcast collapse/expand-all to every item card (including those nested in
// groups) via inject. Per-item toggles still work independently.
const collapseBus = reactive({ tick: 0, collapsed: false })
provide('lessonCollapseBus', collapseBus)
const setAllCollapsed = (value) => {
    collapseBus.collapsed = value
    collapseBus.tick++
}

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
    },
    uploadLimits: {
        type: Object,
        default: () => ({})
    }
})

// Make upload limits available to every item card (incl. those in groups).
provide('lessonUploadLimits', props.uploadLimits)

// Transient client-side keys so dragging stays stable. Stripped server-side
// (the controller only reads type/content/config/children).
let uidCounter = 0
const nextUid = () => `li-${uidCounter++}`

const ensureUids = (nodes) => {
    nodes.forEach((node) => {
        if (!node._uid) node._uid = nextUid()
        if (node.type === 'group') {
            if (!node.children) node.children = []
            ensureUids(node.children)
        }
    })
}
ensureUids(props.items)

const blockIcons = {
    'book-open': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
    'chat-bubble-bottom-center-text': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>',
    'film': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 4h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1z"/>',
    'photo': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
    'document-text': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
    'question-mark-circle': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'folder': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>',
}

const typeMetaFor = (type) => props.itemTypes.find((t) => t.value === type) || {}

const listRef = ref(null)
const highlightedUid = ref(null)
let highlightTimer = null

const flagNew = async (uid) => {
    await nextTick()
    listRef.value?.$el?.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'center' })
    highlightedUid.value = uid
    clearTimeout(highlightTimer)
    highlightTimer = setTimeout(() => { highlightedUid.value = null }, 1600)
}

const addBlock = (type) => {
    const uid = nextUid()
    props.items.push({ _uid: uid, type, content: '', config: {} })
    flagNew(uid)
}

const addGroup = () => {
    const uid = nextUid()
    props.items.push({ _uid: uid, type: 'group', config: { title: '' }, children: [] })
    flagNew(uid)
}

const removeBlock = (index) => {
    props.items.splice(index, 1)
}

// Dissolve a group, lifting its items back into the lesson at its position.
const ungroup = (index) => {
    const group = props.items[index]
    const children = group?.children ?? []
    props.items.splice(index, 1, ...children)
}
</script>

<template>
    <div class="space-y-4">
        <!-- Add block toolbar -->
        <div class="rounded-lg border border-stone-100 bg-white p-4 shadow">
            <p class="mb-3 text-sm font-medium text-stone-700">Add to your lesson</p>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-6">
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
                <button
                    type="button"
                    @click="addGroup"
                    class="flex flex-col items-center rounded-lg border-2 border-dashed border-amber-300 p-3 text-center text-amber-700 transition-all hover:border-amber-500 hover:bg-amber-50"
                    title="Add a named group to organize items"
                >
                    <svg class="mb-1 h-6 w-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="blockIcons['folder']"></svg>
                    <span class="text-xs font-medium">Group</span>
                </button>
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-if="!items.length"
            class="rounded-lg border-2 border-dashed border-stone-200 p-10 text-center text-stone-400"
        >
            Your lesson is empty. Add blocks above, then drag to arrange them — group related items if you like.
        </div>

        <!-- Lesson blocks -->
        <div v-else class="space-y-3">
            <!-- Collapse / expand all -->
            <div class="flex justify-end gap-2">
                <button
                    type="button"
                    @click="setAllCollapsed(true)"
                    class="inline-flex items-center gap-1.5 rounded-md border border-stone-200 bg-white px-3 py-1.5 text-xs font-medium text-stone-600 hover:border-amber-400 hover:text-amber-700"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11h14M5 11l3-3m-3 3l3 3M19 13H5m14 0l-3 3m3-3l-3-3"/>
                    </svg>
                    Collapse all
                </button>
                <button
                    type="button"
                    @click="setAllCollapsed(false)"
                    class="inline-flex items-center gap-1.5 rounded-md border border-stone-200 bg-white px-3 py-1.5 text-xs font-medium text-stone-600 hover:border-amber-400 hover:text-amber-700"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4h4m8 0h4v4M4 16v4h4m8 0h4v-4"/>
                    </svg>
                    Expand all
                </button>
            </div>

            <!-- Sortable list of blocks (loose items and groups) -->
            <draggable
                ref="listRef"
                :list="items"
                :group="{ name: 'lesson-blocks' }"
                handle=".lesson-drag-handle"
                item-key="_uid"
                :animation="150"
                ghost-class="opacity-50"
                :force-fallback="true"
                :scroll="true"
                :bubble-scroll="true"
                :scroll-sensitivity="120"
                :scroll-speed="12"
                class="space-y-3"
            >
                <template #item="{ element, index }">
                    <LessonGroupCard
                        v-if="element.type === 'group'"
                        :group="element"
                        :item-types="itemTypes"
                        :scripture-books="scriptureBooks"
                        :make-uid="nextUid"
                        @ungroup="ungroup(index)"
                    />
                    <LessonItemCard
                        v-else
                        :item="element"
                        :type-meta="typeMetaFor(element.type)"
                        :scripture-books="scriptureBooks"
                        :highlight="element._uid === highlightedUid"
                        @remove="removeBlock(index)"
                    />
                </template>
            </draggable>
        </div>
    </div>
</template>
