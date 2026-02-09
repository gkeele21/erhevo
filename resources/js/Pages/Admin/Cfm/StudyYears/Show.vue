<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    studyYear: Object,
});
</script>

<template>
    <AdminLayout :title="studyYear.title">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Year Info -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow border border-navy-50 p-6">
                    <h3 class="text-2xl font-bold text-navy mb-4">{{ studyYear.year }}</h3>
                    <p class="text-teal mb-4">{{ studyYear.title }}</p>

                    <div v-if="studyYear.description" class="text-sm text-navy mb-4">
                        {{ studyYear.description }}
                    </div>

                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-teal">Volumes:</span>
                            <div class="mt-1">
                                <span
                                    v-for="volume in studyYear.volumes"
                                    :key="volume.id"
                                    class="inline-block px-2 py-1 bg-navy-50 text-navy text-xs rounded mr-1 mb-1"
                                >
                                    {{ volume.name }}
                                </span>
                            </div>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-teal">Weeks:</span>
                            <span class="font-medium text-navy">{{ studyYear.weeks?.length || 0 }}</span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <Link :href="route('admin.cfm.study-years.edit', studyYear.id)">
                            <PrimaryButton class="w-full justify-center">Edit Study Year</PrimaryButton>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Weeks -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow border border-navy-50">
                    <div class="px-6 py-4 border-b border-navy-50 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-navy">Weeks</h3>
                        <Link :href="route('admin.cfm.weeks.create')">
                            <PrimaryButton>Add Week</PrimaryButton>
                        </Link>
                    </div>
                    <div v-if="studyYear.weeks && studyYear.weeks.length > 0" class="divide-y divide-navy-50">
                        <div v-for="week in studyYear.weeks" :key="week.id" class="px-6 py-4 flex justify-between items-center">
                            <div>
                                <Link :href="route('admin.cfm.weeks.edit', week.id)" class="text-teal hover:text-navy font-medium">
                                    Week {{ week.week_number }}: {{ week.title }}
                                </Link>
                                <p class="text-sm text-teal mt-1">
                                    {{ new Date(week.start_date).toLocaleDateString() }} - {{ new Date(week.end_date).toLocaleDateString() }}
                                </p>
                            </div>
                            <span v-if="week.is_special_topic" class="px-2 py-1 bg-amber text-white text-xs rounded">
                                Special
                            </span>
                        </div>
                    </div>
                    <div v-else class="px-6 py-4 text-teal">
                        No weeks added yet.
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
