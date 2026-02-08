<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    post: {
        type: Object,
        required: true
    }
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const stripHtml = (html) => {
    const tmp = document.createElement('div')
    tmp.innerHTML = html
    return tmp.textContent || tmp.innerText || ''
}

const truncate = (text, length = 150) => {
    const stripped = stripHtml(text)
    if (stripped.length <= length) return stripped
    return stripped.substring(0, length) + '...'
}

const postType = computed(() => props.post.post_type || 'story')

const typeConfig = computed(() => ({
    story: {
        label: 'Story',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>`,
        badgeClass: 'bg-navy-50 text-navy',
        contentClass: '',
    },
    thought: {
        label: 'Thought',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>`,
        badgeClass: 'bg-amber-50 text-amber-700',
        contentClass: 'italic',
    },
    note: {
        label: 'Note',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>`,
        badgeClass: 'bg-teal-50 text-teal-700',
        contentClass: '',
    },
    quote: {
        label: 'Quote',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>`,
        badgeClass: 'bg-gold-50 text-gold-700',
        contentClass: 'text-center',
    },
}[postType.value] || {
    label: 'Post',
    icon: '',
    badgeClass: 'bg-stone-50 text-stone-600',
    contentClass: '',
}))
</script>

<template>
    <!-- Quote Card - Special styling -->
    <article v-if="postType === 'quote'" class="bg-gradient-to-br from-gold-50 to-amber-50 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow border border-gold-200">
        <div class="p-6">
            <!-- Type Badge -->
            <div class="flex justify-end mb-2">
                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded" :class="typeConfig.badgeClass">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="typeConfig.icon"></svg>
                    {{ typeConfig.label }}
                </span>
            </div>

            <!-- Quote Content -->
            <Link :href="route('posts.show', post.slug)" class="block">
                <div class="relative">
                    <svg class="absolute -top-2 -left-2 w-8 h-8 text-gold-300 opacity-50" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                    </svg>
                    <p class="text-lg text-navy text-center italic px-6 py-4 leading-relaxed">
                        {{ post.excerpt || truncate(post.content, 200) }}
                    </p>
                </div>
                <div v-if="post.title" class="text-center text-sm text-gold-700 mt-2">
                    &mdash; {{ post.title }}
                </div>
            </Link>

            <!-- Footer -->
            <div class="flex items-center justify-between text-sm text-teal-300 mt-4 pt-4 border-t border-gold-200">
                <div class="flex items-center gap-2">
                    <span v-if="post.creator_name">{{ post.creator_name }}</span>
                    <span v-else class="italic">Anonymous</span>
                </div>
                <span>{{ formatDate(post.published_at) }}</span>
            </div>
        </div>
    </article>

    <!-- Thought Card - Intimate styling -->
    <article v-else-if="postType === 'thought'" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow border-l-4 border-amber-400">
        <div class="p-5">
            <!-- Type Badge -->
            <div class="flex items-center justify-between mb-3">
                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded" :class="typeConfig.badgeClass">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="typeConfig.icon"></svg>
                    {{ typeConfig.label }}
                </span>
                <span class="text-xs text-teal-300">{{ formatDate(post.published_at) }}</span>
            </div>

            <!-- Content -->
            <Link :href="route('posts.show', post.slug)" class="block">
                <p class="text-teal italic leading-relaxed">
                    {{ post.excerpt || truncate(post.content, 200) }}
                </p>
                <div v-if="post.title" class="text-sm font-medium text-navy mt-3">
                    {{ post.title }}
                </div>
            </Link>

            <!-- Footer -->
            <div class="flex items-center justify-between text-sm text-teal-300 mt-4">
                <div class="flex items-center gap-2">
                    <span v-if="post.creator_name">{{ post.creator_name }}</span>
                    <span v-else class="italic">Anonymous</span>
                </div>
            </div>
        </div>
    </article>

    <!-- Note Card - Compact styling -->
    <article v-else-if="postType === 'note'" class="bg-stone-50 rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow border border-stone-200">
        <div class="p-4">
            <!-- Type Badge & Date -->
            <div class="flex items-center justify-between mb-2">
                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded" :class="typeConfig.badgeClass">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="typeConfig.icon"></svg>
                    {{ typeConfig.label }}
                </span>
                <span class="text-xs text-teal-300">{{ formatDate(post.published_at) }}</span>
            </div>

            <!-- Title -->
            <h3 class="text-base font-medium text-navy mb-1">
                <Link :href="route('posts.show', post.slug)" class="hover:text-teal">
                    {{ post.title }}
                </Link>
            </h3>

            <!-- Excerpt -->
            <p class="text-teal text-sm">
                {{ post.excerpt || truncate(post.content, 100) }}
            </p>

            <!-- Tags -->
            <div v-if="post.tags && post.tags.length" class="flex flex-wrap gap-1 mt-3">
                <span
                    v-for="tag in post.tags.slice(0, 3)"
                    :key="tag.id"
                    class="px-1.5 py-0.5 text-xs bg-white text-teal rounded"
                >
                    #{{ tag.name }}
                </span>
            </div>
        </div>
    </article>

    <!-- Default Story Card -->
    <article v-else class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow border border-navy-50">
        <!-- Cover Image -->
        <div v-if="post.cover_image" class="aspect-video overflow-hidden">
            <img
                :src="post.cover_image"
                :alt="post.title"
                class="w-full h-full object-cover"
            >
        </div>

        <div class="p-5">
            <!-- Category & Type Badge -->
            <div class="flex items-center justify-between mb-2">
                <Link
                    v-if="post.category"
                    :href="route('categories.show', post.category.slug)"
                    class="text-xs font-medium text-teal hover:text-navy"
                >
                    {{ post.category.name }}
                </Link>
                <span v-else></span>
                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded" :class="typeConfig.badgeClass">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="typeConfig.icon"></svg>
                    {{ typeConfig.label }}
                </span>
            </div>

            <!-- Title -->
            <h3 class="text-lg font-semibold text-navy mb-2">
                <Link :href="route('posts.show', post.slug)" class="hover:text-teal">
                    {{ post.title }}
                </Link>
            </h3>

            <!-- Excerpt -->
            <p class="text-teal text-sm mb-4" :class="typeConfig.contentClass">
                {{ post.excerpt || truncate(post.content) }}
            </p>

            <!-- Tags -->
            <div v-if="post.tags && post.tags.length" class="flex flex-wrap gap-2 mb-4">
                <span
                    v-for="tag in post.tags.slice(0, 3)"
                    :key="tag.id"
                    class="px-2 py-1 text-xs bg-navy-50 text-teal rounded"
                >
                    #{{ tag.name }}
                </span>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between text-sm text-teal-300">
                <div class="flex items-center gap-2">
                    <span v-if="post.creator_name">{{ post.creator_name }}</span>
                    <span v-else class="italic">Anonymous</span>
                </div>
                <span>{{ formatDate(post.published_at) }}</span>
            </div>
        </div>
    </article>
</template>
