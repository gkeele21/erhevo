<script setup>
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AiCategorySuggest from './AiCategorySuggest.vue'

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    categories: {
        type: Array,
        default: () => []
    },
    content: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue'])

const isAdmin = computed(() => usePage().props.isAdmin)

const jsonHeaders = () => ({
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-XSRF-TOKEN': decodeURIComponent(
        document.cookie
            .split('; ')
            .find(row => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1] || ''
    )
})

// Categories this user proposed during this session; they await admin
// approval so they aren't in the shared categories list yet.
const pendingCategories = ref([])
const proposing = ref(false)
const error = ref('')

// Top-level categories an admin created inline during this session. They're
// approved immediately but aren't in the categories prop until a reload.
const createdCategories = ref([])
const showCreateForm = ref(false)
const newCategoryName = ref('')
const creating = ref(false)

const allCategories = computed(() => [
    ...props.categories,
    ...createdCategories.value,
    ...pendingCategories.value,
])

const handleSelect = (e) => {
    const value = e.target.value
    if (value === '__create__') {
        showCreateForm.value = true
        e.target.value = props.modelValue || ''
    } else {
        emit('update:modelValue', value)
    }
}

const createCategory = async () => {
    if (!newCategoryName.value.trim()) {
        error.value = 'Please enter a category name'
        return
    }

    creating.value = true
    error.value = ''

    try {
        const response = await fetch('/admin/categories', {
            method: 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify({ name: newCategoryName.value.trim(), parent_id: null })
        })

        const data = await response.json()

        if (response.ok && data.category) {
            createdCategories.value.push(data.category)
            emit('update:modelValue', data.category.id)
            cancelCreate()
        } else {
            error.value = data.message || 'Failed to create category'
        }
    } catch (e) {
        error.value = 'Failed to create category'
    } finally {
        creating.value = false
    }
}

const cancelCreate = () => {
    showCreateForm.value = false
    newCategoryName.value = ''
    error.value = ''
}

const handleAiSuggestion = async (suggestion) => {
    error.value = ''

    // Find matching category by name
    const existing = allCategories.value.find(
        c => c.name.toLowerCase() === suggestion.name.toLowerCase()
    )

    if (existing) {
        emit('update:modelValue', existing.id)
        return
    }

    // New category: propose it for admin approval and select it right away
    proposing.value = true

    try {
        const response = await fetch('/categories', {
            method: 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify({ name: suggestion.name })
        })

        const data = await response.json()

        if (response.ok && data.category) {
            pendingCategories.value.push(data.category)
            emit('update:modelValue', data.category.id)
        } else {
            error.value = data.message || 'Failed to suggest this category'
        }
    } catch (e) {
        error.value = 'Failed to suggest this category'
    } finally {
        proposing.value = false
    }
}
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <label class="block text-sm font-medium text-stone-700">
                Public Category
                <span class="text-stone-400 font-normal">(helps others discover your post)</span>
            </label>
            <AiCategorySuggest
                :content="content"
                :existing-categories="categories"
                type="public"
                @select-category="handleAiSuggestion"
            />
        </div>

        <div class="relative">
            <select
                :value="modelValue"
                @change="handleSelect"
                class="w-full appearance-none rounded-lg border border-stone-300 bg-white py-2.5 pl-4 pr-10 text-stone-900 transition-colors focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 hover:border-stone-400"
            >
                <option value="">Select a category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                </option>
                <option v-for="cat in createdCategories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                </option>
                <option v-for="cat in pendingCategories" :key="cat.id" :value="cat.id">
                    {{ cat.name }} (pending approval)
                </option>
                <template v-if="isAdmin">
                    <option disabled>───────────</option>
                    <option value="__create__">+ Create new category</option>
                </template>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                <svg class="h-5 w-5 text-stone-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>

        <!-- Inline Create Form (admins only) -->
        <div v-if="showCreateForm" class="mt-3 p-4 bg-stone-50 rounded-lg border border-stone-200">
            <h4 class="text-sm font-medium text-stone-700 mb-3">Create New Category</h4>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-stone-600 mb-1">Name</label>
                    <input
                        v-model="newCategoryName"
                        type="text"
                        class="w-full rounded-md border-stone-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                        placeholder="Category name..."
                        @keydown.enter.prevent="createCategory"
                    >
                    <p class="mt-1 text-xs text-stone-500">Creates a top-level public category, visible to everyone.</p>
                </div>

                <div class="flex gap-2">
                    <button
                        type="button"
                        @click="createCategory"
                        :disabled="creating"
                        class="px-3 py-1.5 text-sm font-medium text-white bg-amber-600 rounded-md hover:bg-amber-700 disabled:opacity-50"
                    >
                        {{ creating ? 'Creating...' : 'Create' }}
                    </button>
                    <button
                        type="button"
                        @click="cancelCreate"
                        class="px-3 py-1.5 text-sm font-medium text-stone-600 hover:text-stone-800"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <p v-if="proposing" class="text-xs text-stone-500">Suggesting new category...</p>
        <p v-else-if="pendingCategories.some(c => String(c.id) === String(modelValue))" class="text-xs text-amber-700">
            This is a new category. It will be reviewed by an admin before appearing publicly.
        </p>
        <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
    </div>
</template>
