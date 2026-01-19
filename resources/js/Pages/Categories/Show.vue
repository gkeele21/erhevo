<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StoryCard from '@/Components/Story/StoryCard.vue'

defineProps({
    category: Object,
    stories: Object
})
</script>

<template>
    <AppLayout :title="category.name">
        <template #header>
            <div>
                <Link
                    :href="route('categories.index')"
                    class="text-amber-600 hover:text-amber-800 text-sm mb-2 inline-block"
                >
                    &larr; All Categories
                </Link>
                <h2 class="font-semibold text-xl text-stone-800 leading-tight">
                    {{ category.name }}
                </h2>
                <p v-if="category.description" class="text-stone-600 mt-1">
                    {{ category.description }}
                </p>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="stories.data?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <StoryCard
                        v-for="story in stories.data"
                        :key="story.id"
                        :story="story"
                    />
                </div>

                <div v-else class="bg-white rounded-lg shadow p-12 text-center border border-stone-100">
                    <p class="text-stone-500">
                        No stories in this category yet.
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
