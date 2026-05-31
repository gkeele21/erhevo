<script setup>
import { ref, computed, watch } from 'vue'
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

const emit = defineEmits(['update:modelValue', 'categoryCreated'])

const showCreateForm = ref(false)
const newCategoryName = ref('')
const newCategoryParentId = ref('')
const creating = ref(false)
const error = ref('')

// Flatten categories for display
const flattenedCategories = computed(() => {
    const result = []
    props.categories.forEach(cat => {
        result.push({ ...cat, depth: 0 })
        if (cat.children) {
            cat.children.forEach(child => {
                result.push({ ...child, depth: 1 })
            })
        }
    })
    return result
})

// Only root categories can be parents
const parentOptions = computed(() => {
    return props.categories.filter(cat => !cat.parent_id)
})

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
        const response = await fetch('/my-categories', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie
                        .split('; ')
                        .find(row => row.startsWith('XSRF-TOKEN='))
                        ?.split('=')[1] || ''
                ),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                name: newCategoryName.value.trim(),
                parent_id: newCategoryParentId.value || null,
            })
        })

        if (response.ok) {
            // Emit event to parent to refresh categories
            emit('categoryCreated', {
                name: newCategoryName.value.trim(),
                parent_id: newCategoryParentId.value || null,
            })

            // Reset form
            newCategoryName.value = ''
            newCategoryParentId.value = ''
            showCreateForm.value = false
        } else {
            const data = await response.json()
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
    newCategoryParentId.value = ''
    error.value = ''
}

const handleAiSuggestion = (suggestion) => {
    // Check if category exists
    const existing = flattenedCategories.value.find(
        c => c.name.toLowerCase() === suggestion.name.toLowerCase()
    )

    if (existing) {
        emit('update:modelValue', existing.id)
    } else {
        // Pre-fill the create form
        newCategoryName.value = suggestion.name
        newCategoryParentId.value = ''
        showCreateForm.value = true
    }
}
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <label class="block text-sm font-medium text-stone-700">
                My Category
                <span class="text-stone-400 font-normal">(for personal organization)</span>
            </label>
            <AiCategorySuggest
                :content="content"
                :existing-categories="categories"
                type="user"
                @select-category="handleAiSuggestion"
            />
        </div>

        <div class="relative">
            <select
                :value="modelValue"
                @change="handleSelect"
                class="w-full appearance-none rounded-lg border border-stone-300 bg-white py-2.5 pl-4 pr-10 text-stone-900 transition-colors focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 hover:border-stone-400"
            >
                <option value="">None</option>
                <option
                    v-for="cat in flattenedCategories"
                    :key="cat.id"
                    :value="cat.id"
                >
                    {{ cat.depth === 1 ? '\u00A0\u00A0\u00A0\u00A0' : '' }}{{ cat.name }}
                </option>
                <option disabled>───────────</option>
                <option value="__create__">+ Create new category</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                <svg class="h-5 w-5 text-stone-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>

        <!-- Inline Create Form -->
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
                </div>

                <div>
                    <label class="block text-xs font-medium text-stone-600 mb-1">
                        Parent Category <span class="text-stone-400">(optional)</span>
                    </label>
                    <div class="relative">
                        <select
                            v-model="newCategoryParentId"
                            class="w-full appearance-none rounded-lg border border-stone-300 bg-white py-2 pl-3 pr-10 text-sm text-stone-900 transition-colors focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 hover:border-stone-400"
                        >
                            <option value="">None (top-level)</option>
                            <option v-for="cat in parentOptions" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg class="h-4 w-4 text-stone-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                <p v-if="error" class="text-xs text-red-600">{{ error }}</p>

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
    </div>
</template>
