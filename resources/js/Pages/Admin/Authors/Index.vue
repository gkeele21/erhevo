<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    authors: Object,
    filters: Object,
    callings: Array,
});

const search = ref(props.filters.search || '');
const calling = ref(props.filters.calling || '');

const apply = () => {
    router.get(route('admin.authors.index'), {
        search: search.value || undefined,
        calling: calling.value || undefined,
    }, { preserveState: true, replace: true });
};

const deleteAuthor = (author) => {
    if (confirm(`Delete "${author.full_name}"? Their calling history is removed and any posts are unlinked. This cannot be undone.`)) {
        router.delete(route('admin.authors.destroy', author.id));
    }
};
</script>

<template>
    <AdminLayout title="Authors">
        <div class="bg-white rounded-lg shadow border border-navy-50">
            <div class="p-4 border-b border-navy-50 flex flex-wrap gap-3 items-center">
                <form @submit.prevent="apply" class="flex gap-2 flex-1 min-w-[240px]">
                    <TextInput v-model="search" placeholder="Search authors by name..." class="flex-1" />
                    <PrimaryButton type="submit">Search</PrimaryButton>
                </form>
                <select v-model="calling" @change="apply" class="rounded-lg border-navy-100 text-sm">
                    <option value="">All callings</option>
                    <option v-for="c in callings" :key="c.id" :value="c.id">{{ c.label }}</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-navy-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Name</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Current calling</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Posts</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Callings</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-navy-50">
                        <tr v-for="author in authors.data" :key="author.id">
                            <td class="px-4 py-3">
                                <Link :href="route('admin.authors.edit', author.id)" class="text-teal hover:text-navy font-medium">
                                    {{ author.full_name }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-sm text-teal">{{ author.calling?.full_title || '—' }}</td>
                            <td class="px-4 py-3 text-sm">{{ author.posts_count }}</td>
                            <td class="px-4 py-3 text-sm">{{ author.callings_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-3">
                                    <Link :href="route('admin.authors.edit', author.id)" class="text-teal hover:text-navy text-sm">Edit</Link>
                                    <button @click="deleteAuthor(author)" class="text-red-500 hover:text-red-700 text-sm">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!authors.data.length">
                            <td colspan="5" class="px-4 py-8 text-center text-navy-300">No authors found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="authors.last_page > 1" class="p-4 border-t border-navy-50 flex flex-wrap gap-2">
                <Link
                    v-for="p in authors.last_page"
                    :key="p"
                    :href="route('admin.authors.index', { ...filters, page: p })"
                    class="px-3 py-1 rounded text-sm"
                    :class="p === authors.current_page ? 'bg-teal text-white' : 'bg-navy-50 text-navy hover:bg-navy-100'"
                >
                    {{ p }}
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
