<script setup>
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    studyYears: Object,
});

const deleteYear = (year) => {
    if (confirm(`Delete study year ${year.year}? This will also delete all associated weeks.`)) {
        router.delete(route('admin.cfm.study-years.destroy', year.id));
    }
};
</script>

<template>
    <AdminLayout title="CFM Study Years">
        <div class="mb-4 flex justify-end">
            <Link :href="route('admin.cfm.study-years.create')">
                <PrimaryButton>Add Study Year</PrimaryButton>
            </Link>
        </div>

        <div class="bg-white rounded-lg shadow border border-navy-50">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-navy-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Year</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Title</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Volumes</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Weeks</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-navy-50">
                        <tr v-for="year in studyYears.data" :key="year.id">
                            <td class="px-4 py-3 font-medium text-navy">{{ year.year }}</td>
                            <td class="px-4 py-3">
                                <Link :href="route('admin.cfm.study-years.show', year.id)" class="text-teal hover:text-navy">
                                    {{ year.title }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-sm text-teal">
                                {{ year.volumes?.map(v => v.abbreviation).join(', ') || '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm">{{ year.weeks_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <Link :href="route('admin.cfm.study-years.edit', year.id)" class="text-teal hover:text-navy text-sm">
                                        Edit
                                    </Link>
                                    <button @click="deleteYear(year)" class="text-red-500 hover:text-red-700 text-sm">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="studyYears.last_page > 1" class="p-4 border-t border-navy-50 flex gap-2">
                <Link
                    v-for="page in studyYears.last_page"
                    :key="page"
                    :href="route('admin.cfm.study-years.index', { page })"
                    class="px-3 py-1 rounded"
                    :class="page === studyYears.current_page ? 'bg-teal text-white' : 'bg-navy-50 text-navy hover:bg-navy-100'"
                >
                    {{ page }}
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
