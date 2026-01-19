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
    visibilityOptions: Array,
    authorTypes: Array
})

const form = useForm({
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

const submit = () => {
    form.post(route('stories.store'))
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
    <AppLayout title="Create Story">
        <template #header>
            <h2 class="font-semibold text-xl text-stone-800 leading-tight">
                Create a New Story
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="publishStory" class="space-y-8">
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
                                placeholder="Give your story a title..."
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
                                placeholder="A short summary of your story..."
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
