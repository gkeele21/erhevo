<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    book: Object,
    chapter: Object,
    verses: Array,
    entries: Array,
    prevChapter: Number,
    nextChapter: Number,
})

// Clicking a verse narrows the list to what was written about that verse.
const selectedVerse = ref(null)

const selected = computed(() =>
    props.verses.find((v) => v.number === selectedVerse.value) ?? null)

const shownEntries = computed(() => {
    if (!selected.value) return props.entries
    return props.entries.filter((e) => selected.value.entry_ids.includes(e.id))
})

const toggleVerse = (verse) => {
    if (!verse.entry_ids.length) return
    selectedVerse.value = selectedVerse.value === verse.number ? null : verse.number
}

const kindLabel = {
    post: 'Thought',
    lesson: 'Lesson',
    talk: 'Talk',
}

const kindClass = {
    post: 'bg-teal-50 text-teal-800 border-teal-200',
    lesson: 'bg-amber-50 text-amber-800 border-amber-200',
    talk: 'bg-stone-100 text-stone-700 border-stone-300',
}

const chapterUrl = (n) => route('scriptures.show', { book: props.book.slug, chapter: n })

const stripHtml = (html) => (html || '').replace(/<[^>]*>/g, '').trim()
</script>

<template>
    <AppLayout :title="chapter.reference">
        <Head :title="chapter.reference" />

        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-xl font-semibold leading-tight text-stone-800">{{ chapter.reference }}</h2>
                <Link :href="route('scriptures.index')" class="text-sm text-stone-500 hover:text-stone-800">
                    All scriptures
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Passage -->
                <div class="lg:col-span-2">
                    <div class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                        <p
                            v-for="verse in verses"
                            :key="verse.number"
                            class="group -mx-2 flex gap-3 rounded px-2 py-1.5"
                            :class="[
                                verse.entry_ids.length ? 'cursor-pointer hover:bg-amber-50' : '',
                                selectedVerse === verse.number ? 'bg-amber-50 ring-1 ring-amber-300' : '',
                            ]"
                            @click="toggleVerse(verse)"
                        >
                            <span class="w-6 flex-shrink-0 pt-0.5 text-right text-xs font-semibold text-stone-400">
                                {{ verse.number }}
                            </span>
                            <span class="flex-1 text-stone-800">{{ verse.text }}</span>
                            <span
                                v-if="verse.entry_ids.length"
                                class="flex-shrink-0 self-start rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800"
                                :title="`${verse.entry_ids.length} linked`"
                            >
                                {{ verse.entry_ids.length }}
                            </span>
                        </p>
                    </div>

                    <!-- Chapter paging -->
                    <div class="mt-4 flex items-center justify-between">
                        <Link
                            v-if="prevChapter"
                            :href="chapterUrl(prevChapter)"
                            class="text-sm text-stone-600 hover:text-stone-900"
                        >
                            ← {{ book.name }} {{ prevChapter }}
                        </Link>
                        <span v-else></span>
                        <Link
                            v-if="nextChapter"
                            :href="chapterUrl(nextChapter)"
                            class="text-sm text-stone-600 hover:text-stone-900"
                        >
                            {{ book.name }} {{ nextChapter }} →
                        </Link>
                    </div>
                </div>

                <!-- What's been written -->
                <div>
                    <div class="flex items-baseline justify-between gap-2">
                        <h3 class="font-semibold text-stone-800">
                            {{ selected ? `On verse ${selected.number}` : 'On this chapter' }}
                        </h3>
                        <button
                            v-if="selected"
                            type="button"
                            class="text-xs text-stone-500 underline hover:text-stone-800"
                            @click="selectedVerse = null"
                        >
                            Show all
                        </button>
                    </div>

                    <div v-if="shownEntries.length" class="mt-3 space-y-3">
                        <a
                            v-for="entry in shownEntries"
                            :key="entry.id"
                            :href="entry.url"
                            class="block rounded-lg border border-stone-200 bg-white p-4 shadow-sm hover:border-amber-300 hover:shadow"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="rounded border px-1.5 py-0.5 text-xs font-medium"
                                    :class="kindClass[entry.kind]"
                                >
                                    {{ kindLabel[entry.kind] }}
                                </span>
                                <span class="truncate text-xs text-stone-500">{{ entry.passages.join(', ') }}</span>
                            </div>

                            <p class="mt-2 font-medium text-stone-800">{{ entry.title }}</p>
                            <p v-if="entry.excerpt" class="mt-1 line-clamp-3 text-sm text-stone-600">
                                {{ stripHtml(entry.excerpt) }}
                            </p>
                            <p v-if="entry.by" class="mt-2 text-xs text-stone-500">{{ entry.by }}</p>
                        </a>
                    </div>

                    <p v-else class="mt-3 rounded-lg border border-dashed border-stone-300 p-6 text-center text-sm text-stone-500">
                        <template v-if="selected">
                            Nothing written about verse {{ selected.number }} yet.
                        </template>
                        <template v-else>
                            Nothing written about this chapter yet. Link a scripture when you write a post
                            and it will show up here.
                        </template>
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
