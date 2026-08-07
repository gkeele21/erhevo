<script setup>
import { Head, useForm, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import StoryEditor from '@/Components/Story/StoryEditor.vue'
import VisibilitySelector from '@/Components/Story/VisibilitySelector.vue'
import TagInput from '@/Components/Story/TagInput.vue'
import PrivacyOptions from '@/Components/Story/PrivacyOptions.vue'
import AuthorInput from '@/Components/Story/AuthorInput.vue'
import AiExcerptGenerator from '@/Components/Story/AiExcerptGenerator.vue'
import AiTitleSuggest from '@/Components/Story/AiTitleSuggest.vue'
import AiScriptureSuggest from '@/Components/Story/AiScriptureSuggest.vue'
import AiPrivacyCheck from '@/Components/Story/AiPrivacyCheck.vue'
import SourceLinkInput from '@/Components/Story/SourceLinkInput.vue'
import UserCategoryInput from '@/Components/Story/UserCategoryInput.vue'
import PublicCategoryInput from '@/Components/Story/PublicCategoryInput.vue'
import LdsContentSection from '@/Components/Story/LdsContentSection.vue'

const props = defineProps({
    post: Object,
    sharedUserIds: Array,
    categories: Array,
    userCategories: Array,
    postTypes: Array,
    visibilityOptions: Array,
    authorTypes: Array,
    cfmWeeks: Array,
    currentCfmWeek: Object,
    churchCallings: Array,
    friends: Array
})

const form = useForm({
    post_type: props.post.post_type || 'story',
    title: props.post.title,
    content: props.post.content,
    excerpt: props.post.excerpt || '',
    cover_image: props.post.cover_image || '',
    category_id: props.post.category_id || '',
    user_category_id: props.post.user_category_id || '',
    tags: props.post.tags?.map(t => t.name) || [],
    cfm_week_ids: props.post.cfm_weeks?.map(w => w.id) || [],
    author_type: props.post.author_type,
    // Name shown in the author picker; sourced from the linked author entity.
    author_text: props.post.author?.full_name || '',
    author_id: props.post.author_id,
    church_calling_id: props.post.church_calling_id || '',
    // date_given serializes as an ISO datetime; the date input needs YYYY-MM-DD.
    date_given: props.post.date_given ? props.post.date_given.slice(0, 10) : '',
    source_url: props.post.source_url || '',
    visibility: props.post.visibility,
    shared_user_ids: props.sharedUserIds ?? [],
    hide_creator: props.post.hide_creator,
    hide_author: props.post.hide_author,
    anonymize_names: props.post.anonymize_names,
    name_mappings: props.post.name_mappings,
    publish: !!props.post.published_at
})

const showDeleteModal = ref(false)

const postTypeIcons = {
    'book-open': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>`,
    'lightbulb': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>`,
    'document-text': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>`,
    'chat-bubble-bottom-center-text': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>`,
    'clipboard-document-list': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>`,
    'film': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 4h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1z"/>`,
    'photo': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>`,
    'academic-cap': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/>`
}

const submit = () => {
    form.put(route('posts.update', props.post.slug))
}

const deletePost = () => {
    router.delete(route('posts.destroy', props.post.slug))
}

const handleSourceText = ({ text, title }) => {
    if (form.content) {
        form.content += '\n\n' + text
    } else {
        form.content = text
    }
    if (title && !form.title) {
        form.title = title
    }
}

const handleScriptureAdd = (suggestion) => {
    const reference = suggestion.reference
    if (form.content) {
        form.content += `\n\n> ${reference}`
    } else {
        form.content = `> ${reference}`
    }
}

const handleCategoryCreated = () => {
    // Reload the page data to get updated categories
    router.reload({ only: ['userCategories'] })
}

// --- Cover image (for image posts) ---
const uploadingImage = ref(false)
const imageUploadError = ref('')

const uploadCoverImage = async (e) => {
    const file = e.target.files[0]
    if (!file) return
    uploadingImage.value = true
    imageUploadError.value = ''
    try {
        const data = new FormData()
        data.append('image', file)
        const res = await axios.post('/upload-image', data)
        form.cover_image = res.data.url
    } catch (err) {
        imageUploadError.value = err.response?.data?.message || 'Upload failed — try a smaller image (max 5MB).'
    } finally {
        uploadingImage.value = false
        e.target.value = ''
    }
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

                    <!-- Image (for image posts) -->
                    <div v-if="form.post_type === 'image' || form.post_type === 'scripture_help'" class="bg-white rounded-lg shadow p-6 border border-stone-100 space-y-3">
                        <label class="block text-sm font-medium text-stone-700">
                            {{ form.post_type === 'image' ? 'Image' : 'Image (optional)' }}
                        </label>
                        <img v-if="form.cover_image" :src="form.cover_image" alt="" class="max-h-64 rounded-lg">
                        <div class="flex flex-wrap items-center gap-3">
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-stone-300 px-3 py-2 text-sm text-stone-700 hover:bg-stone-50">
                                <span>{{ uploadingImage ? 'Uploading…' : (form.cover_image ? 'Replace image' : 'Upload an image') }}</span>
                                <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" :disabled="uploadingImage" @change="uploadCoverImage">
                            </label>
                            <span class="text-sm text-stone-400">or</span>
                            <input v-model="form.cover_image" type="url" placeholder="Paste an image URL..." class="flex-1 min-w-48 rounded-lg border-stone-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                        </div>
                        <p v-if="imageUploadError" class="text-sm text-red-600">{{ imageUploadError }}</p>
                        <p v-if="form.errors.cover_image" class="text-sm text-red-600">{{ form.errors.cover_image }}</p>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6 space-y-6 border border-stone-100">
                        <!-- Title -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium text-stone-700">
                                    Title
                                </label>
                                <AiTitleSuggest
                                    :content="form.content"
                                    @select-title="(title) => form.title = title"
                                />
                            </div>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500 text-lg"
                            >
                            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                        </div>

                        <!-- Source link (e.g. a Facebook post this came from) -->
                        <SourceLinkInput
                            v-model="form.source_url"
                            @text-fetched="handleSourceText"
                        />

                        <!-- Content Editor -->
                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">
                                Content
                            </label>
                            <StoryEditor v-model="form.content" />
                            <p v-if="form.errors.content" class="mt-1 text-sm text-red-600">{{ form.errors.content }}</p>
                            <div class="mt-2">
                                <AiScriptureSuggest
                                    :content="form.content"
                                    @add-scripture="handleScriptureAdd"
                                />
                            </div>
                        </div>

                        <!-- Excerpt -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium text-stone-700">
                                    Excerpt (optional)
                                </label>
                                <AiExcerptGenerator
                                    :content="form.content"
                                    @excerpt-generated="(excerpt) => form.excerpt = excerpt"
                                />
                            </div>
                            <textarea
                                v-model="form.excerpt"
                                rows="2"
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                            ></textarea>
                        </div>

                        <!-- User Category -->
                        <UserCategoryInput
                            v-model="form.user_category_id"
                            :categories="userCategories"
                            :content="form.content"
                            @category-created="handleCategoryCreated"
                        />

                        <!-- Tags -->
                        <TagInput v-model="form.tags" :content="form.content" />

                        <!-- Author -->
                        <AuthorInput
                            v-model:author-type="form.author_type"
                            v-model:author-text="form.author_text"
                            v-model:author-id="form.author_id"
                            :author-types="authorTypes"
                        />

                        <!-- Date given + calling (quotes) -->
                        <div v-if="form.post_type === 'quote'" class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-stone-700 mb-1">
                                    Date given (optional)
                                </label>
                                <input
                                    v-model="form.date_given"
                                    type="date"
                                    class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                                >
                                <p class="mt-1 text-xs text-stone-500">When the quote was originally said or written.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-stone-700 mb-1">
                                    Calling when given (optional)
                                </label>
                                <select
                                    v-model="form.church_calling_id"
                                    class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                                >
                                    <option value="">— None —</option>
                                    <option v-for="c in churchCallings" :key="c.id" :value="c.id">{{ c.label }}</option>
                                </select>
                                <p class="mt-1 text-xs text-stone-500">The author's calling at the time.</p>
                            </div>
                        </div>
                    </div>

                    <!-- LDS Content Section -->
                    <LdsContentSection
                        v-if="$page.props.userSettings?.show_lds_content"
                        v-model:cfm-week-ids="form.cfm_week_ids"
                        :cfm-weeks="cfmWeeks"
                        :current-cfm-week="currentCfmWeek"
                    />

                    <!-- Visibility & Privacy -->
                    <div class="bg-white rounded-lg shadow p-6 space-y-6 border border-stone-100">
                        <div class="flex items-start justify-between gap-4">
                            <VisibilitySelector
                                v-model="form.visibility"
                                v-model:shared-user-ids="form.shared_user_ids"
                                :options="visibilityOptions"
                                :friends="friends"
                                class="flex-1"
                            />
                            <AiPrivacyCheck
                                :content="form.content"
                                :current-visibility="form.visibility"
                                @update-visibility="(v) => form.visibility = v"
                            />
                        </div>

                        <PrivacyOptions
                            v-model:hide-creator="form.hide_creator"
                            v-model:hide-author="form.hide_author"
                            v-model:anonymize-names="form.anonymize_names"
                            :visibility="form.visibility"
                        />

                        <!-- Public Category (only for public posts) -->
                        <PublicCategoryInput
                            v-if="form.visibility === 'public'"
                            v-model="form.category_id"
                            :categories="categories"
                            :content="form.content"
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
                                :href="route('posts.share.index', post.slug)"
                                class="px-6 py-3 border border-amber-300 text-amber-700 rounded-lg hover:bg-amber-50 flex items-center gap-2"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                </svg>
                                Share
                            </Link>
                            <Link
                                :href="route('posts.show', post.slug)"
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
                            @click="deletePost"
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
