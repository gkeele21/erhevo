<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    story: {
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
</script>

<template>
    <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow border border-navy-50">
        <!-- Cover Image -->
        <div v-if="story.cover_image" class="aspect-video overflow-hidden">
            <img
                :src="story.cover_image"
                :alt="story.title"
                class="w-full h-full object-cover"
            >
        </div>

        <div class="p-5">
            <!-- Category -->
            <div v-if="story.category" class="mb-2">
                <Link
                    :href="route('categories.show', story.category.slug)"
                    class="text-xs font-medium text-teal hover:text-navy"
                >
                    {{ story.category.name }}
                </Link>
            </div>

            <!-- Title -->
            <h3 class="text-lg font-semibold text-navy mb-2">
                <Link :href="route('stories.show', story.slug)" class="hover:text-teal">
                    {{ story.title }}
                </Link>
            </h3>

            <!-- Excerpt -->
            <p class="text-teal text-sm mb-4">
                {{ story.excerpt || truncate(story.content) }}
            </p>

            <!-- Tags -->
            <div v-if="story.tags && story.tags.length" class="flex flex-wrap gap-2 mb-4">
                <span
                    v-for="tag in story.tags.slice(0, 3)"
                    :key="tag.id"
                    class="px-2 py-1 text-xs bg-navy-50 text-teal rounded"
                >
                    #{{ tag.name }}
                </span>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between text-sm text-teal-300">
                <div class="flex items-center gap-2">
                    <span v-if="story.creator_name">{{ story.creator_name }}</span>
                    <span v-else class="italic">Anonymous</span>
                </div>
                <span>{{ formatDate(story.published_at) }}</span>
            </div>
        </div>
    </article>
</template>
