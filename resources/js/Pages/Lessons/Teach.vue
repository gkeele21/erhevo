<script setup>
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import LessonItemDisplay from '@/Components/Lesson/LessonItemDisplay.vue'

const props = defineProps({
    lesson: Object,
})

// Ids of every individual element (loose items + group children).
const allElementIds = computed(() => {
    const ids = []
    for (const item of props.lesson.items) {
        if (item.type === 'group') {
            for (const child of item.children || []) ids.push(child.id)
        } else {
            ids.push(item.id)
        }
    }
    return ids
})

// Elements start expanded; we track which ones are collapsed.
const collapsed = ref(new Set())

const isCollapsed = (id) => collapsed.value.has(id)

function toggle(id) {
    if (collapsed.value.has(id)) collapsed.value.delete(id)
    else collapsed.value.add(id)
}

function expandAll() {
    collapsed.value = new Set()
}

function collapseAll() {
    collapsed.value = new Set(allElementIds.value)
}

const allCollapsed = computed(
    () => allElementIds.value.length > 0 && collapsed.value.size >= allElementIds.value.length
)

// Human label for an element's type, always shown in its header.
function typeLabel(item) {
    switch (item.type) {
        case 'scripture': return 'Scripture'
        case 'talk': return 'Talk'
        case 'quote': return 'Quote'
        case 'video': return 'Video'
        case 'image': return 'Image'
        case 'text': return 'My Words'
        case 'question': return 'Question'
        default: return 'Item'
    }
}

// One-line content preview shown when an element is collapsed. Mirrors the
// lesson builder's summary (LessonItemCard.vue) so collapsed items read the same.
const stripHtml = (html) => (html || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()

function summary(item) {
    const c = item.config || {}
    switch (item.type) {
        case 'scripture': return c.reference || 'Scripture reference'
        case 'talk': return c.title || 'Talk'
        case 'quote': return c.source_title || c.author || stripHtml(item.content) || 'Quote'
        case 'video': return c.title || c.filename || c.url || 'Video / link'
        case 'image': return c.caption || c.filename || c.url || 'Image'
        case 'text': return stripHtml(item.content) || 'Empty'
        case 'question': return item.content || 'Question'
        default: return ''
    }
}
</script>

<template>
    <Head :title="`Teaching: ${lesson.title}`" />

    <div class="min-h-screen bg-stone-50">
        <!-- Minimal top bar -->
        <header class="sticky top-0 z-10 border-b border-stone-200 bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-3">
                <h1 class="truncate text-lg font-semibold text-stone-800">{{ lesson.title }}</h1>
                <Link
                    :href="route('lessons.show', lesson.slug)"
                    class="flex-shrink-0 text-sm text-stone-500 hover:text-stone-800"
                >
                    Done
                </Link>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-10">
            <div class="mb-8 flex items-center justify-between gap-4">
                <p v-if="lesson.cfm_week" class="text-sm text-amber-700">
                    Come Follow Me · {{ lesson.cfm_week.title }}
                </p>
                <span v-else></span>
                <button
                    v-if="allElementIds.length"
                    type="button"
                    class="flex-shrink-0 text-sm font-medium text-stone-500 hover:text-stone-800"
                    @click="allCollapsed ? expandAll() : collapseAll()"
                >
                    {{ allCollapsed ? 'Expand all' : 'Collapse all' }}
                </button>
            </div>

            <div v-if="lesson.items.length">
                <template v-for="(item, index) in lesson.items" :key="item.id">
                    <!-- Decorative separator between blocks -->
                    <div v-if="index > 0" class="flex items-center justify-center gap-2 py-10" aria-hidden="true">
                        <span class="h-1 w-1 rounded-full bg-amber-300"></span>
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                        <span class="h-1 w-1 rounded-full bg-amber-300"></span>
                    </div>
                    <!-- Group: a named section with its child items -->
                    <section v-if="item.type === 'group'">
                        <h2 v-if="item.config?.title" class="mb-6 border-b border-stone-200 pb-2 text-2xl font-bold text-stone-800">
                            {{ item.config.title }}
                        </h2>
                        <div class="space-y-6">
                            <div v-for="child in item.children" :key="child.id">
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-2 text-left text-sm font-medium text-stone-500 hover:text-stone-800"
                                    :aria-expanded="!isCollapsed(child.id)"
                                    @click="toggle(child.id)"
                                >
                                    <svg
                                        class="h-4 w-4 flex-shrink-0 transition-transform"
                                        :class="{ '-rotate-90': isCollapsed(child.id) }"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                    <span class="flex-shrink-0 text-stone-700">{{ typeLabel(child) }}</span>
                                    <span v-if="isCollapsed(child.id)" class="truncate font-normal text-stone-400">— {{ summary(child) }}</span>
                                </button>
                                <div v-show="!isCollapsed(child.id)" class="mt-3">
                                    <LessonItemDisplay :item="child" teaching />
                                </div>
                            </div>
                        </div>
                    </section>
                    <!-- Loose item -->
                    <section v-else>
                        <button
                            type="button"
                            class="flex w-full items-center gap-2 text-left text-sm font-medium text-stone-500 hover:text-stone-800"
                            :aria-expanded="!isCollapsed(item.id)"
                            @click="toggle(item.id)"
                        >
                            <svg
                                class="h-4 w-4 flex-shrink-0 transition-transform"
                                :class="{ '-rotate-90': isCollapsed(item.id) }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                            <span class="flex-shrink-0 text-stone-700">{{ typeLabel(item) }}</span>
                            <span v-if="isCollapsed(item.id)" class="truncate font-normal text-stone-400">— {{ summary(item) }}</span>
                        </button>
                        <div v-show="!isCollapsed(item.id)" class="mt-3">
                            <LessonItemDisplay :item="item" teaching />
                        </div>
                    </section>
                </template>
            </div>
            <p v-else class="text-stone-400">This lesson has no content yet.</p>
        </main>
    </div>
</template>
