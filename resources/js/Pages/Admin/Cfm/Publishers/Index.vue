<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    publishers: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

let debounceTimer = null;
const applyFilters = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route('admin.cfm.publishers.index'), {
            search: search.value || undefined,
            status: status.value || undefined,
        }, { preserveState: true });
    }, 300);
};

watch([search, status], applyFilters);

const deletePublisher = (publisher) => {
    if (confirm(`Delete publisher "${publisher.name}"? This will also delete all their content.`)) {
        router.delete(route('admin.cfm.publishers.destroy', publisher.id));
    }
};

const toggleVerified = (publisher) => {
    router.post(route('admin.cfm.publishers.toggle-verified', publisher.id));
};

const toggleActive = (publisher) => {
    router.post(route('admin.cfm.publishers.toggle-active', publisher.id));
};
</script>

<template>
    <AdminLayout title="CFM Publishers">
        <div class="mb-4 flex flex-wrap justify-between items-center gap-4">
            <div class="flex gap-4 items-center">
                <TextInput
                    v-model="search"
                    type="text"
                    placeholder="Search publishers..."
                    class="w-64"
                />
                <select
                    v-model="status"
                    class="border-navy-200 focus:border-teal focus:ring-teal rounded-md shadow-sm"
                >
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="verified">Verified</option>
                </select>
            </div>
            <Link :href="route('admin.cfm.publishers.create')">
                <PrimaryButton>Add Publisher</PrimaryButton>
            </Link>
        </div>

        <div class="bg-white rounded-lg shadow border border-navy-50">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-navy-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Publisher</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Website</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Content</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Status</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-navy-50">
                        <tr v-for="publisher in publishers.data" :key="publisher.id">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="publisher.logo_url"
                                        :src="publisher.logo_url"
                                        :alt="publisher.name"
                                        class="w-8 h-8 rounded-full object-cover"
                                    />
                                    <div v-else class="w-8 h-8 rounded-full bg-navy-100 flex items-center justify-center">
                                        <span class="text-navy text-sm font-medium">{{ publisher.name.charAt(0) }}</span>
                                    </div>
                                    <Link :href="route('admin.cfm.publishers.show', publisher.id)" class="text-teal hover:text-navy font-medium">
                                        {{ publisher.name }}
                                    </Link>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <a v-if="publisher.website_url" :href="publisher.website_url" target="_blank" class="text-teal hover:text-navy">
                                    {{ publisher.website_url.replace(/^https?:\/\//, '').split('/')[0] }}
                                </a>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-navy">
                                {{ publisher.content_count || 0 }} items
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <button
                                        @click="toggleActive(publisher)"
                                        :class="publisher.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                        class="px-2 py-1 text-xs rounded cursor-pointer hover:opacity-80"
                                    >
                                        {{ publisher.is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                    <button
                                        @click="toggleVerified(publisher)"
                                        :class="publisher.is_verified ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'"
                                        class="px-2 py-1 text-xs rounded cursor-pointer hover:opacity-80"
                                    >
                                        {{ publisher.is_verified ? 'Verified' : 'Unverified' }}
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <Link :href="route('admin.cfm.publishers.show', publisher.id)" class="text-teal hover:text-navy text-sm">
                                        View
                                    </Link>
                                    <Link :href="route('admin.cfm.publishers.edit', publisher.id)" class="text-teal hover:text-navy text-sm">
                                        Edit
                                    </Link>
                                    <button @click="deletePublisher(publisher)" class="text-red-500 hover:text-red-700 text-sm">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="publishers.data.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-teal">
                                No publishers found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="publishers.last_page > 1" class="p-4 border-t border-navy-50 flex gap-2">
                <Link
                    v-for="page in publishers.last_page"
                    :key="page"
                    :href="route('admin.cfm.publishers.index', { ...filters, page })"
                    class="px-3 py-1 rounded"
                    :class="page === publishers.current_page ? 'bg-teal text-white' : 'bg-navy-50 text-navy hover:bg-navy-100'"
                >
                    {{ page }}
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
