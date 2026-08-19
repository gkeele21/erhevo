<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    // The author currently filtered on: { id, name } or null.
    selected: { type: Object, default: null }
})

const emit = defineEmits(['select', 'clear'])

const term = ref('')
const results = ref([])
const searching = ref(false)
const open = ref(false)

// Authors number in the thousands, so they're searched on the server rather
// than shipped with the page.
let debounceTimer = null
let requestId = 0

watch(term, (value) => {
    clearTimeout(debounceTimer)
    const query = value.trim()

    if (query.length < 2) {
        results.value = []
        open.value = false

        return
    }

    debounceTimer = setTimeout(async () => {
        const thisRequest = ++requestId
        searching.value = true

        try {
            const response = await fetch(
                `${route('talks.authors.search')}?q=${encodeURIComponent(query)}`,
                { headers: { Accept: 'application/json' } }
            )
            const data = await response.json()

            // Ignore a slow response that a newer keystroke has superseded.
            if (thisRequest === requestId) {
                results.value = Array.isArray(data) ? data : []
                open.value = true
            }
        } catch {
            if (thisRequest === requestId) {
                results.value = []
            }
        } finally {
            if (thisRequest === requestId) {
                searching.value = false
            }
        }
    }, 300)
})

const choose = (author) => {
    term.value = ''
    results.value = []
    open.value = false
    emit('select', author)
}

const clear = () => {
    term.value = ''
    results.value = []
    open.value = false
    emit('clear')
}
</script>

<template>
    <div class="relative">
        <!-- A chosen author reads as a chip, so the filter is obvious at a glance -->
        <div
            v-if="selected"
            class="flex items-center justify-between gap-2 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2"
        >
            <span class="text-sm font-medium text-amber-900 truncate">{{ selected.name }}</span>
            <button
                type="button"
                class="shrink-0 text-xs text-amber-700 hover:text-amber-900 underline"
                @click="clear"
            >
                Change
            </button>
        </div>

        <template v-else>
            <input
                v-model="term"
                type="text"
                placeholder="Any speaker — type 2+ letters"
                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                @focus="open = results.length > 0"
            >

            <ul
                v-if="open && (results.length || !searching)"
                class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto rounded-lg border border-stone-200 bg-white shadow-lg divide-y divide-stone-100"
            >
                <li v-for="author in results" :key="author.id">
                    <button
                        type="button"
                        class="block w-full px-3 py-2 text-left text-sm text-stone-700 hover:bg-amber-50"
                        @click="choose(author)"
                    >
                        {{ author.name }}
                        <span v-if="author.calling" class="text-stone-400"> &middot; {{ author.calling }}</span>
                    </button>
                </li>
                <li v-if="!results.length" class="px-3 py-2 text-sm text-stone-500">
                    No authors match that.
                </li>
            </ul>
        </template>
    </div>
</template>
