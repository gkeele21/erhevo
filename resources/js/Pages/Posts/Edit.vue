<script setup>
import { Head, useForm, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import StoryEditor from '@/Components/Story/StoryEditor.vue'
import VisibilitySelector from '@/Components/Story/VisibilitySelector.vue'
import TagInput from '@/Components/Story/TagInput.vue'
import PrivacyOptions from '@/Components/Story/PrivacyOptions.vue'
import AuthorInput from '@/Components/Story/AuthorInput.vue'

const props = defineProps({
    story: Object,
    categories: Array,
    postTypes: Array,
    visibilityOptions: Array,
    authorTypes: Array
})

const form = useForm({
    post_type: props.story.post_type || 'story',
    title: props.story.title,
    content: props.story.content,
    excerpt: props.story.excerpt || '',
    cover_image: props.story.cover_image || '',
    category_id: props.story.category_id || '',
    tags: props.story.tags?.map(t => t.name) || [],
    author_type: props.story.author_type,
    author_text: props.story.author_text || '',
    author_user_id: props.story.author_user_id,
    visibility: props.story.visibility,
    hide_creator: props.story.hide_creator,
    hide_author: props.story.hide_author,
    anonymize_names: props.story.anonymize_names,
    name_mappings: props.story.name_mappings,
    publish: !!props.story.published_at
})

const showDeleteModal = ref(false)

const postTypeIcons = {
    'book-open': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>`,
    'lightbulb': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>`,
    'document-text': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>`,
    'chat-bubble-bottom-center-text': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>`
}

const submit = () => {
    form.put(route('posts.update', props.story.slug))
}

const deleteStory = () => {
    router.delete(route('posts.destroy', props.story.slug))
}
</script>

<template>
    <AppLayout title="Edit Post">
        <template #header>
            <h2 class="font-semibold text-xl text-stone-800 leading-tight">
                Edit Post
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-8">
                    <!-- Post Type Selector -->
                    <div class="bg-white rounded-lg shadow p-6 border border-stone-100">
                        <label class="block text-sm font-medium text-stone-700 mb-3">
                            Post Type
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <button
                                v-for="type in postTypes"
                                :key="type.value"
                                type="button"
                                @click="form.post_type = type.value"
                                class="p-4 rounded-lg border-2 text-center transition-all"
                                :class="form.post_type === type.value
                                    ? 'border-amber-500 bg-amber-50 text-amber-900'
                                    : 'border-stone-200 hover:border-stone-300 text-stone-600'"
                            >
                                <svg
                                    class="w-6 h-6 mx-auto mb-2"
                                    :class="form.post_type === type.value ? 'text-amber-600' : 'text-stone-400'"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    v-html="postTypeIcons[type.icon]"
                                ></svg>
                                <div class="font-medium text-sm">{{ type.label }}</div>
                            </button>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6 space-y-6 border border-stone-100">
                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">
                                Title
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500 text-lg"
                            >
                            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                        </div>

                        <!-- Content Editor -->
                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">
                                Content
                            </label>
                            <StoryEditor v-model="form.content" />
                            <p v-if="form.errors.content" class="mt-1 text-sm text-red-600">{{ form.errors.content }}</p>
                        </div>

                        <!-- Excerpt -->
                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">
                                Excerpt (optional)
                            </label>
                            <textarea
                                v-model="form.excerpt"
                                rows="2"
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                            ></textarea>
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">
                                Category
                            </label>
                            <select
                                v-model="form.category_id"
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                            >
                                <option value="">Select a category</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Tags -->
                        <TagInput v-model="form.tags" />

                        <!-- Author -->
                        <AuthorInput
                            v-model:author-type="form.author_type"
                            v-model:author-text="form.author_text"
                            v-model:author-user-id="form.author_user_id"
                            :author-types="authorTypes"
                        />
                    </div>

                    <!-- Visibility & Privacy -->
                    <div class="bg-white rounded-lg shadow p-6 space-y-6 border border-stone-100">
                        <VisibilitySelector
                            v-model="form.visibility"
                            :options="visibilityOptions"
                        />

                        <PrivacyOptions
                            v-model:hide-creator="form.hide_creator"
                            v-model:hide-author="form.hide_author"
                            v-model:anonymize-names="form.anonymize_names"
                            :visibility="form.visibility"
                        />
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-between items-center">
                        <button
                            type="button"
                            @click="showDeleteModal = true"
                            class="text-red-600 hover:text-red-800"
                        >
                            Delete Post
                        </button>

                        <div class="flex gap-4">
                            <Link
                                :href="route('posts.share.index', story.slug)"
                                class="px-6 py-3 border border-amber-300 text-amber-700 rounded-lg hover:bg-amber-50 flex items-center gap-2"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                </svg>
                                Share
                            </Link>
                            <Link
                                :href="route('posts.show', story.slug)"
                                class="px-6 py-3 border border-stone-300 text-stone-700 rounded-lg hover:bg-stone-50"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-3 bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50"
                            >
                                Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black opacity-50" @click="showDeleteModal = false"></div>

                <div class="relative bg-white rounded-lg max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-stone-800 mb-4">
                        Delete Post
                    </h3>
                    <p class="text-stone-600 mb-6">
                        Are you sure you want to delete this post? This action cannot be undone.
                    </p>
                    <div class="flex justify-end gap-4">
                        <button
                            @click="showDeleteModal = false"
                            class="px-4 py-2 text-stone-600 hover:text-stone-800"
                        >
                            Cancel
                        </button>
                        <button
                            @click="deleteStory"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
