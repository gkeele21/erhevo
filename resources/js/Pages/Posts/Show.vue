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

const formatSize = (bytes) => {
    if (!bytes) return ''
    if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`
    return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}
const authorSlug = computed(() => props.post.author?.slug || null)

// Mirrors PostType::label() server-side.
const typeLabel = computed(() => ({
    story: 'Story',
    thought: 'Thought',
    note: 'Note',
    quote: 'Quote',
    video: 'Video / Link',
    image: 'Image',
    scripture_help: 'Scripture Help',
    meeting_notes: 'Meeting Notes'
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
                    <!-- Cover Image: the content itself for image and scripture-help
                         posts (never cropped), a fixed-height banner for everything else -->
                    <div v-if="post.cover_image" class="mb-8 rounded-lg overflow-hidden">
                        <img
                            :src="post.cover_image"
                            :alt="post.title"
                            class="w-full"
                            :class="['image', 'scripture_help'].includes(postType) ? 'h-auto max-h-[85vh] object-contain bg-stone-100' : 'h-64 md:h-96 object-cover'"
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

                <!-- PDF attachment: rendered in place, with a download card underneath
                     for the browsers (mostly mobile) that won't embed a PDF. -->
                <div v-if="post.attachment_url" class="mt-8 overflow-hidden rounded-lg border border-stone-200 bg-white shadow">
                    <object
                        :data="post.attachment_url"
                        type="application/pdf"
                        class="hidden h-[80vh] w-full md:block"
                    >
                        <p class="p-6 text-sm text-stone-600">
                            This browser can't display the PDF inline — use the link below.
                        </p>
                    </object>
                    <div class="flex flex-wrap items-center gap-3 border-stone-200 px-4 py-3 md:border-t">
                        <svg class="h-8 w-8 flex-shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-stone-800">
                                {{ post.attachment_name || 'Attached PDF' }}
                            </p>
                            <p class="text-xs text-stone-500">
                                PDF<span v-if="post.attachment_size"> &middot; {{ formatSize(post.attachment_size) }}</span>
                            </p>
                        </div>
                        <a
                            :href="post.attachment_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm font-medium text-amber-700 underline hover:text-amber-900"
                        >
                            Open ↗
                        </a>
                        <a
                            :href="post.attachment_url"
                            :download="post.attachment_name || ''"
                            class="text-sm font-medium text-amber-700 underline hover:text-amber-900"
                        >
                            Download
                        </a>
                    </div>
                </div>

                <!-- Inline viewer for sources that offer one (Google Slides, YouTube, Vimeo) -->
                <div v-if="post.embed_url" class="mt-8 overflow-hidden rounded-lg border border-stone-200 bg-white shadow">
                    <iframe
                        :src="post.embed_url"
                        :title="post.title"
                        class="aspect-video w-full"
                        frameborder="0"
                        loading="lazy"
                        allow="autoplay; encrypted-media; fullscreen; picture-in-picture"
                        allowfullscreen
                    ></iframe>
                    <p v-if="canEdit" class="border-t border-stone-200 px-4 py-2 text-xs text-stone-500">
                        Others only see this if the original is shared publicly. For a Google deck,
                        File &rarr; Share &rarr; Publish to web gives a link that works without sign-in — paste that one.
                        Otherwise viewers get the <span class="font-medium">View original</span> link below.
                    </p>
                </div>

                <!-- Original source link -->
                <div v-if="post.source_url" class="mt-8 rounded-lg border border-stone-200 bg-white p-4 text-sm text-stone-600">
                    Originally seen
                    <template v-if="post.author_name">from <span class="font-medium">{{ post.author_name }}</span></template>
                    <template v-if="post.source_platform">on <span class="font-medium">{{ post.source_platform }}</span></template>
                    &middot;
                    <a
                        :href="post.source_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-amber-700 hover:text-amber-900 underline"
                    >
                        View original ↗
                    </a>
                </div>

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
