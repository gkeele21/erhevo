<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import CategoryTree from './Partials/CategoryTree.vue'

const props = defineProps({
    categories: Array,
})

const showCreateForm = ref(false)
const selectedParent = ref(null)

const form = useForm({
    name: '',
    description: '',
    parent_id: null,
})

const openCreateForm = (parentId = null) => {
    selectedParent.value = parentId
    form.parent_id = parentId
    showCreateForm.value = true
}

const closeCreateForm = () => {
    showCreateForm.value = false
    form.reset()
    selectedParent.value = null
}

const createCategory = () => {
    form.post(route('user-categories.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateForm()
        },
    })
}

const getParentName = () => {
    if (!selectedParent.value) return null
    return props.categories.find(c => c.id === selectedParent.value)?.name
}
</script>

<template>
    <AppLayout title="My Categories">
        <template #header>
            <h2 class="font-semibold text-xl text-stone-800 leading-tight">
                My Categories
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Info Box -->
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                    <p class="text-amber-800 text-sm">
                        Organize your posts with custom categories. Create parent categories and subcategories to keep your content structured.
                    </p>
                </div>

                <!-- Create Category -->
                <div class="bg-white rounded-lg shadow p-6 mb-8 border border-stone-100">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-stone-800">
                            {{ showCreateForm ? (selectedParent ? `Add Subcategory to "${getParentName()}"` : 'Create New Category') : 'Your Categories' }}
                        </h3>
                        <button
                            v-if="!showCreateForm"
                            @click="openCreateForm(null)"
                            class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 text-sm"
                        >
                            New Category
                        </button>
                    </div>

                    <!-- Create Form -->
                    <div v-if="showCreateForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">
                                Name
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                                placeholder="Category name"
                                @keyup.enter="createCategory"
                            >
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">
                                Description (optional)
                            </label>
                            <input
                                v-model="form.description"
                                type="text"
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                                placeholder="Brief description"
                            >
                            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                        </div>

                        <div v-if="!selectedParent && categories.length">
                            <label class="block text-sm font-medium text-stone-700 mb-1">
                                Parent Category (optional)
                            </label>
                            <select
                                v-model="form.parent_id"
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                            >
                                <option :value="null">None (top-level category)</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.parent_id" class="mt-1 text-sm text-red-600">{{ form.errors.parent_id }}</p>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button
                                @click="createCategory"
                                :disabled="form.processing"
                                class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50"
                            >
                                Create Category
                            </button>
                            <button
                                @click="closeCreateForm"
                                class="px-4 py-2 text-stone-600 hover:text-stone-800"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>

                    <!-- Category Tree -->
                    <div v-else>
                        <CategoryTree :categories="categories" />

                        <!-- Quick add subcategory buttons -->
                        <div v-if="categories.length" class="mt-6 pt-4 border-t border-stone-200">
                            <p class="text-sm text-stone-500 mb-2">Quick add subcategory:</p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    @click="openCreateForm(cat.id)"
                                    class="px-3 py-1.5 text-sm bg-stone-100 text-stone-700 rounded-lg hover:bg-stone-200"
                                >
                                    + Add to {{ cat.name }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
