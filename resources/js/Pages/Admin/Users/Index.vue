<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    users: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

const doSearch = () => {
    router.get(route('admin.users.index'), { search: search.value }, { preserveState: true });
};

const toggleAdmin = (user) => {
    if (confirm(`${user.is_admin ? 'Revoke' : 'Grant'} admin access for ${user.name}?`)) {
        router.post(route('admin.users.toggle-admin', user.id));
    }
};

const deleteUser = (user) => {
    if (confirm(`Delete user ${user.name}? This action cannot be undone.`)) {
        router.delete(route('admin.users.destroy', user.id));
    }
};
</script>

<template>
    <AdminLayout title="Users">
        <div class="bg-white rounded-lg shadow border border-navy-50">
            <!-- Search -->
            <div class="p-4 border-b border-navy-50">
                <form @submit.prevent="doSearch" class="flex gap-4">
                    <TextInput v-model="search" placeholder="Search users..." class="flex-1" />
                    <PrimaryButton type="submit">Search</PrimaryButton>
                </form>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-navy-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Name</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Email</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Posts</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Admin</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-navy-50">
                        <tr v-for="user in users.data" :key="user.id">
                            <td class="px-4 py-3">
                                <Link :href="route('admin.users.show', user.id)" class="text-teal hover:text-navy font-medium">
                                    {{ user.name }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-sm text-teal">{{ user.email }}</td>
                            <td class="px-4 py-3 text-sm">{{ user.posts_count }}</td>
                            <td class="px-4 py-3">
                                <span v-if="user.is_admin" class="px-2 py-1 bg-teal text-white text-xs rounded">Admin</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <Link :href="route('admin.users.edit', user.id)" class="text-teal hover:text-navy text-sm">
                                        Edit
                                    </Link>
                                    <button @click="toggleAdmin(user)" class="text-amber hover:text-amber-600 text-sm">
                                        {{ user.is_admin ? 'Revoke Admin' : 'Make Admin' }}
                                    </button>
                                    <button @click="deleteUser(user)" class="text-red-500 hover:text-red-700 text-sm">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="users.last_page > 1" class="p-4 border-t border-navy-50 flex gap-2">
                <Link
                    v-for="page in users.last_page"
                    :key="page"
                    :href="route('admin.users.index', { ...filters, page })"
                    class="px-3 py-1 rounded"
                    :class="page === users.current_page ? 'bg-teal text-white' : 'bg-navy-50 text-navy hover:bg-navy-100'"
                >
                    {{ page }}
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
