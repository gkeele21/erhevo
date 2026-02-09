<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    weeks: Object,
    studyYears: Array,
    filters: Object,
});

const selectedYear = ref(props.filters.study_year_id || '');

const filterByYear = () => {
    router.get(route('admin.cfm.weeks.index'), { study_year_id: selectedYear.value }, { preserveState: true });
};

const deleteWeek = (week) => {
    if (confirm(`Delete week ${week.week_number}: ${week.title}?`)) {
        router.delete(route('admin.cfm.weeks.destroy', week.id));
    }
};
</script>

<template>
    <AdminLayout title="CFM Weeks">
        <div class="mb-4 flex justify-between items-center">
            <div class="flex gap-4 items-center">
                <select
                    v-model="selectedYear"
                    @change="filterByYear"
                    class="border-navy-200 focus:border-teal focus:ring-teal rounded-md shadow-sm"
                >
                    <option value="">All Years</option>
                    <option v-for="year in studyYears" :key="year.id" :value="year.id">
                        {{ year.year }} - {{ year.title }}
                    </option>
                </select>
            </div>
            <Link :href="route('admin.cfm.weeks.create')">
                <PrimaryButton>Add Week</PrimaryButton>
            </Link>
        </div>

        <div class="bg-white rounded-lg shadow border border-navy-50">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-navy-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Week</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Title</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Year</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Dates</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Type</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-navy-50">
                        <tr v-for="week in weeks.data" :key="week.id">
                            <td class="px-4 py-3 font-medium text-navy">{{ week.week_number }}</td>
                            <td class="px-4 py-3">
                                <Link :href="route('admin.cfm.weeks.edit', week.id)" class="text-teal hover:text-navy">
                                    {{ week.title }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-sm text-teal">{{ week.study_year?.year }}</td>
                            <td class="px-4 py-3 text-sm text-teal">
                                {{ new Date(week.start_date).toLocaleDateString() }} - {{ new Date(week.end_date).toLocaleDateString() }}
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="week.is_special_topic" class="px-2 py-1 bg-amber text-white text-xs rounded">
                                    Special
                                </span>
                                <span v-else class="px-2 py-1 bg-teal text-white text-xs rounded">
                                    Regular
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <Link :href="route('admin.cfm.weeks.edit', week.id)" class="text-teal hover:text-navy text-sm">
                                        Edit
                                    </Link>
                                    <button @click="deleteWeek(week)" class="text-red-500 hover:text-red-700 text-sm">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="weeks.last_page > 1" class="p-4 border-t border-navy-50 flex gap-2">
                <Link
                    v-for="page in weeks.last_page"
                    :key="page"
                    :href="route('admin.cfm.weeks.index', { ...filters, page })"
                    class="px-3 py-1 rounded"
                    :class="page === weeks.current_page ? 'bg-teal text-white' : 'bg-navy-50 text-navy hover:bg-navy-100'"
                >
                    {{ page }}
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
