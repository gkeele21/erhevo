<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
    // The item's config: { book_id, chapter_id, chapter_number, book_name,
    //                      start_verse, end_verse, reference }
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

// Flat list of books with their volume name for the <select> (grouped).
const volumes = computed(() => props.scriptureBooks)

const selectedBookId = ref(config.value.book_id ?? '')
const selectedChapterId = ref(config.value.chapter_id ?? '')
const startVerse = ref(config.value.start_verse ?? '')
const endVerse = ref(config.value.end_verse ?? '')
const loading = ref(false)

const allBooks = computed(() => volumes.value.flatMap((v) => v.books))

const selectedBook = computed(() => allBooks.value.find((b) => b.id === Number(selectedBookId.value)))
const chapters = computed(() => selectedBook.value?.chapters ?? [])
const selectedChapter = computed(() => chapters.value.find((c) => c.id === Number(selectedChapterId.value)))
const verseCount = computed(() => selectedChapter.value?.verse_count ?? 0)
const verseOptions = computed(() => Array.from({ length: verseCount.value }, (_, i) => i + 1))

const onBookChange = () => {
    selectedChapterId.value = ''
    startVerse.value = ''
    endVerse.value = ''
}

const onChapterChange = () => {
    startVerse.value = ''
    endVerse.value = ''
}

const fetchPassage = async () => {
    if (!selectedChapterId.value) return

    loading.value = true
    try {
        const { data } = await axios.get(route('lessons.scripture-text'), {
            params: {
                chapter_id: selectedChapterId.value,
                start_verse: startVerse.value || null,
                end_verse: endVerse.value || null,
            },
        })

        emit('update:modelValue', {
            book_id: selectedBook.value?.id ?? null,
            book_name: selectedBook.value?.name ?? null,
            chapter_id: Number(selectedChapterId.value),
            chapter_number: selectedChapter.value?.number ?? null,
            start_verse: startVerse.value ? Number(startVerse.value) : null,
            end_verse: endVerse.value ? Number(endVerse.value) : null,
            reference: data.reference,
        })
        emit('update:passage', data.text)
    } finally {
        loading.value = false
    }
}

// Keep end_verse from dropping below start_verse.
watch(startVerse, (val) => {
    if (val && endVerse.value && Number(endVerse.value) < Number(val)) {
        endVerse.value = val
    }
})
</script>

<template>
    <div class="space-y-3">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <!-- Book -->
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Book</label>
                <select
                    v-model="selectedBookId"
                    @change="onBookChange"
                    class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                >
                    <option value="">Select a book...</option>
                    <optgroup v-for="vol in volumes" :key="vol.name" :label="vol.name">
                        <option v-for="book in vol.books" :key="book.id" :value="book.id">
                            {{ book.name }}
                        </option>
                    </optgroup>
                </select>
            </div>

            <!-- Chapter -->
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Chapter</label>
                <select
                    v-model="selectedChapterId"
                    @change="onChapterChange"
                    :disabled="!selectedBook"
                    class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500 disabled:bg-stone-100"
                >
                    <option value="">Chapter...</option>
                    <option v-for="ch in chapters" :key="ch.id" :value="ch.id">{{ ch.number }}</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <!-- Verse from -->
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">From verse (optional)</label>
                <select
                    v-model="startVerse"
                    :disabled="!selectedChapter"
                    class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500 disabled:bg-stone-100"
                >
                    <option value="">Whole chapter</option>
                    <option v-for="n in verseOptions" :key="n" :value="n">{{ n }}</option>
                </select>
            </div>

            <!-- Verse to -->
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">To verse (optional)</label>
                <select
                    v-model="endVerse"
                    :disabled="!startVerse"
                    class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500 disabled:bg-stone-100"
                >
                    <option value="">Single verse</option>
                    <option v-for="n in verseOptions.filter((v) => v >= Number(startVerse))" :key="n" :value="n">
                        {{ n }}
                    </option>
                </select>
            </div>

            <!-- Fetch -->
            <div class="flex items-end">
                <button
                    type="button"
                    @click="fetchPassage"
                    :disabled="!selectedChapterId || loading"
                    class="w-full rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50"
                >
                    {{ loading ? 'Loading...' : 'Insert passage' }}
                </button>
            </div>
        </div>

        <p v-if="config.reference" class="text-sm font-medium text-amber-700">{{ config.reference }}</p>

        <!-- Editable passage: trim/keep what you want -->
        <div>
            <label class="mb-1 block text-xs font-medium text-stone-600">
                Passage text (edit freely to keep only what you want)
            </label>
            <textarea
                :value="passage"
                @input="emit('update:passage', $event.target.value)"
                rows="6"
                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                placeholder="Pick a book, chapter, and verses above, then Insert passage. You can edit the text here."
            ></textarea>
        </div>
    </div>
</template>
