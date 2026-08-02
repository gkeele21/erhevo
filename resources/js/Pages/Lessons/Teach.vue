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

// Block tally by emphasis — the teach header's "time budget" line.
const emphasisCounts = computed(() => {
    const counts = { key: 0, normal: 0, optional: 0 }
    const tally = (item) => {
        const e = item.config?.emphasis
        counts[e === 'key' || e === 'optional' ? e : 'normal']++
    }
    for (const item of props.lesson.items) {
        if (item.type === 'group') (item.children || []).forEach(tally)
        else tally(item)
    }
    return counts
})

const totalBlocks = computed(() =>
    emphasisCounts.value.key + emphasisCounts.value.normal + emphasisCounts.value.optional
)

// --- Skip optionals ('if time' blocks) when the clock is against you ---
const hideOptionals = ref(false)
const isOptional = (item) => item.config?.emphasis === 'optional'

const hasOptionals = computed(() =>
    props.lesson.items.some(item => item.type === 'group'
        ? (item.children || []).some(isOptional)
        : isOptional(item))
)

const visibleItems = computed(() => {
    if (!hideOptionals.value) return props.lesson.items
    return props.lesson.items
        .map(item => item.type === 'group'
            ? { ...item, children: (item.children || []).filter(c => !isOptional(c)) }
            : item)
        .filter(item => item.type === 'group' ? item.children.length > 0 : !isOptional(item))
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
                <div class="min-w-0">
                    <p v-if="lesson.cfm_week" class="text-sm text-amber-700">
                        Come Follow Me · {{ lesson.cfm_week.title }}
                    </p>
                    <p v-if="totalBlocks" class="text-sm text-stone-400">
                        <template v-if="emphasisCounts.key || emphasisCounts.optional">
                            <span v-if="emphasisCounts.key" class="text-amber-600 font-medium">{{ emphasisCounts.key }} key</span>
                            <template v-if="emphasisCounts.key && (emphasisCounts.normal || emphasisCounts.optional)"> · </template>
                            <span v-if="emphasisCounts.normal">{{ emphasisCounts.normal }} normal</span>
                            <template v-if="emphasisCounts.normal && emphasisCounts.optional"> · </template>
                            <span v-if="emphasisCounts.optional">
                                {{ emphasisCounts.optional }} optional<template v-if="hideOptionals"> (hidden)</template>
                            </span>
                        </template>
                        <template v-else>{{ totalBlocks }} {{ totalBlocks === 1 ? 'block' : 'blocks' }}</template>
                    </p>
                </div>
                <div class="flex flex-shrink-0 items-center gap-4">
                    <button
                        v-if="hasOptionals"
                        type="button"
                        class="text-sm font-medium"
                        :class="hideOptionals ? 'text-teal-700 hover:text-teal-900' : 'text-stone-500 hover:text-stone-800'"
                        @click="hideOptionals = !hideOptionals"
                    >
                        {{ hideOptionals ? 'Show optionals' : 'Skip optionals' }}
                    </button>
                    <button
                        v-if="allElementIds.length"
                        type="button"
                        class="text-sm font-medium text-stone-500 hover:text-stone-800"
                        @click="allCollapsed ? expandAll() : collapseAll()"
                    >
                        {{ allCollapsed ? 'Expand all' : 'Collapse all' }}
                    </button>
                </div>
            </div>

            <div v-if="lesson.items.length">
                <template v-for="(item, index) in visibleItems" :key="item.id">
                    <!-- Decorative separator between blocks -->
                    <div v-if="index > 0" class="flex items-center justify-center gap-2 py-10" aria-hidden="true">
                        <span class="h-1 w-1 rounded-full bg-amber-300"></span>
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                        <span class="h-1 w-1 rounded-full bg-amber-300"></span>
                    </div>
                    <!-- Group: a named section with its child items -->
                    <section v-if="item.type === 'group'">
                        <h2 v-if="item.config?.title" class="mb-6 flex items-center justify-between gap-3 border-b border-stone-200 pb-2 text-2xl font-bold text-stone-800">
                            <span>{{ item.config.title }}</span>
                            <a
                                :href="route('lessons.present', [lesson.slug, item.id])"
                                target="_blank"
                                rel="noopener"
                                class="flex-shrink-0 rounded p-1 text-stone-300 hover:text-amber-600"
                                title="Present this whole section on a big screen"
                            ><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12M9 20.25v-3.375m6 3.375v-3.375m-10.5-13.5h15A1.5 1.5 0 0121 4.875v9.75a1.5 1.5 0 01-1.5 1.5h-15a1.5 1.5 0 01-1.5-1.5v-9.75a1.5 1.5 0 011.5-1.5z"/></svg></a>
                        </h2>
                        <div class="space-y-6">
                            <div v-for="child in item.children" :key="child.id">
                                <div class="flex items-center gap-1">
                                <a
                                    :href="route('lessons.present', [lesson.slug, child.id])"
                                    target="_blank"
                                    rel="noopener"
                                    class="flex-shrink-0 rounded p-1 text-stone-300 hover:text-amber-600"
                                    title="Present this on a big screen (opens a new tab)"
                                    @click.stop
                                ><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12M9 20.25v-3.375m6 3.375v-3.375m-10.5-13.5h15A1.5 1.5 0 0121 4.875v9.75a1.5 1.5 0 01-1.5 1.5h-15a1.5 1.5 0 01-1.5-1.5v-9.75a1.5 1.5 0 011.5-1.5z"/></svg></a>
                                <button
                                    type="button"
                                    class="flex min-w-0 flex-1 items-center gap-2 text-left text-sm font-medium text-stone-500 hover:text-stone-800"
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
                                    <svg v-if="child.config?.emphasis === 'key'" class="h-3.5 w-3.5 flex-shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                                    <span v-else-if="child.config?.emphasis === 'optional'" class="flex-shrink-0 text-xs italic text-stone-400">(if time)</span>
                                    <span v-if="isCollapsed(child.id)" class="truncate font-normal text-stone-400">— {{ summary(child) }}</span>
                                </button>
                                </div>
                                <div v-show="!isCollapsed(child.id)" class="mt-3">
                                    <LessonItemDisplay :item="child" teaching />
                                </div>
                            </div>
                        </div>
                    </section>
                    <!-- Loose item -->
                    <section v-else>
                        <div class="flex items-center gap-1">
                        <a
                                    :href="route('lessons.present', [lesson.slug, item.id])"
                                    target="_blank"
                                    rel="noopener"
                                    class="flex-shrink-0 rounded p-1 text-stone-300 hover:text-amber-600"
                                    title="Present this on a big screen (opens a new tab)"
                                    @click.stop
                                ><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12M9 20.25v-3.375m6 3.375v-3.375m-10.5-13.5h15A1.5 1.5 0 0121 4.875v9.75a1.5 1.5 0 01-1.5 1.5h-15a1.5 1.5 0 01-1.5-1.5v-9.75a1.5 1.5 0 011.5-1.5z"/></svg></a>
                        <button
                            type="button"
                            class="flex min-w-0 flex-1 items-center gap-2 text-left text-sm font-medium text-stone-500 hover:text-stone-800"
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
                            <svg v-if="item.config?.emphasis === 'key'" class="h-3.5 w-3.5 flex-shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                                    <span v-else-if="item.config?.emphasis === 'optional'" class="flex-shrink-0 text-xs italic text-stone-400">(if time)</span>
                            <span v-if="isCollapsed(item.id)" class="truncate font-normal text-stone-400">— {{ summary(item) }}</span>
                        </button>
                        </div>
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
