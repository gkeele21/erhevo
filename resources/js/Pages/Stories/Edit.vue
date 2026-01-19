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
    visibilityOptions: Array,
    authorTypes: Array
})

const form = useForm({
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

const submit = () => {
    form.put(route('stories.update', props.story.slug))
}

const deleteStory = () => {
    router.delete(route('stories.destroy', props.story.slug))
}
</script>

<template>
    <AppLayout title="Edit Story">
        <template #header>
            <h2 class="font-semibold text-xl text-stone-800 leading-tight">
                Edit Story
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-8">
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
                            Delete Story
                        </button>

                        <div class="flex gap-4">
                            <Link
                                :href="route('stories.show', story.slug)"
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
                        Delete Story
                    </h3>
                    <p class="text-stone-600 mb-6">
                        Are you sure you want to delete this story? This action cannot be undone.
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
