<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    talks: Object,
    filters: Object,
    sources: Array,
    years: Array,
});

const search = ref(props.filters.search || '');
const year = ref(props.filters.year || '');
const source = ref(props.filters.source || '');

const apply = () => {
    router.get(route('admin.talks.index'), {
        search: search.value || undefined,
        year: year.value || undefined,
        source: source.value || undefined,
    }, { preserveState: true, replace: true });
};

const deleteTalk = (talk) => {
    if (confirm(`Delete "${talk.title}"? This cannot be undone.`)) {
        router.delete(route('admin.talks.destroy', talk.id), { preserveScroll: true });
    }
};
</script>

<template>
    <AdminLayout title="Talks">
        <div class="bg-white rounded-lg shadow border border-navy-50">
            <div class="p-4 border-b border-navy-50 flex flex-wrap gap-3 items-center">
                <form @submit.prevent="apply" class="flex gap-2 flex-1 min-w-[240px]">
                    <TextInput v-model="search" placeholder="Search by title or speaker..." class="flex-1" />
                    <PrimaryButton type="submit">Search</PrimaryButton>
                </form>
                <select v-model="year" @change="apply" class="rounded-lg border-navy-100 text-sm">
                    <option value="">Any year</option>
                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                </select>
                <select v-model="source" @change="apply" class="rounded-lg border-navy-100 text-sm">
                    <option value="">All sources</option>
                    <option v-for="s in sources" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-navy-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Title</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Speaker</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Calling</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Date</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Source</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-navy-50">
                        <tr v-for="talk in talks.data" :key="talk.id">
                            <td class="px-4 py-3">
                                <Link :href="route('admin.talks.edit', talk.id)" class="text-teal hover:text-navy font-medium">{{ talk.title }}</Link>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ talk.speaker }}</td>
                            <td class="px-4 py-3 text-sm text-navy-300">{{ talk.calling || '—' }}</td>
                            <td class="px-4 py-3 text-sm">{{ talk.date }}</td>
                            <td class="px-4 py-3 text-sm">{{ talk.source }}</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-3">
                                    <a v-if="talk.url" :href="talk.url" target="_blank" rel="noopener" class="text-teal hover:text-navy text-sm">View</a>
                                    <Link :href="route('admin.talks.edit', talk.id)" class="text-teal hover:text-navy text-sm">Edit</Link>
                                    <button @click="deleteTalk(talk)" class="text-red-500 hover:text-red-700 text-sm">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!talks.data.length">
                            <td colspan="6" class="px-4 py-8 text-center text-navy-300">No talks found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="talks.last_page > 1" class="p-4 border-t border-navy-50 flex flex-wrap items-center gap-1">
                <span class="mr-2 text-sm text-navy-300">{{ talks.from }}–{{ talks.to }} of {{ talks.total }}</span>
                <component
                    :is="link.url ? Link : 'span'"
                    v-for="link in talks.links"
                    :key="link.label"
                    :href="link.url"
                    v-html="link.label"
                    class="px-3 py-1 rounded text-sm"
                    :class="link.active ? 'bg-teal text-white' : (link.url ? 'bg-navy-50 text-navy hover:bg-navy-100' : 'text-navy-200')"
                />
            </div>
        </div>
    </AdminLayout>
</template>
