<script setup>
import { computed, inject, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import TagInput from '@/Components/Story/TagInput.vue'
import AuthorSelect from '@/Components/Story/AuthorSelect.vue'

// Church callings for the calling picker, provided by the lesson builder.
const churchCallings = inject('lessonChurchCallings', [])

// The talk library is LDS content — only offer the lookup when it's enabled.
const page = usePage()
const ldsEnabled = computed(() => !!page.props.userSettings?.show_lds_content)

// Mutates the lesson item directly (matching the rest of the builder): sets
// item.post_id (the FK), item.content (an editable/highlightable copy of the
// quote text), and item.config (a display snapshot of the source quote).
const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
})

if (!props.item.config) props.item.config = {}

const mode = ref('search') // 'search' | 'create'

// --- Search existing quotes ---
const query = ref('')
const results = ref([])
const searching = ref(false)
const open = ref(false)
let debounceTimer = null

const runSearch = () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(async () => {
        searching.value = true
        try {
            const { data } = await axios.get(route('lessons.quote-search'), { params: { q: query.value.trim() } })
            results.value = data
            open.value = true
        } catch (e) {
            results.value = []
        } finally {
            searching.value = false
        }
    }, 300)
}

const attach = (quote) => {
    props.item.post_id = quote.post_id
    props.item.content = quote.content || ''
    props.item.config = {
        post_slug: quote.slug,
        source_title: quote.title,
        author: quote.author,
        church_calling: quote.church_calling,
        date_given: quote.date_given,
        tags: quote.tags || [],
    }
    query.value = ''
    results.value = []
    open.value = false
}

const clear = () => {
    props.item.post_id = null
    props.item.content = ''
    props.item.config = {}
    selectedTalk.value = null
    mode.value = 'search'
}

// --- Create a new quote inline ---
const emptyForm = () => ({ content: '', title: '', author: '', author_id: null, church_calling_id: '', date_given: '', tags: [] })
const form = ref(emptyForm())
const saving = ref(false)
const createError = ref('')

const createQuote = async () => {
    if (!form.value.content.trim()) {
        createError.value = 'Enter the quote text.'
        return
    }
    saving.value = true
    createError.value = ''
    try {
        const { data } = await axios.post(route('lessons.quote-store'), {
            content: form.value.content,
            title: form.value.title || null,
            author: form.value.author || null,
            author_id: form.value.author_id || null,
            church_calling_id: form.value.church_calling_id || null,
            date_given: form.value.date_given || null,
            tags: form.value.tags,
        })
        attach(data)
        form.value = emptyForm()
        selectedTalk.value = null
        mode.value = 'search'
    } catch (e) {
        createError.value = e.response?.data?.message || 'Could not save the quote.'
    } finally {
        saving.value = false
    }
}

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

// --- Talk library lookup (to source the quote) ---
const showTalkSearch = ref(false)
const talk = ref({ q: '', year: '', month: '' })
const talkResults = ref([])
const talkSearching = ref(false)
let talkDebounce = null

const hasTalkFilter = () => !!(talk.value.q || talk.value.year || talk.value.month)

const searchTalkLibrary = () => {
    clearTimeout(talkDebounce)
    if (!hasTalkFilter()) {
        talkResults.value = []
        return
    }
    talkDebounce = setTimeout(async () => {
        talkSearching.value = true
        try {
            const { data } = await axios.get(route('lessons.talk-library-search'), {
                params: {
                    q: talk.value.q || undefined,
                    year: talk.value.year || undefined,
                    month: talk.value.month || undefined,
                    author: form.value.author || undefined,
                    author_id: form.value.author_id || undefined,
                },
            })
            talkResults.value = data
        } catch (e) {
            talkResults.value = []
        } finally {
            talkSearching.value = false
        }
    }, 300)
}

// The talk chosen as the source — kept so we can offer an "open to copy text" link.
const selectedTalk = ref(null)

const pickTalk = (t) => {
    selectedTalk.value = t
    form.value.title = t.title
    if (t.date) form.value.date_given = t.date
    if (t.church_calling_id) form.value.church_calling_id = t.church_calling_id
    talkResults.value = []
    showTalkSearch.value = false
}

const clearSource = () => {
    selectedTalk.value = null
    showTalkSearch.value = true
}
</script>

<template>
    <div class="space-y-3">
        <!-- Selected quote -->
        <div
            v-if="item.post_id"
            class="flex items-start justify-between gap-3 rounded-lg border border-stone-200 bg-stone-50 p-3"
        >
            <div class="min-w-0">
                <p v-if="item.config.source_title" class="font-medium text-stone-800">{{ item.config.source_title }}</p>
                <p class="text-sm text-stone-500">
                    <span v-if="item.config.author">{{ item.config.author }}</span>
                    <span v-if="item.config.church_calling" class="text-stone-400"> ({{ item.config.church_calling }})</span>
                    <span v-if="item.config.author && item.config.date_given"> · </span>
                    <span v-if="item.config.date_given">{{ formatDate(item.config.date_given) }}</span>
                </p>
                <div v-if="item.config.tags && item.config.tags.length" class="mt-1 flex flex-wrap gap-1">
                    <span
                        v-for="tag in item.config.tags"
                        :key="tag"
                        class="rounded bg-amber-100 px-1.5 py-0.5 text-xs text-amber-800"
                    >#{{ tag }}</span>
                </div>
                <a
                    v-if="item.config.post_slug"
                    :href="route('posts.show', item.config.post_slug)"
                    target="_blank"
                    rel="noopener"
                    class="mt-1 inline-block text-xs text-amber-600 underline hover:text-amber-800"
                >View source quote</a>
            </div>
            <button type="button" @click="clear" class="flex-shrink-0 text-sm text-stone-400 hover:text-stone-600">
                Change
            </button>
        </div>

        <template v-else>
            <!-- Search / Create toggle -->
            <div class="inline-flex rounded-lg border border-stone-200 p-0.5">
                <button
                    type="button"
                    @click="mode = 'search'"
                    class="rounded-md px-3 py-1 text-sm"
                    :class="mode === 'search' ? 'bg-amber-100 text-amber-800' : 'text-stone-500'"
                >
                    Find a quote
                </button>
                <button
                    type="button"
                    @click="mode = 'create'; open = false"
                    class="rounded-md px-3 py-1 text-sm"
                    :class="mode === 'create' ? 'bg-amber-100 text-amber-800' : 'text-stone-500'"
                >
                    New quote
                </button>
            </div>

            <!-- Search existing -->
            <div v-if="mode === 'search'" class="relative">
                <input
                    v-model="query"
                    @input="runSearch"
                    @focus="runSearch"
                    type="text"
                    class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                    placeholder="Search your quotes by text, author, or tag..."
                >
                <span v-if="searching" class="absolute right-3 top-2.5 text-xs text-stone-400">Searching...</span>

                <ul
                    v-if="open && results.length"
                    class="absolute z-20 mt-1 max-h-72 w-full overflow-auto rounded-lg border border-stone-200 bg-white shadow-lg"
                >
                    <li
                        v-for="quote in results"
                        :key="quote.post_id"
                        @click="attach(quote)"
                        class="cursor-pointer border-b border-stone-100 px-3 py-2 last:border-0 hover:bg-amber-50"
                    >
                        <p class="text-sm font-medium text-stone-800">{{ quote.title }}</p>
                        <p class="text-xs text-stone-500">
                            <span v-if="quote.author">{{ quote.author }}</span>
                            <span v-if="quote.author && quote.date_given"> · </span>
                            <span v-if="quote.date_given">{{ formatDate(quote.date_given) }}</span>
                        </p>
                    </li>
                </ul>
                <p v-else-if="open && !searching" class="mt-1 text-xs text-stone-400">
                    No quotes found. Try “New quote”.
                </p>
            </div>

            <!-- Create new -->
            <div v-else class="space-y-3 rounded-lg border border-stone-200 p-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Quote</label>
                    <textarea
                        v-model="form.content"
                        rows="4"
                        class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        placeholder="Paste or type the quote text here..."
                    ></textarea>
                    <p class="mt-1 text-xs text-stone-400">Type the quote, or find a talk below — open it, copy the passage you want, and paste it here.</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Author (optional)</label>
                    <AuthorSelect
                        v-model="form.author_id"
                        v-model:name="form.author"
                        placeholder="Who said it? Search or add a new author..."
                    />
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-stone-700">Calling when given (optional)</label>
                        <select
                            v-model="form.church_calling_id"
                            class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        >
                            <option value="">— None —</option>
                            <option v-for="c in churchCallings" :key="c.id" :value="c.id">{{ c.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-stone-700">Date given (optional)</label>
                        <input
                            v-model="form.date_given"
                            type="date"
                            class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        >
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Source / title (optional)</label>
                    <input
                        v-model="form.title"
                        type="text"
                        class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        placeholder="e.g. the talk or book it's from"
                    >
                    <button
                        v-if="ldsEnabled"
                        type="button"
                        @click="showTalkSearch = !showTalkSearch"
                        class="mt-1 text-xs text-amber-600 underline hover:text-amber-800"
                    >
                        {{ showTalkSearch ? 'Hide talk library search' : 'Find the source in the talk library' }}
                    </button>

                    <!-- Talk library lookup -->
                    <div v-if="ldsEnabled && showTalkSearch" class="mt-2 space-y-2 rounded-lg border border-stone-200 bg-stone-50 p-3">
                        <div class="grid gap-2 sm:grid-cols-3">
                            <input
                                v-model="talk.q"
                                @input="searchTalkLibrary"
                                type="text"
                                class="rounded-lg border-stone-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                                placeholder="Title contains…"
                            >
                            <input
                                v-model="talk.year"
                                @input="searchTalkLibrary"
                                type="number"
                                class="rounded-lg border-stone-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                                placeholder="Year"
                            >
                            <select
                                v-model="talk.month"
                                @change="searchTalkLibrary"
                                class="rounded-lg border-stone-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                            >
                                <option value="">Any session</option>
                                <option value="4">April</option>
                                <option value="10">October</option>
                            </select>
                        </div>
                        <p v-if="form.author" class="text-xs text-stone-500">Filtered to author: <span class="font-medium">{{ form.author }}</span></p>
                        <span v-if="talkSearching" class="text-xs text-stone-400">Searching…</span>

                        <ul v-if="talkResults.length" class="max-h-56 overflow-auto rounded-lg border border-stone-200 bg-white">
                            <li
                                v-for="t in talkResults"
                                :key="t.id"
                                class="flex items-start justify-between gap-2 border-b border-stone-100 px-3 py-2 last:border-0 hover:bg-amber-50"
                            >
                                <button type="button" @click="pickTalk(t)" class="min-w-0 flex-1 text-left">
                                    <p class="text-sm font-medium text-stone-800">{{ t.title }}</p>
                                    <p class="text-xs text-stone-500">{{ t.speaker }}<span v-if="t.date"> · {{ formatDate(t.date) }}</span></p>
                                </button>
                                <a v-if="t.url" :href="t.url" target="_blank" rel="noopener" class="mt-0.5 flex-shrink-0 text-xs text-amber-600 hover:text-amber-800">Open ↗</a>
                            </li>
                        </ul>
                        <p v-else-if="hasTalkFilter() && !talkSearching" class="text-xs text-stone-400">No talks found.</p>
                    </div>

                    <!-- Chosen source talk: open it to copy the passage -->
                    <div v-if="selectedTalk" class="mt-2 rounded-lg border border-amber-200 bg-amber-50 p-3">
                        <p class="text-sm font-medium text-stone-800">{{ selectedTalk.title }}</p>
                        <p class="text-xs text-stone-500">{{ selectedTalk.speaker }}<span v-if="selectedTalk.date"> · {{ formatDate(selectedTalk.date) }}</span></p>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <a
                                v-if="selectedTalk.url"
                                :href="selectedTalk.url"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-1 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-700"
                            >
                                Open talk to copy text ↗
                            </a>
                            <button type="button" @click="clearSource" class="text-xs text-stone-500 hover:text-stone-700">Change source</button>
                        </div>
                        <p class="mt-1 text-xs text-stone-400">Opens the talk on ChurchofJesusChrist.org — copy the passage you want and paste it into the Quote box above.</p>
                    </div>
                </div>
                <TagInput v-model="form.tags" :content="form.content" />
                <p v-if="createError" class="text-xs text-red-600">{{ createError }}</p>
                <button
                    type="button"
                    @click="createQuote"
                    :disabled="saving"
                    class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50"
                >
                    {{ saving ? 'Saving...' : 'Save & add quote' }}
                </button>
            </div>
        </template>
    </div>
</template>
