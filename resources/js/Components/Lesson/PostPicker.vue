<script setup>
import { ref } from 'vue'
import axios from 'axios'

// Mutates the lesson item directly (matching the rest of the builder): sets
// item.post_id (the FK), item.content (an editable excerpt of the post), and
// item.config (a display snapshot of the source post).
const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
})

if (!props.item.config) props.item.config = {}

const typeChips = [
    { value: '', label: 'All' },
    { value: 'story', label: 'Stories' },
    { value: 'thought', label: 'Thoughts' },
    { value: 'note', label: 'Notes' },
]

const typeLabels = {
    story: 'Story',
    thought: 'Thought',
    note: 'Note',
    meeting_notes: 'Meeting Notes',
}

const query = ref('')
const type = ref('')
const results = ref([])
const searching = ref(false)
const open = ref(false)
let debounceTimer = null

const runSearch = () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(async () => {
        searching.value = true
        try {
            const { data } = await axios.get(route('lessons.post-search'), {
                params: { q: query.value.trim(), type: type.value || undefined }
            })
            results.value = data
            open.value = true
        } catch (e) {
            results.value = []
        } finally {
            searching.value = false
        }
    }, 300)
}

const setType = (value) => {
    type.value = value
    runSearch()
}

const attach = (post) => {
    props.item.post_id = post.post_id
    props.item.content = post.content || ''
    props.item.config = {
        post_slug: post.slug,
        post_title: post.title,
        post_type: post.post_type,
        tags: post.tags || [],
    }
    query.value = ''
    results.value = []
    open.value = false
}

const clear = () => {
    props.item.post_id = null
    props.item.content = ''
    props.item.config = {}
}
</script>

<template>
    <div class="space-y-3">
        <!-- Selected post -->
        <div
            v-if="item.post_id"
            class="flex items-start justify-between gap-3 rounded-lg border border-stone-200 bg-stone-50 p-3"
        >
            <div class="min-w-0">
                <p class="font-medium text-stone-800">{{ item.config.post_title }}</p>
                <p class="text-sm text-stone-500">
                    {{ typeLabels[item.config.post_type] || 'Post' }}
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
                >View the full post</a>
            </div>
            <button type="button" @click="clear" class="flex-shrink-0 text-sm text-stone-400 hover:text-stone-600">
                Change
            </button>
        </div>

        <template v-else>
            <!-- Type filter -->
            <div class="inline-flex rounded-lg border border-stone-200 p-0.5">
                <button
                    v-for="chip in typeChips"
                    :key="chip.value"
                    type="button"
                    @click="setType(chip.value)"
                    class="rounded-md px-3 py-1 text-sm"
                    :class="type === chip.value ? 'bg-amber-100 text-amber-800' : 'text-stone-500'"
                >
                    {{ chip.label }}
                </button>
            </div>

            <!-- Search -->
            <div class="relative">
                <input
                    v-model="query"
                    @input="runSearch"
                    @focus="runSearch"
                    type="text"
                    class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                    placeholder="Search your posts by title, text, or tag..."
                >
                <span v-if="searching" class="absolute right-3 top-2.5 text-xs text-stone-400">Searching...</span>

                <ul
                    v-if="open && results.length"
                    class="absolute z-20 mt-1 max-h-72 w-full overflow-auto rounded-lg border border-stone-200 bg-white shadow-lg"
                >
                    <li
                        v-for="post in results"
                        :key="post.post_id"
                        @click="attach(post)"
                        class="cursor-pointer border-b border-stone-100 px-3 py-2 last:border-0 hover:bg-amber-50"
                    >
                        <p class="text-sm font-medium text-stone-800">
                            {{ post.title }}
                            <span class="ml-1 rounded bg-stone-100 px-1.5 py-0.5 text-xs font-normal text-stone-500">
                                {{ typeLabels[post.post_type] || post.post_type }}
                            </span>
                        </p>
                        <p class="text-xs text-stone-500">{{ post.excerpt }}</p>
                    </li>
                </ul>
                <p v-else-if="open && !searching" class="mt-1 text-xs text-stone-400">
                    No posts found. Stories, thoughts, and notes you write show up here.
                </p>
            </div>
        </template>
    </div>
</template>
