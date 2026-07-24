<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import StoryEditor from '@/Components/Story/StoryEditor.vue'

// Convert the plain text returned by the endpoint (verse-number prefixed,
// newline separated) into simple HTML so it can be edited as rich text.
const textToHtml = (text) => {
    if (!text) return ''
    const escape = (s) => s
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
    return '<p>' + text.split('\n').map(escape).join('<br>') + '</p>'
}

const props = defineProps({
    // The item's config: { book_id, book_name, start_chapter_id, start_chapter_number,
    //   end_chapter_id, end_chapter_number, start_verse, end_verse, reference }
    modelValue: {
        type: Object,
        default: () => ({})
    },
    // The (possibly edited) passage text lives on the item's content.
    passage: {
        type: String,
        default: ''
    },
    scriptureBooks: {
        type: Array,
        default: () => []
    },
})

const emit = defineEmits(['update:modelValue', 'update:passage'])

const config = computed(() => props.modelValue || {})

const selectedBookId = ref(config.value.book_id ?? '')
const startChapterId = ref(config.value.start_chapter_id ?? '')
const startVerse = ref(config.value.start_verse ?? '')
const endChapterId = ref(config.value.end_chapter_id ?? '')
const endVerse = ref(config.value.end_verse ?? '')
const loading = ref(false)

const allBooks = computed(() => props.scriptureBooks.flatMap((v) => v.books))
const selectedBook = computed(() => allBooks.value.find((b) => b.id === Number(selectedBookId.value)))
const chapters = computed(() => selectedBook.value?.chapters ?? [])

const startChapter = computed(() => chapters.value.find((c) => c.id === Number(startChapterId.value)))
// When no end chapter is chosen ("Same chapter"), the range stays within the
// start chapter — so verse options come from the start chapter.
const endChapter = computed(() =>
    endChapterId.value
        ? chapters.value.find((c) => c.id === Number(endChapterId.value))
        : startChapter.value)

const startVerseOptions = computed(() =>
    Array.from({ length: startChapter.value?.verse_count ?? 0 }, (_, i) => i + 1))

// The end chapter can be the start chapter or any later chapter in the same book.
const endChapterOptions = computed(() => {
    if (!startChapter.value) return []
    return chapters.value.filter((c) => c.number >= startChapter.value.number)
})

const endVerseOptions = computed(() =>
    Array.from({ length: endChapter.value?.verse_count ?? 0 }, (_, i) => i + 1))

// When start and end are the same chapter, the end verse can't precede the start verse.
const sameChapter = computed(() =>
    !endChapterId.value || Number(endChapterId.value) === Number(startChapterId.value))
const filteredEndVerseOptions = computed(() => {
    if (sameChapter.value && startVerse.value) {
        return endVerseOptions.value.filter((v) => v >= Number(startVerse.value))
    }
    return endVerseOptions.value
})

const onBookChange = () => {
    startChapterId.value = ''
    startVerse.value = ''
    endChapterId.value = ''
    endVerse.value = ''
}

const onStartChapterChange = () => {
    startVerse.value = ''
    endChapterId.value = ''
    endVerse.value = ''
}

const onEndChapterChange = () => {
    endVerse.value = ''
}

const fetchPassage = async () => {
    if (!startChapterId.value) return

    // Within a single chapter, an empty end verse means "Single verse" — the
    // endpoint treats a null end_verse as "to the end of the chapter", so pin
    // it to the start verse. (Cross-chapter, empty means "End of chapter".)
    const effectiveEndVerse = endVerse.value
        || (sameChapter.value && startVerse.value ? startVerse.value : null)

    loading.value = true
    try {
        const { data } = await axios.get(route('lessons.scripture-text'), {
            params: {
                start_chapter_id: startChapterId.value,
                start_verse: startVerse.value || null,
                end_chapter_id: endChapterId.value || null,
                end_verse: effectiveEndVerse,
            },
        })

        emit('update:modelValue', {
            book_id: selectedBook.value?.id ?? null,
            book_name: selectedBook.value?.name ?? null,
            start_chapter_id: Number(startChapterId.value),
            start_chapter_number: startChapter.value?.number ?? null,
            end_chapter_id: endChapterId.value ? Number(endChapterId.value) : null,
            end_chapter_number: endChapter.value?.number ?? null,
            start_verse: startVerse.value ? Number(startVerse.value) : null,
            end_verse: effectiveEndVerse ? Number(effectiveEndVerse) : null,
            reference: data.reference,
        })
        emit('update:passage', textToHtml(data.text))
    } finally {
        loading.value = false
    }
}

watch(startVerse, (val) => {
    if (sameChapter.value && val && endVerse.value && Number(endVerse.value) < Number(val)) {
        endVerse.value = val
    }
})
</script>

<template>
    <div class="space-y-3">
        <!-- Book -->
        <div>
            <label class="mb-1 block text-xs font-medium text-stone-600">Book</label>
            <select
                v-model="selectedBookId"
                @change="onBookChange"
                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
            >
                <option value="">Select a book...</option>
                <optgroup v-for="vol in scriptureBooks" :key="vol.name" :label="vol.name">
                    <option v-for="book in vol.books" :key="book.id" :value="book.id">
                        {{ book.name }}
                    </option>
                </optgroup>
            </select>
        </div>

        <!-- From -->
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">From chapter</label>
                <select
                    v-model="startChapterId"
                    @change="onStartChapterChange"
                    :disabled="!selectedBook"
                    class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500 disabled:bg-stone-100"
                >
                    <option value="">Chapter...</option>
                    <option v-for="ch in chapters" :key="ch.id" :value="ch.id">{{ ch.number }}</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">From verse (optional)</label>
                <select
                    v-model="startVerse"
                    :disabled="!startChapter"
                    class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500 disabled:bg-stone-100"
                >
                    <option value="">Whole chapter</option>
                    <option v-for="n in startVerseOptions" :key="n" :value="n">{{ n }}</option>
                </select>
            </div>
        </div>

        <!-- To (optional) -->
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">To chapter (optional)</label>
                <select
                    v-model="endChapterId"
                    @change="onEndChapterChange"
                    :disabled="!startChapter"
                    class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500 disabled:bg-stone-100"
                >
                    <option value="">Same chapter</option>
                    <option v-for="ch in endChapterOptions" :key="ch.id" :value="ch.id">{{ ch.number }}</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">To verse (optional)</label>
                <select
                    v-model="endVerse"
                    :disabled="!startChapter || (sameChapter && !startVerse)"
                    class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500 disabled:bg-stone-100"
                >
                    <option value="">{{ sameChapter ? 'Single verse' : 'End of chapter' }}</option>
                    <option v-for="n in filteredEndVerseOptions" :key="n" :value="n">{{ n }}</option>
                </select>
            </div>
        </div>

        <div>
            <button
                type="button"
                @click="fetchPassage"
                :disabled="!startChapterId || loading"
                class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50"
            >
                {{ loading ? 'Loading...' : 'Insert passage' }}
            </button>
        </div>

        <p v-if="config.reference" class="text-sm font-medium text-amber-700">{{ config.reference }}</p>

        <!-- Editable passage: trim/keep what you want, bold, highlight, etc. -->
        <div>
            <label class="mb-1 block text-xs font-medium text-stone-600">
                Passage text (edit freely — bold or highlight the parts you want to emphasize)
            </label>
            <StoryEditor
                :model-value="passage"
                @update:model-value="emit('update:passage', $event)"
                placeholder="Pick a book, chapter, and verses above, then Insert passage. You can edit and highlight the text here."
            />
        </div>
    </div>
</template>
