<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    posts: Object,
    filters: Object,
    postTypes: Array,
});

const search = ref(props.filters.search || '');
const type = ref(props.filters.type || '');

const typeLabels = computed(() => Object.fromEntries(props.postTypes.map((t) => [t.value, t.label])));

const apply = () => {
    router.get(route('admin.posts.index'), {
        search: search.value || undefined,
        type: type.value || undefined,
    }, { preserveState: true, replace: true });
};

const deletePost = (post) => {
    if (confirm(`Delete "${post.title}"? This cannot be undone.`)) {
        router.delete(route('admin.posts.destroy', post.id), { preserveScroll: true });
    }
};

const fmt = (d) => (d ? new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '—');
</script>

<template>
    <AdminLayout title="Posts">
        <div class="bg-white rounded-lg shadow border border-navy-50">
            <div class="p-4 border-b border-navy-50 flex flex-wrap gap-3 items-center">
                <form @submit.prevent="apply" class="flex gap-2 flex-1 min-w-[240px]">
                    <TextInput v-model="search" placeholder="Search by title, author, or creator..." class="flex-1" />
                    <PrimaryButton type="submit">Search</PrimaryButton>
                </form>
                <select v-model="type" @change="apply" class="rounded-lg border-navy-100 text-sm">
                    <option value="">All types</option>
                    <option v-for="t in postTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-navy-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Title</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Type</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Author</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Creator</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Visibility</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Published</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-navy-50">
                        <tr v-for="post in posts.data" :key="post.id">
                            <td class="px-4 py-3">
                                <Link :href="route('posts.show', post.slug)" class="text-teal hover:text-navy font-medium">{{ post.title }}</Link>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ typeLabels[post.post_type] || post.post_type }}</td>
                            <td class="px-4 py-3 text-sm">{{ post.author?.full_name || '—' }}</td>
                            <td class="px-4 py-3 text-sm text-teal">{{ post.user?.name || '—' }}</td>
                            <td class="px-4 py-3 text-sm">{{ post.visibility }}</td>
                            <td class="px-4 py-3 text-sm">{{ fmt(post.published_at) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-3">
                                    <Link :href="route('posts.edit', post.slug)" class="text-teal hover:text-navy text-sm">Edit</Link>
                                    <button @click="deletePost(post)" class="text-red-500 hover:text-red-700 text-sm">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!posts.data.length">
                            <td colspan="7" class="px-4 py-8 text-center text-navy-300">No posts found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="posts.last_page > 1" class="p-4 border-t border-navy-50 flex flex-wrap gap-2">
                <Link
                    v-for="p in posts.last_page"
                    :key="p"
                    :href="route('admin.posts.index', { ...filters, page: p })"
                    class="px-3 py-1 rounded text-sm"
                    :class="p === posts.current_page ? 'bg-teal text-white' : 'bg-navy-50 text-navy hover:bg-navy-100'"
                >
                    {{ p }}
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
