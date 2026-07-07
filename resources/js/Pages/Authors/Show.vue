<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    author: Object,
    posts: Object,
    callings: Array,
    filters: Object,
})

const formatDate = (date) => date ? new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : ''

const typeLabel = (t) => ({ story: 'Story', thought: 'Thought', note: 'Note', quote: 'Quote', meeting_notes: 'Meeting Notes' }[t] || 'Post')

const filterByCalling = (callingId) => {
    router.get(route('authors.show', props.author.slug), { calling: callingId || undefined }, { preserveState: true, replace: true })
}
</script>

<template>
    <Head :title="author.full_name" />
    <AppLayout :title="author.full_name">
        <template #header>
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-xl font-semibold leading-tight text-stone-800">{{ author.full_name }}</h2>
                <span v-if="author.current_calling" class="rounded-full bg-amber-100 px-3 py-0.5 text-sm text-amber-800">
                    {{ author.current_calling }}
                </span>
                <span v-if="author.is_user" class="rounded-full bg-stone-100 px-3 py-0.5 text-sm text-stone-600">App user</span>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <p v-if="author.notes" class="mb-6 text-stone-600">{{ author.notes }}</p>

                <!-- Calling filter -->
                <div v-if="callings.length" class="mb-6 flex flex-wrap items-center gap-2">
                    <span class="text-sm text-stone-500">Given as:</span>
                    <button
                        type="button"
                        @click="filterByCalling(null)"
                        class="rounded-full px-3 py-1 text-sm"
                        :class="!filters?.calling ? 'bg-amber-600 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200'"
                    >
                        All
                    </button>
                    <button
                        v-for="c in callings"
                        :key="c.id"
                        type="button"
                        @click="filterByCalling(c.id)"
                        class="rounded-full px-3 py-1 text-sm"
                        :class="String(filters?.calling) === String(c.id) ? 'bg-amber-600 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200'"
                    >
                        {{ c.label }}
                    </button>
                </div>

                <!-- Content -->
                <div class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
                    <Link
                        v-for="post in posts.data"
                        :key="post.id"
                        :href="route('posts.show', post.slug)"
                        class="block px-4 py-4 hover:bg-stone-50"
                    >
                        <div class="flex items-center gap-2">
                            <span class="rounded bg-stone-100 px-2 py-0.5 text-xs text-stone-500">{{ typeLabel(post.post_type) }}</span>
                            <span v-if="post.calling" class="rounded bg-amber-50 px-2 py-0.5 text-xs text-amber-700">{{ post.calling.name }}</span>
                            <span class="text-xs text-stone-400">{{ formatDate(post.published_at || post.created_at) }}</span>
                        </div>
                        <p class="mt-1 font-medium text-stone-800">{{ post.title }}</p>
                        <div v-if="post.tags?.length" class="mt-1 flex flex-wrap gap-1">
                            <span v-for="tag in post.tags" :key="tag.id" class="text-xs text-stone-400">#{{ tag.name }}</span>
                        </div>
                    </Link>
                    <p v-if="!posts.data.length" class="px-4 py-8 text-center text-stone-400">No visible content for this author yet.</p>
                </div>

                <!-- Pagination -->
                <div v-if="posts.links?.length > 3" class="mt-6 flex flex-wrap gap-1">
                    <component
                        :is="link.url ? Link : 'span'"
                        v-for="link in posts.links"
                        :key="link.label"
                        :href="link.url"
                        v-html="link.label"
                        class="rounded px-3 py-1 text-sm"
                        :class="link.active ? 'bg-amber-600 text-white' : (link.url ? 'text-stone-600 hover:bg-stone-100' : 'text-stone-300')"
                    />
                </div>

                <div class="mt-8">
                    <Link :href="route('authors.index')" class="text-amber-600 hover:text-amber-800">← All authors</Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
