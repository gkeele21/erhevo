<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'

// Entity-aware author picker: search existing Author records, select one, or
// type a new name (the server find-or-creates it on save). Two-way binds the
// chosen author_id and the display name.
const props = defineProps({
    modelValue: { type: [Number, String, null], default: null }, // author_id
    name: { type: String, default: '' },
    placeholder: { type: String, default: 'Search authors, or type a new name...' },
})

const emit = defineEmits(['update:modelValue', 'update:name'])

const query = ref(props.name || '')
const results = ref([])
const searching = ref(false)
const open = ref(false)
let debounce = null

// Reflect external name changes (e.g. form reset) when nothing is selected.
watch(() => props.name, (v) => {
    if (!props.modelValue && v !== query.value) query.value = v || ''
})

const onInput = () => {
    clearTimeout(debounce)
    // Typing detaches any previously chosen entity; the typed text becomes the
    // pending new-author name.
    emit('update:modelValue', null)
    emit('update:name', query.value)

    const q = query.value.trim()
    if (q.length < 2) {
        results.value = []
        open.value = false
        return
    }

    debounce = setTimeout(async () => {
        searching.value = true
        try {
            const { data } = await axios.get(route('authors.search'), { params: { q } })
            results.value = data
            open.value = true
        } catch (e) {
            results.value = []
        } finally {
            searching.value = false
        }
    }, 300)
}

const select = (author) => {
    emit('update:modelValue', author.id)
    emit('update:name', author.full_name)
    query.value = author.full_name
    results.value = []
    open.value = false
}

const hide = () => setTimeout(() => { open.value = false }, 150)
</script>

<template>
    <div class="relative">
        <input
            v-model="query"
            @input="onInput"
            @focus="open = results.length > 0"
            @blur="hide"
            type="text"
            autocomplete="off"
            class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
            :placeholder="placeholder"
        >
        <span v-if="searching" class="absolute right-3 top-2.5 text-xs text-stone-400">Searching...</span>

        <ul
            v-if="open && results.length"
            class="absolute z-30 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-stone-200 bg-white shadow-lg"
        >
            <li
                v-for="author in results"
                :key="author.id"
                @mousedown.prevent="select(author)"
                class="flex cursor-pointer items-center justify-between gap-2 px-3 py-2 text-sm hover:bg-amber-50"
            >
                <span class="text-stone-800">{{ author.full_name }}</span>
                <span class="flex items-center gap-1">
                    <span v-if="author.calling" class="rounded bg-stone-100 px-1.5 py-0.5 text-xs text-stone-500">{{ author.calling }}</span>
                    <span v-if="author.is_user" class="rounded bg-amber-100 px-1.5 py-0.5 text-xs text-amber-700">user</span>
                </span>
            </li>
        </ul>
        <p v-else-if="open && !searching && query.trim().length >= 2" class="mt-1 text-xs text-stone-400">
            No match — “{{ query.trim() }}” will be added as a new author.
        </p>
    </div>
</template>
