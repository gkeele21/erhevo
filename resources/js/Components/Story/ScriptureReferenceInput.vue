<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    // Rows of { start_chapter_id, start_verse, end_chapter_id, end_verse, reference }
    modelValue: {
        type: Array,
        default: () => []
    },
    scriptureBooks: {
        type: Array,
        default: () => []
    },
})

const emit = defineEmits(['update:modelValue'])

const adding = ref(false)
const bookId = ref('')
const startChapterId = ref('')
const startVerse = ref('')
const endChapterId = ref('')
const endVerse = ref('')

const allBooks = computed(() => props.scriptureBooks.flatMap((v) => v.books))
const selectedBook = computed(() => allBooks.value.find((b) => b.id === Number(bookId.value)))
const chapters = computed(() => selectedBook.value?.chapters ?? [])

const startChapter = computed(() => chapters.value.find((c) => c.id === Number(startChapterId.value)))
// With no end chapter chosen ("Same chapter") the range stays inside the start
// chapter, so the end verse options come from there.
const endChapter = computed(() =>
    endChapterId.value
        ? chapters.value.find((c) => c.id === Number(endChapterId.value))
        : startChapter.value)

const startVerseOptions = computed(() =>
    Array.from({ length: startChapter.value?.verse_count ?? 0 }, (_, i) => i + 1))

const endChapterOptions = computed(() => {
    if (!startChapter.value) return []
    return chapters.value.filter((c) => c.number >= startChapter.value.number)
})

const sameChapter = computed(() =>
    !endChapterId.value || Number(endChapterId.value) === Number(startChapterId.value))

const endVerseOptions = computed(() => {
    const all = Array.from({ length: endChapter.value?.verse_count ?? 0 }, (_, i) => i + 1)
    if (sameChapter.value && startVerse.value) {
        return all.filter((v) => v >= Number(startVerse.value))
    }
    return all
})

// Mirrors PostScriptureReference::getDisplayReferenceAttribute() so the chip
// reads the same before and after a save.
const buildReference = () => {
    const book = selectedBook.value?.name
    const start = startChapter.value?.number
    if (!book || !start) return ''

    const sv = startVerse.value ? Number(startVerse.value) : null
    const ec = sameChapter.value ? null : endChapter.value?.number
    const ev = endVerse.value ? Number(endVerse.value) : null

    if (sv && !ec && !ev) return `${book} ${start}:${sv}`
    if (sv && !ec && ev) return `${book} ${start}:${sv}-${ev}`
    if (!sv && !ec) return `${book} ${start}`
    if (!sv && ec && !ev) return `${book} ${start}-${ec}`
    if (sv && ec && ev) return `${book} ${start}:${sv}-${ec}:${ev}`
    return `${book} ${start}`
}

const canAdd = computed(() => Boolean(bookId.value && startChapterId.value))

const resetForm = () => {
    bookId.value = ''
    startChapterId.value = ''
    startVerse.value = ''
    endChapterId.value = ''
    endVerse.value = ''
}

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

const addReference = () => {
    if (!canAdd.value) return

    add({
        start_chapter_id: Number(startChapterId.value),
        start_verse: startVerse.value ? Number(startVerse.value) : null,
        end_chapter_id: sameChapter.value ? null : Number(endChapterId.value),
        end_verse: endVerse.value ? Number(endVerse.value) : null,
        reference: buildReference(),
    })

    resetForm()
    adding.value = false
}

// Shared by the manual picker and the AI suggestions, which already come back
// from /api/ai/suggest-scriptures with resolved chapter ids.
const add = (row) => {
    const exists = props.modelValue.some((r) =>
        r.start_chapter_id === row.start_chapter_id &&
        (r.start_verse ?? null) === (row.start_verse ?? null) &&
        (r.end_chapter_id ?? null) === (row.end_chapter_id ?? null) &&
        (r.end_verse ?? null) === (row.end_verse ?? null))

    if (exists) return
    emit('update:modelValue', [...props.modelValue, row])
}

const remove = (index) => {
    emit('update:modelValue', props.modelValue.filter((_, i) => i !== index))
}

defineExpose({ add })
</script>

<template>
    <div class="space-y-3">
        <div>
            <label class="block text-sm font-medium text-amber-900">Scriptures this post is about</label>
            <p class="mt-0.5 text-xs text-amber-800">
                Linking verses lets this post resurface when you or your friends study the same passage.
            </p>
        </div>

        <!-- Current references -->
        <div v-if="modelValue.length" class="flex flex-wrap gap-2">
            <span
                v-for="(ref, index) in modelValue"
                :key="`${ref.start_chapter_id}-${ref.start_verse}-${ref.end_chapter_id}-${ref.end_verse}`"
                class="inline-flex items-center gap-1.5 rounded-full border border-amber-300 bg-white px-3 py-1 text-sm text-amber-900"
            >
                {{ ref.reference }}
                <button
                    type="button"
                    class="text-amber-500 hover:text-amber-800"
                    :aria-label="`Remove ${ref.reference}`"
                    @click="remove(index)"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </span>
        </div>
        <p v-else class="text-sm italic text-amber-700">No scriptures linked yet.</p>

        <!-- Add form -->
        <div v-if="adding" class="space-y-3 rounded-lg border border-amber-300 bg-white p-3">
            <div>
                <label class="block text-xs font-medium text-stone-600">Book</label>
                <select
                    v-model="bookId"
                    class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                    @change="onBookChange"
                >
                    <option value="">Choose a book...</option>
                    <optgroup v-for="vol in scriptureBooks" :key="vol.name" :label="vol.name">
                        <option v-for="book in vol.books" :key="book.id" :value="book.id">{{ book.name }}</option>
                    </optgroup>
                </select>
            </div>

            <div v-if="selectedBook" class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-stone-600">Chapter</label>
                    <select
                        v-model="startChapterId"
                        class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                        @change="onStartChapterChange"
                    >
                        <option value="">Choose...</option>
                        <option v-for="c in chapters" :key="c.id" :value="c.id">{{ c.number }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-stone-600">Verse (optional)</label>
                    <select
                        v-model="startVerse"
                        :disabled="!startChapter"
                        class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-amber-500 focus:ring-amber-500 disabled:bg-stone-100"
                    >
                        <option value="">Whole chapter</option>
                        <option v-for="v in startVerseOptions" :key="v" :value="v">{{ v }}</option>
                    </select>
                </div>
            </div>

            <div v-if="startChapter" class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-stone-600">Through chapter</label>
                    <select
                        v-model="endChapterId"
                        class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                    >
                        <option value="">Same chapter</option>
                        <option v-for="c in endChapterOptions" :key="c.id" :value="c.id">{{ c.number }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-stone-600">Through verse</label>
                    <select
                        v-model="endVerse"
                        class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                    >
                        <option value="">{{ startVerse ? 'Just that verse' : 'End of chapter' }}</option>
                        <option v-for="v in endVerseOptions" :key="v" :value="v">{{ v }}</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 pt-1">
                <span class="text-sm font-medium text-stone-700">{{ buildReference() || '—' }}</span>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-sm text-stone-600 hover:bg-stone-100"
                        @click="adding = false; resetForm()"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="!canAdd"
                        class="rounded-lg bg-amber-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50"
                        @click="addReference"
                    >
                        Add
                    </button>
                </div>
            </div>
        </div>

        <button
            v-else
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-sm font-medium text-amber-800 hover:bg-amber-50"
            @click="adding = true"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Link a scripture
        </button>
    </div>
</template>
