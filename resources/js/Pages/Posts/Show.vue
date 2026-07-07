<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    post: Object,
    canEdit: Boolean,
    usedInLessons: {
        type: Array,
        default: () => []
    }
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const postType = computed(() => props.post.post_type || 'story')
const authorSlug = computed(() => props.post.author?.slug || null)

const typeLabel = computed(() => ({
    story: 'Story',
    thought: 'Thought',
    note: 'Note',
    quote: 'Quote'
}[postType.value] || 'Post'))
</script>

<template>
    <AppLayout :title="post.title">
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

                <!-- Quote Display -->
                <template v-if="postType === 'quote'">
                    <article class="bg-gradient-to-br from-gold-50 via-amber-50 to-gold-50 rounded-xl shadow-lg p-12 border border-gold-200">
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-8">
                            <span class="text-sm text-gold-600">{{ typeLabel }}</span>
                            <Link
                                v-if="canEdit"
                                :href="route('posts.edit', post.slug)"
                                class="text-gold-600 hover:text-gold-800 flex items-center gap-1"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Edit
                            </Link>
                        </div>

                        <!-- Quote Content -->
                        <div class="relative text-center py-8">
                            <svg class="absolute top-0 left-0 w-16 h-16 text-gold-300 opacity-30" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                            </svg>
                            <div
                                class="prose prose-xl prose-stone max-w-none text-center italic text-navy leading-relaxed"
                                v-html="post.display_content || post.content"
                            ></div>
                            <svg class="absolute bottom-0 right-0 w-16 h-16 text-gold-300 opacity-30 transform rotate-180" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                            </svg>
                        </div>

                        <!-- Attribution -->
                        <div class="text-center mt-8 pt-8 border-t border-gold-200">
                            <div v-if="post.title" class="text-lg font-medium text-navy mb-2">
                                &mdash; {{ post.title }}
                            </div>
                            <div class="text-gold-700 text-sm">
                                <Link v-if="post.author_name && authorSlug" :href="route('authors.show', authorSlug)" class="hover:underline">{{ post.author_name }}</Link>
                                <span v-else-if="post.author_name">{{ post.author_name }}</span>
                                <span v-else-if="post.creator_name">{{ post.creator_name }}</span>
                            </div>
                            <div class="text-sm text-gold-600 mt-2">
                                {{ formatDate(post.published_at || post.created_at) }}
                            </div>
                        </div>

                        <!-- Tags -->
                        <div v-if="post.tags && post.tags.length" class="mt-8 pt-8 border-t border-gold-200">
                            <div class="flex flex-wrap justify-center gap-2">
                                <Link
                                    v-for="tag in post.tags"
                                    :key="tag.id"
                                    :href="route('posts.index', { tag: tag.slug })"
                                    class="px-3 py-1 bg-white/50 text-gold-700 rounded-full text-sm hover:bg-white transition-colors"
                                >
                                    #{{ tag.name }}
                                </Link>
                            </div>
                        </div>
                    </article>
                </template>

                <!-- Thought Display -->
                <template v-else-if="postType === 'thought'">
                    <article class="bg-white rounded-lg shadow p-8 border-l-4 border-amber-400">
                        <!-- Header -->
                        <header class="mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-4">
                                    <span class="px-3 py-1 bg-amber-50 text-amber-700 rounded text-sm">{{ typeLabel }}</span>
                                    <span class="text-sm text-stone-500">
                                        {{ formatDate(post.published_at || post.created_at) }}
                                    </span>
                                </div>
                                <Link
                                    v-if="canEdit"
                                    :href="route('posts.edit', post.slug)"
                                    class="text-amber-600 hover:text-amber-800 flex items-center gap-1"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit
                                </Link>
                            </div>

                            <div class="text-stone-600">
                                <span v-if="post.creator_name">{{ post.creator_name }}</span>
                            </div>
                        </header>

                        <!-- Content -->
                        <div
                            class="prose prose-lg prose-stone max-w-none italic prose-headings:text-stone-800 prose-a:text-amber-600 leading-relaxed"
                            v-html="post.display_content || post.content"
                        ></div>

                        <!-- Title (if present, shown at end) -->
                        <div v-if="post.title" class="mt-8 pt-6 border-t border-stone-100">
                            <h1 class="text-xl font-medium text-stone-700">
                                {{ post.title }}
                            </h1>
                        </div>

                        <!-- Tags -->
                        <div v-if="post.tags && post.tags.length" class="mt-6 pt-6 border-t border-stone-100">
                            <div class="flex flex-wrap gap-2">
                                <Link
                                    v-for="tag in post.tags"
                                    :key="tag.id"
                                    :href="route('posts.index', { tag: tag.slug })"
                                    class="px-3 py-1 bg-amber-50 text-amber-700 rounded-full text-sm hover:bg-amber-100 transition-colors"
                                >
                                    #{{ tag.name }}
                                </Link>
                            </div>
                        </div>
                    </article>
                </template>

                <!-- Note Display -->
                <template v-else-if="postType === 'note'">
                    <article class="bg-stone-50 rounded-lg shadow p-8 border border-stone-200">
                        <!-- Header -->
                        <header class="mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-4">
                                    <span class="px-3 py-1 bg-teal-50 text-teal-700 rounded text-sm">{{ typeLabel }}</span>
                                    <Link
                                        v-if="post.category"
                                        :href="route('categories.show', post.category.slug)"
                                        class="text-sm font-medium text-teal-600 hover:text-teal-800"
                                    >
                                        {{ post.category.name }}
                                    </Link>
                                </div>
                                <Link
                                    v-if="canEdit"
                                    :href="route('posts.edit', post.slug)"
                                    class="text-teal-600 hover:text-teal-800 flex items-center gap-1"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit
                                </Link>
                            </div>

                            <h1 class="text-2xl font-semibold text-stone-800 mb-2">
                                {{ post.title }}
                            </h1>

                            <div class="text-sm text-stone-500">
                                {{ formatDate(post.published_at || post.created_at) }}
                                <span v-if="post.creator_name" class="ml-2">
                                    &middot; {{ post.creator_name }}
                                </span>
                            </div>
                        </header>

                        <!-- Content -->
                        <div
                            class="prose prose-stone max-w-none prose-headings:text-stone-800 prose-a:text-teal-600 bg-white rounded-lg p-6 border border-stone-200"
                            v-html="post.display_content || post.content"
                        ></div>

                        <!-- Tags -->
                        <div v-if="post.tags && post.tags.length" class="mt-6">
                            <div class="flex flex-wrap gap-2">
                                <Link
                                    v-for="tag in post.tags"
                                    :key="tag.id"
                                    :href="route('posts.index', { tag: tag.slug })"
                                    class="px-3 py-1 bg-white text-stone-600 rounded-full text-sm hover:bg-teal-50 hover:text-teal-700 transition-colors border border-stone-200"
                                >
                                    #{{ tag.name }}
                                </Link>
                            </div>
                        </div>
                    </article>
                </template>

                <!-- Default Story Display -->
                <template v-else>
                    <!-- Cover Image -->
                    <div v-if="post.cover_image" class="mb-8 rounded-lg overflow-hidden">
                        <img
                            :src="post.cover_image"
                            :alt="post.title"
                            class="w-full h-64 md:h-96 object-cover"
                        >
                    </div>

                    <article class="bg-white rounded-lg shadow p-8 border border-stone-100">
                        <!-- Header -->
                        <header class="mb-8">
                            <div class="flex items-center gap-4 mb-4">
                                <span class="px-3 py-1 bg-navy-50 text-navy rounded text-sm">{{ typeLabel }}</span>
                                <Link
                                    v-if="post.category"
                                    :href="route('categories.show', post.category.slug)"
                                    class="text-sm font-medium text-amber-600 hover:text-amber-800"
                                >
                                    {{ post.category.name }}
                                </Link>
                                <span class="text-sm text-stone-500">
                                    {{ formatDate(post.published_at || post.created_at) }}
                                </span>
                            </div>

                            <h1 class="text-3xl md:text-4xl font-bold text-stone-800 mb-4">
                                {{ post.title }}
                            </h1>

                            <div class="flex items-center justify-between">
                                <div class="text-stone-600">
                                    <span v-if="post.creator_name">
                                        By {{ post.creator_name }}
                                    </span>
                                    <span v-if="post.author_name && post.author_name !== post.creator_name" class="ml-2">
                                        &middot; Author:
                                        <Link v-if="authorSlug" :href="route('authors.show', authorSlug)" class="hover:underline">{{ post.author_name }}</Link>
                                        <span v-else>{{ post.author_name }}</span>
                                    </span>
                                </div>

                                <Link
                                    v-if="canEdit"
                                    :href="route('posts.edit', post.slug)"
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
                            v-html="post.display_content || post.content"
                        ></div>

                        <!-- Tags -->
                        <div v-if="post.tags && post.tags.length" class="mt-8 pt-8 border-t border-stone-200">
                            <div class="flex flex-wrap gap-2">
                                <Link
                                    v-for="tag in post.tags"
                                    :key="tag.id"
                                    :href="route('posts.index', { tag: tag.slug })"
                                    class="px-3 py-1 bg-stone-100 text-stone-700 rounded-full text-sm hover:bg-amber-100 hover:text-amber-700 transition-colors"
                                >
                                    #{{ tag.name }}
                                </Link>
                            </div>
                        </div>
                    </article>
                </template>

                <!-- Used in lessons -->
                <div v-if="usedInLessons.length" class="mt-8 rounded-lg border border-stone-200 bg-white p-6">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-stone-500">
                        Used in {{ usedInLessons.length }} {{ usedInLessons.length === 1 ? 'lesson' : 'lessons' }}
                    </h2>
                    <ul class="space-y-2">
                        <li v-for="lesson in usedInLessons" :key="lesson.slug">
                            <Link
                                :href="route('lessons.show', lesson.slug)"
                                class="flex items-center gap-2 text-amber-700 hover:text-amber-900"
                            >
                                <svg class="h-4 w-4 flex-shrink-0 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                {{ lesson.title }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Navigation -->
                <div class="mt-8 flex justify-between">
                    <Link
                        :href="route('posts.index')"
                        class="text-amber-600 hover:text-amber-800 flex items-center gap-1"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        All Posts
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
