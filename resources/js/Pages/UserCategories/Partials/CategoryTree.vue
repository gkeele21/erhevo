<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'

const props = defineProps({
    categories: Array,
    parentId: {
        type: Number,
        default: null
    },
    level: {
        type: Number,
        default: 0
    }
})

const editingId = ref(null)
const editForm = useForm({
    name: '',
    description: '',
})

const startEdit = (category) => {
    editingId.value = category.id
    editForm.name = category.name
    editForm.description = category.description || ''
}

const cancelEdit = () => {
    editingId.value = null
    editForm.reset()
}

const saveEdit = (category) => {
    editForm.put(route('user-categories.update', category.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null
            editForm.reset()
        },
    })
}

const deleteCategory = (category) => {
    const hasChildren = category.children && category.children.length > 0
    const message = hasChildren
        ? `Delete "${category.name}" and all its subcategories? This cannot be undone.`
        : `Delete "${category.name}"? This cannot be undone.`

    if (confirm(message)) {
        router.delete(route('user-categories.destroy', category.id), {
            preserveScroll: true
        })
    }
}
</script>

<template>
    <div class="space-y-2">
        <div
            v-for="category in categories"
            :key="category.id"
            :class="level === 0 ? '' : 'ml-6 border-l-2 border-stone-200 pl-4'"
        >
            <!-- Category Item -->
            <div class="flex items-center gap-3 p-3 rounded-lg bg-stone-50 hover:bg-stone-100 transition-colors">
                <template v-if="editingId === category.id">
                    <div class="flex-1 flex gap-2">
                        <input
                            v-model="editForm.name"
                            type="text"
                            class="flex-1 rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500 text-sm"
                            placeholder="Category name"
                            @keyup.enter="saveEdit(category)"
                            @keyup.escape="cancelEdit"
                        >
                        <input
                            v-model="editForm.description"
                            type="text"
                            class="flex-1 rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500 text-sm"
                            placeholder="Description (optional)"
                            @keyup.enter="saveEdit(category)"
                            @keyup.escape="cancelEdit"
                        >
                    </div>
                    <div class="flex gap-2">
                        <button
                            @click="saveEdit(category)"
                            :disabled="editForm.processing"
                            class="text-green-600 hover:text-green-800 text-sm font-medium"
                        >
                            Save
                        </button>
                        <button
                            @click="cancelEdit"
                            class="text-stone-500 hover:text-stone-700 text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </template>
                <template v-else>
                    <div class="flex-1">
                        <span class="font-medium text-stone-800">{{ category.name }}</span>
                        <span v-if="category.description" class="text-stone-500 text-sm ml-2">
                            - {{ category.description }}
                        </span>
                    </div>
                    <div class="flex gap-3">
                        <button
                            @click="startEdit(category)"
                            class="text-stone-500 hover:text-stone-700 text-sm"
                        >
                            Edit
                        </button>
                        <button
                            @click="deleteCategory(category)"
                            class="text-red-500 hover:text-red-700 text-sm"
                        >
                            Delete
                        </button>
                    </div>
                </template>
            </div>

            <!-- Children -->
            <div v-if="category.children?.length" class="mt-2">
                <CategoryTree
                    :categories="category.children"
                    :parent-id="category.id"
                    :level="level + 1"
                />
            </div>
        </div>

        <div v-if="!categories?.length" class="text-center py-4 text-stone-500">
            <p v-if="level === 0">No categories yet. Create one above.</p>
            <p v-else>No subcategories.</p>
        </div>
    </div>
</template>
