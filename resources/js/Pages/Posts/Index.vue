<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import StoryCard from '@/Components/Story/StoryCard.vue'

const props = defineProps({
    stories: Object,
    categories: Array,
    postTypes: Array,
    filters: Object
})

const search = ref(props.filters?.search || '')
const selectedCategory = ref(props.filters?.category || '')
const selectedType = ref(props.filters?.type || '')
const friendsOnly = ref(props.filters?.friends_only === '1' || props.filters?.friends_only === true)

const applyFilters = () => {
    router.get(route('posts.index'), {
        search: search.value || undefined,
        category: selectedCategory.value || undefined,
        type: selectedType.value || undefined,
        tag: props.filters?.tag || undefined,
        friends_only: friendsOnly.value ? '1' : undefined
    }, {
        preserveState: true,
        replace: true
    })
}

const setTypeFilter = (type) => {
    selectedType.value = type
    applyFilters()
}

const page = usePage()

let debounceTimer = null
watch(search, () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(applyFilters, 500)
})

watch(selectedCategory, applyFilters)
watch(friendsOnly, applyFilters)
</script>

<template>
    <AppLayout title="Posts">
        <template #header>
            <h2 class="font-semibold text-xl text-stone-800 leading-tight">
                Posts
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Type Filter Tabs -->
                <div class="mb-6">
                    <div class="flex flex-wrap gap-2">
                        <button
                            @click="setTypeFilter('')"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="selectedType === ''
                                ? 'bg-amber-600 text-white'
                                : 'bg-white text-stone-600 hover:bg-stone-100 border border-stone-200'"
                        >
                            All
                        </button>
                        <button
                            v-for="type in postTypes"
                            :key="type.value"
                            @click="setTypeFilter(type.value)"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="selectedType === type.value
                                ? 'bg-amber-600 text-white'
                                : 'bg-white text-stone-600 hover:bg-stone-100 border border-stone-200'"
                        >
                            {{ type.label }}s
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow p-4 mb-8 border border-stone-100">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search by title, tag, or author..."
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                            >
                        </div>
                        <div class="w-full md:w-48">
                            <select
                                v-model="selectedCategory"
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                            >
                                <option value="">All Categories</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.slug">
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>
                        <div v-if="page.props.auth?.user" class="flex items-center">
                            <label class="flex items-center cursor-pointer">
                                <input
                                    v-model="friendsOnly"
                                    type="checkbox"
                                    class="rounded border-stone-300 text-amber-500 focus:ring-amber-500"
                                >
                                <span class="ml-2 text-sm text-stone-600">Friends only</span>
                            </label>
                        </div>
                    </div>

                    <div v-if="filters?.tag" class="mt-3">
                        <span class="text-sm text-stone-500">Filtered by tag:</span>
                        <span class="ml-2 px-2 py-1 bg-amber-100 text-amber-800 rounded text-sm">
                            #{{ filters.tag }}
                        </span>
                        <Link
                            :href="route('posts.index')"
                            class="ml-2 text-sm text-stone-500 hover:text-stone-700"
                        >
                            Clear
                        </Link>
                    </div>
                </div>

                <!-- Posts Grid -->
                <div v-if="stories.data.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <StoryCard
                        v-for="story in stories.data"
                        :key="story.id"
                        :story="story"
                    />
                </div>

                <div v-else class="bg-white rounded-lg shadow p-12 text-center border border-stone-100">
                    <svg class="w-16 h-16 mx-auto text-stone-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-stone-800 mb-2">
                        No posts found
                    </h3>
                    <p class="text-stone-500">
                        Try adjusting your search or filters to find what you're looking for.
                    </p>
                </div>

                <!-- Pagination -->
                <div v-if="stories.last_page > 1" class="mt-8 flex justify-center gap-2">
                    <Link
                        v-if="stories.prev_page_url"
                        :href="stories.prev_page_url"
                        class="px-4 py-2 bg-stone-200 text-stone-700 rounded hover:bg-stone-300"
                    >
                        Previous
                    </Link>
                    <span class="px-4 py-2 text-stone-600">
                        Page {{ stories.current_page }} of {{ stories.last_page }}
                    </span>
                    <Link
                        v-if="stories.next_page_url"
                        :href="stories.next_page_url"
                        class="px-4 py-2 bg-stone-200 text-stone-700 rounded hover:bg-stone-300"
                    >
                        Next
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
