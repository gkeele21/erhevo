<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    // The item's config: { talk_id, title, speaker, url }
    modelValue: {
        type: Object,
        default: () => ({})
    }
})

const emit = defineEmits(['update:modelValue'])

const query = ref('')
const results = ref([])
const searching = ref(false)
const open = ref(false)
let debounceTimer = null

const search = () => {
    clearTimeout(debounceTimer)
    const q = query.value.trim()

    if (q.length < 2) {
        results.value = []
        open.value = false
        return
    }

    debounceTimer = setTimeout(async () => {
        searching.value = true
        try {
            const { data } = await axios.get(route('lessons.talk-search'), { params: { q } })
            results.value = data
            open.value = true
        } catch (e) {
            results.value = []
        } finally {
            searching.value = false
        }
    }, 300)
}

const select = (talk) => {
    emit('update:modelValue', {
        talk_id: talk.talk_id,
        title: talk.title,
        speaker: talk.speaker,
        url: talk.url,
    })
    query.value = ''
    results.value = []
    open.value = false
}

const clear = () => {
    emit('update:modelValue', {})
}
</script>

<template>
    <div class="space-y-3">
        <!-- Selected talk -->
        <div
            v-if="modelValue && modelValue.talk_id"
            class="flex items-start justify-between gap-3 rounded-lg border border-stone-200 bg-stone-50 p-3"
        >
            <div>
                <p class="font-medium text-stone-800">{{ modelValue.title }}</p>
                <p class="text-sm text-stone-500">{{ modelValue.speaker }}</p>
                <a
                    v-if="modelValue.url"
                    :href="modelValue.url"
                    target="_blank"
                    rel="noopener"
                    class="text-xs text-amber-600 hover:text-amber-800 underline"
                >
                    View talk
                </a>
            </div>
            <button
                type="button"
                @click="clear"
                class="text-stone-400 hover:text-stone-600 text-sm"
            >
                Change
            </button>
        </div>

        <!-- Search -->
        <div v-else class="relative">
            <input
                v-model="query"
                @input="search"
                type="text"
                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                placeholder="Search the talks library by title or speaker..."
            >
            <span v-if="searching" class="absolute right-3 top-2.5 text-xs text-stone-400">Searching...</span>

            <ul
                v-if="open && results.length"
                class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-lg border border-stone-200 bg-white shadow-lg"
            >
                <li
                    v-for="talk in results"
                    :key="talk.talk_id"
                    @click="select(talk)"
                    class="cursor-pointer px-3 py-2 hover:bg-amber-50"
                >
                    <p class="text-sm font-medium text-stone-800">{{ talk.title }}</p>
                    <p class="text-xs text-stone-500">{{ talk.speaker }}</p>
                </li>
            </ul>
            <p v-else-if="open && !searching" class="mt-1 text-xs text-stone-400">No talks found.</p>
        </div>
    </div>
</template>
