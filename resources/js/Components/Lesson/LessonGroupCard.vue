<script setup>
import { ref, nextTick } from 'vue'
import draggable from 'vuedraggable'
import LessonItemCard from '@/Components/Lesson/LessonItemCard.vue'

const props = defineProps({
    group: {
        type: Object,
        required: true
    },
    itemTypes: {
        type: Array,
        default: () => []
    },
    scriptureBooks: {
        type: Array,
        default: () => []
    },
    makeUid: {
        type: Function,
        required: true
    }
})

defineEmits(['ungroup'])

if (!props.group.config) props.group.config = {}
if (!props.group.children) props.group.children = []

const listRef = ref(null)
const highlightedUid = ref(null)
let highlightTimer = null

const typeMetaFor = (type) => props.itemTypes.find((t) => t.value === type) || {}

const addChild = async (type) => {
    const uid = props.makeUid()
    props.group.children.push({ _uid: uid, type, content: '', config: {} })

    await nextTick()
    listRef.value?.$el?.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'center' })
    highlightedUid.value = uid
    clearTimeout(highlightTimer)
    highlightTimer = setTimeout(() => { highlightedUid.value = null }, 1600)
}

const removeChild = (index) => {
    props.group.children.splice(index, 1)
}

// A group child list never accepts another group (one level of nesting only).
const childMove = (evt) => evt.draggedContext.element?.type !== 'group'
</script>

<template>
    <div class="rounded-lg border-2 border-amber-200 bg-amber-50/40">
        <!-- Group header -->
        <div class="flex items-center gap-2 border-b border-amber-200 px-3 py-2">
            <span class="lesson-drag-handle cursor-grab text-amber-500 hover:text-amber-700" title="Drag to reorder group">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h.01M8 12h.01M8 18h.01M16 6h.01M16 12h.01M16 18h.01"/>
                </svg>
            </span>
            <input
                v-model="group.config.title"
                type="text"
                placeholder="Group name (e.g. Introduction)"
                class="flex-1 rounded-md border-transparent bg-transparent font-semibold text-amber-900 placeholder:font-normal placeholder:text-amber-500/70 focus:border-amber-400 focus:bg-white focus:ring-amber-400"
            >
            <button
                type="button"
                @click="$emit('ungroup')"
                class="rounded px-2 py-1 text-xs font-medium text-amber-700 hover:bg-amber-100"
                title="Remove the group but keep its items in the lesson"
            >
                Ungroup
            </button>
        </div>

        <div class="space-y-3 p-3">
            <draggable
                ref="listRef"
                :list="group.children"
                :group="{ name: 'lesson-blocks' }"
                :move="childMove"
                handle=".lesson-drag-handle"
                item-key="_uid"
                :animation="150"
                ghost-class="opacity-50"
                :force-fallback="true"
                :scroll="true"
                :bubble-scroll="true"
                :scroll-sensitivity="120"
                :scroll-speed="12"
                class="min-h-[2.5rem] space-y-3"
            >
                <template #item="{ element, index }">
                    <LessonItemCard
                        :item="element"
                        :type-meta="typeMetaFor(element.type)"
                        :scripture-books="scriptureBooks"
                        :highlight="element._uid === highlightedUid"
                        @remove="removeChild(index)"
                    />
                </template>
            </draggable>

            <p v-if="!group.children.length" class="py-1 text-center text-xs text-amber-700/70">
                Drag items here, or add one below.
            </p>

            <!-- Add a block into this group -->
            <div class="flex flex-wrap gap-2 border-t border-amber-200/70 pt-3">
                <button
                    v-for="type in itemTypes"
                    :key="type.value"
                    type="button"
                    @click="addChild(type.value)"
                    class="rounded-md border border-amber-200 bg-white px-2.5 py-1 text-xs font-medium text-stone-600 hover:border-amber-400 hover:text-amber-700"
                >
                    + {{ type.label }}
                </button>
            </div>
        </div>
    </div>
</template>
