<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StoryEditor from '@/Components/Story/StoryEditor.vue'
import VisibilitySelector from '@/Components/Story/VisibilitySelector.vue'
import TagInput from '@/Components/Story/TagInput.vue'
import PrivacyOptions from '@/Components/Story/PrivacyOptions.vue'
import AuthorInput from '@/Components/Story/AuthorInput.vue'

const props = defineProps({
    categories: Array,
    postTypes: Array,
    visibilityOptions: Array,
    authorTypes: Array
})

const form = useForm({
    post_type: 'story',
    title: '',
    content: '',
    excerpt: '',
    cover_image: '',
    category_id: '',
    tags: [],
    author_type: 'self',
    author_text: '',
    author_user_id: null,
    visibility: 'public',
    hide_creator: false,
    hide_author: false,
    anonymize_names: false,
    name_mappings: null,
    publish: true
})

const postTypeIcons = {
    'book-open': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>`,
    'lightbulb': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>`,
    'document-text': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>`,
    'chat-bubble-bottom-center-text': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>`
}

const submit = () => {
    form.post(route('posts.store'))
}

const saveDraft = () => {
    form.publish = false
    submit()
}

const publishStory = () => {
    form.publish = true
    submit()
}
</script>

<template>
    <AppLayout title="Create Post">
        <template #header>
            <h2 class="font-semibold text-xl text-stone-800 leading-tight">
                Create a New Post
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="publishStory" class="space-y-8">
                    <!-- Post Type Selector -->
                    <div class="bg-white rounded-lg shadow p-6 border border-stone-100">
                        <label class="block text-sm font-medium text-stone-700 mb-3">
                            What type of post is this?
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
                                <div class="text-xs mt-1 opacity-75">{{ type.description }}</div>
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
                                placeholder="Give your post a title..."
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
                                placeholder="A short summary of your post..."
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
                        <Link
                            :href="route('dashboard')"
                            class="text-stone-600 hover:text-stone-800"
                        >
                            Cancel
                        </Link>

                        <div class="flex gap-4">
                            <button
                                type="button"
                                @click="saveDraft"
                                :disabled="form.processing"
                                class="px-6 py-3 border border-stone-300 text-stone-700 rounded-lg hover:bg-stone-50 disabled:opacity-50"
                            >
                                Save as Draft
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-3 bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50"
                            >
                                Publish
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
