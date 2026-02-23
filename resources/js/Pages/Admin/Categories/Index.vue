<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    categories: Object,
});

const form = useForm({
    name: '',
    description: '',
});

const editingId = ref(null);
const editForm = useForm({
    name: '',
    description: '',
});

const createCategory = () => {
    form.post(route('admin.categories.store'), {
        onSuccess: () => form.reset(),
    });
};

const startEdit = (category) => {
    editingId.value = category.id;
    editForm.name = category.name;
    editForm.description = category.description || '';
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.reset();
};

const saveEdit = (category) => {
    editForm.put(route('admin.categories.update', category.id), {
        onSuccess: () => {
            editingId.value = null;
            editForm.reset();
        },
    });
};

const approveCategory = (category) => {
    router.post(route('admin.categories.approve', category.id));
};

const rejectCategory = (category) => {
    if (confirm(`Reject and delete category "${category.name}"?`)) {
        router.post(route('admin.categories.reject', category.id));
    }
};

const deleteCategory = (category) => {
    if (confirm(`Delete category "${category.name}"? This action cannot be undone.`)) {
        router.delete(route('admin.categories.destroy', category.id));
    }
};
</script>

<template>
    <AdminLayout title="Categories">
        <div class="space-y-6">
            <!-- Create New Category -->
            <div class="bg-white rounded-lg shadow border border-navy-50 p-6">
                <h2 class="text-lg font-semibold text-navy mb-4">Create Category</h2>
                <form @submit.prevent="createCategory" class="flex gap-4 items-end">
                    <div class="flex-1">
                        <InputLabel for="name" value="Name" />
                        <TextInput id="name" v-model="form.name" class="w-full" required />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="flex-1">
                        <InputLabel for="description" value="Description" />
                        <TextInput id="description" v-model="form.description" class="w-full" />
                        <InputError :message="form.errors.description" />
                    </div>
                    <PrimaryButton type="submit" :disabled="form.processing">Create</PrimaryButton>
                </form>
            </div>

            <!-- Categories Table -->
            <div class="bg-white rounded-lg shadow border border-navy-50">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Name</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Description</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Status</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Suggested By</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-50">
                            <tr v-for="category in categories.data" :key="category.id">
                                <template v-if="editingId === category.id">
                                    <td class="px-4 py-3">
                                        <TextInput v-model="editForm.name" class="w-full" />
                                        <InputError :message="editForm.errors.name" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <TextInput v-model="editForm.description" class="w-full" />
                                        <InputError :message="editForm.errors.description" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <span v-if="category.is_approved" class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Approved</span>
                                        <span v-else class="px-2 py-1 bg-amber-100 text-amber-800 text-xs rounded">Pending</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-teal">
                                        {{ category.user?.name || 'Admin' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2">
                                            <button @click="saveEdit(category)" class="text-green-600 hover:text-green-800 text-sm" :disabled="editForm.processing">
                                                Save
                                            </button>
                                            <button @click="cancelEdit" class="text-gray-500 hover:text-gray-700 text-sm">
                                                Cancel
                                            </button>
                                        </div>
                                    </td>
                                </template>
                                <template v-else>
                                    <td class="px-4 py-3 font-medium text-navy">{{ category.name }}</td>
                                    <td class="px-4 py-3 text-sm text-teal">{{ category.description || '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span v-if="category.is_approved" class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Approved</span>
                                        <span v-else class="px-2 py-1 bg-amber-100 text-amber-800 text-xs rounded">Pending</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-teal">
                                        {{ category.user?.name || 'Admin' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2">
                                            <button @click="startEdit(category)" class="text-teal hover:text-navy text-sm">
                                                Edit
                                            </button>
                                            <template v-if="!category.is_approved">
                                                <button @click="approveCategory(category)" class="text-green-600 hover:text-green-800 text-sm">
                                                    Approve
                                                </button>
                                                <button @click="rejectCategory(category)" class="text-red-500 hover:text-red-700 text-sm">
                                                    Reject
                                                </button>
                                            </template>
                                            <button v-else @click="deleteCategory(category)" class="text-red-500 hover:text-red-700 text-sm">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </template>
                            </tr>
                            <tr v-if="categories.data.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    No categories found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="categories.last_page > 1" class="p-4 border-t border-navy-50 flex gap-2">
                    <Link
                        v-for="page in categories.last_page"
                        :key="page"
                        :href="route('admin.categories.index', { page })"
                        class="px-3 py-1 rounded"
                        :class="page === categories.current_page ? 'bg-teal text-white' : 'bg-navy-50 text-navy hover:bg-navy-100'"
                    >
                        {{ page }}
                    </Link>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
