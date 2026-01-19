<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    story: Object,
    canEdit: Boolean
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}
</script>

<template>
    <AppLayout :title="story.title">
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Cover Image -->
                <div v-if="story.cover_image" class="mb-8 rounded-lg overflow-hidden">
                    <img
                        :src="story.cover_image"
                        :alt="story.title"
                        class="w-full h-64 md:h-96 object-cover"
                    >
                </div>

                <article class="bg-white rounded-lg shadow p-8 border border-stone-100">
                    <!-- Header -->
                    <header class="mb-8">
                        <div class="flex items-center gap-4 mb-4">
                            <Link
                                v-if="story.category"
                                :href="route('categories.show', story.category.slug)"
                                class="text-sm font-medium text-amber-600 hover:text-amber-800"
                            >
                                {{ story.category.name }}
                            </Link>
                            <span class="text-sm text-stone-500">
                                {{ formatDate(story.published_at || story.created_at) }}
                            </span>
                        </div>

                        <h1 class="text-3xl md:text-4xl font-bold text-stone-800 mb-4">
                            {{ story.title }}
                        </h1>

                        <div class="flex items-center justify-between">
                            <div class="text-stone-600">
                                <span v-if="story.creator_name">
                                    By {{ story.creator_name }}
                                </span>
                                <span v-if="story.author_name && story.author_name !== story.creator_name" class="ml-2">
                                    &middot; Author: {{ story.author_name }}
                                </span>
                            </div>

                            <Link
                                v-if="canEdit"
                                :href="route('stories.edit', story.slug)"
                                class="text-amber-600 hover:text-amber-800 flex items-center gap-1"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Edit
                            </Link>
                        </div>
                    </header>

                    <!-- Content -->
                    <div
                        class="prose prose-lg prose-stone max-w-none prose-headings:text-stone-800 prose-a:text-amber-600"
                        v-html="story.display_content || story.content"
                    ></div>

                    <!-- Tags -->
                    <div v-if="story.tags && story.tags.length" class="mt-8 pt-8 border-t border-stone-200">
                        <div class="flex flex-wrap gap-2">
                            <Link
                                v-for="tag in story.tags"
                                :key="tag.id"
                                :href="route('stories.index', { tag: tag.slug })"
                                class="px-3 py-1 bg-stone-100 text-stone-700 rounded-full text-sm hover:bg-amber-100 hover:text-amber-700 transition-colors"
                            >
                                #{{ tag.name }}
                            </Link>
                        </div>
                    </div>
                </article>

                <!-- Navigation -->
                <div class="mt-8 flex justify-between">
                    <Link
                        :href="route('stories.index')"
                        class="text-amber-600 hover:text-amber-800 flex items-center gap-1"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        All Stories
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
