<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import GuestEditLayout from '@/Layouts/GuestEditLayout.vue'
import StoryEditor from '@/Components/Story/StoryEditor.vue'

const props = defineProps({
    story: Object,
    token: String,
    categories: Array,
    expiresAt: String,
})

const page = usePage()

const form = useForm({
    title: props.story.title,
    content: props.story.content,
    excerpt: props.story.excerpt || '',
    category_id: props.story.category_id || '',
    tags: props.story.tags || [],
})

const tagInput = ref('')

const addTag = () => {
    const name = tagInput.value.trim()
    if (name && !form.tags.includes(name)) {
        form.tags.push(name)
    }
    tagInput.value = ''
}

const removeTag = (index) => {
    form.tags.splice(index, 1)
}

const handleTagKeydown = (e) => {
    if (e.key === 'Enter') {
        e.preventDefault()
        addTag()
    } else if (e.key === 'Backspace' && !tagInput.value && form.tags.length) {
        removeTag(form.tags.length - 1)
    }
}

const submit = () => {
    form.put(route('posts.shared.update', props.token))
}

const successMessage = computed(() => page.props.flash?.success)
</script>

<template>
    <GuestEditLayout title="Edit Post" :expiresAt="expiresAt">
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Success Message -->
                <div v-if="successMessage" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
                    {{ successMessage }}
                </div>

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
                            <p class="mt-1 text-xs text-stone-500">Note: Image uploads are not available in shared edit mode.</p>
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

                        <!-- Tags (simplified without suggestions) -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-stone-700">
                                Tags
                            </label>
                            <div class="flex flex-wrap gap-2 p-2 border border-stone-300 rounded-lg bg-white min-h-[42px]">
                                <span
                                    v-for="(tag, index) in form.tags"
                                    :key="index"
                                    class="inline-flex items-center gap-1 px-2 py-1 bg-amber-100 text-amber-800 text-sm rounded"
                                >
                                    #{{ tag }}
                                    <button
                                        type="button"
                                        @click="removeTag(index)"
                                        class="text-amber-600 hover:text-amber-800"
                                    >
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </span>
                                <input
                                    v-model="tagInput"
                                    type="text"
                                    @keydown="handleTagKeydown"
                                    placeholder="Add tags..."
                                    class="flex-1 min-w-[120px] border-0 bg-transparent focus:ring-0 text-sm p-0"
                                >
                            </div>
                            <p class="text-xs text-stone-500">Press Enter to add a tag</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-3 bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </GuestEditLayout>
</template>
