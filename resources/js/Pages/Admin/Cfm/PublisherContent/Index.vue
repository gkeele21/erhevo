<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    content: Object,
    publishers: Array,
    studyYears: Array,
    contentTypes: Array,
    filters: Object,
});

const publisherId = ref(props.filters.publisher_id || '');
const contentType = ref(props.filters.content_type || '');
const studyYearId = ref(props.filters.study_year_id || '');

const applyFilters = () => {
    router.get(route('admin.cfm.publisher-content.index'), {
        publisher_id: publisherId.value || undefined,
        content_type: contentType.value || undefined,
        study_year_id: studyYearId.value || undefined,
    }, { preserveState: true });
};

watch([publisherId, contentType, studyYearId], applyFilters);

const deleteContent = (item) => {
    if (confirm(`Delete "${item.title}"?`)) {
        router.delete(route('admin.cfm.publisher-content.destroy', item.id));
    }
};

const toggleFeatured = (item) => {
    router.post(route('admin.cfm.publisher-content.toggle-featured', item.id));
};

const contentTypeColors = {
    video: 'bg-red-100 text-red-700',
    podcast: 'bg-purple-100 text-purple-700',
    blog: 'bg-blue-100 text-blue-700',
    pdf: 'bg-orange-100 text-orange-700',
    other: 'bg-gray-100 text-gray-700',
};
</script>

<template>
    <AdminLayout title="CFM Publisher Content">
        <div class="mb-4 flex flex-wrap justify-between items-center gap-4">
            <div class="flex gap-4 items-center flex-wrap">
                <select
                    v-model="publisherId"
                    class="border-navy-200 focus:border-teal focus:ring-teal rounded-md shadow-sm"
                >
                    <option value="">All Publishers</option>
                    <option v-for="publisher in publishers" :key="publisher.id" :value="publisher.id">
                        {{ publisher.name }}
                    </option>
                </select>
                <select
                    v-model="contentType"
                    class="border-navy-200 focus:border-teal focus:ring-teal rounded-md shadow-sm"
                >
                    <option value="">All Types</option>
                    <option v-for="type in contentTypes" :key="type.value" :value="type.value">
                        {{ type.label }}
                    </option>
                </select>
                <select
                    v-model="studyYearId"
                    class="border-navy-200 focus:border-teal focus:ring-teal rounded-md shadow-sm"
                >
                    <option value="">All Years</option>
                    <option v-for="year in studyYears" :key="year.id" :value="year.id">
                        {{ year.year }} - {{ year.title }}
                    </option>
                </select>
            </div>
            <Link :href="route('admin.cfm.publisher-content.create')">
                <PrimaryButton>Add Content</PrimaryButton>
            </Link>
        </div>

        <div class="bg-white rounded-lg shadow border border-navy-50">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-navy-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Title</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Publisher</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Week</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Type</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Featured</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-navy-50">
                        <tr v-for="item in content.data" :key="item.id">
                            <td class="px-4 py-3">
                                <a :href="item.external_url" target="_blank" class="text-teal hover:text-navy font-medium">
                                    {{ item.title }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <Link v-if="item.publisher" :href="route('admin.cfm.publishers.show', item.publisher.id)" class="text-teal hover:text-navy">
                                    {{ item.publisher.name }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-sm text-navy">
                                <span v-if="item.cfm_week">
                                    {{ item.cfm_week.study_year?.year }} Week {{ item.cfm_week.week_number }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="contentTypeColors[item.content_type]" class="px-2 py-1 text-xs rounded">
                                    {{ contentTypes.find(t => t.value === item.content_type)?.label || item.content_type }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <button
                                    @click="toggleFeatured(item)"
                                    :class="item.is_featured ? 'bg-amber text-white' : 'bg-gray-100 text-gray-600'"
                                    class="px-2 py-1 text-xs rounded cursor-pointer hover:opacity-80"
                                >
                                    {{ item.is_featured ? 'Yes' : 'No' }}
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <Link :href="route('admin.cfm.publisher-content.edit', item.id)" class="text-teal hover:text-navy text-sm">
                                        Edit
                                    </Link>
                                    <button @click="deleteContent(item)" class="text-red-500 hover:text-red-700 text-sm">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="content.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-teal">
                                No content found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="content.last_page > 1" class="p-4 border-t border-navy-50 flex gap-2">
                <Link
                    v-for="page in content.last_page"
                    :key="page"
                    :href="route('admin.cfm.publisher-content.index', { ...filters, page })"
                    class="px-3 py-1 rounded"
                    :class="page === content.current_page ? 'bg-teal text-white' : 'bg-navy-50 text-navy hover:bg-navy-100'"
                >
                    {{ page }}
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
