<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    stats: Object,
});
</script>

<template>
    <AdminLayout title="Dashboard">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Link :href="route('admin.users.index')" class="bg-white rounded-lg shadow p-6 border border-navy-50 hover:shadow-lg transition-shadow">
                <h3 class="text-lg font-semibold text-navy mb-2">Users</h3>
                <p class="text-3xl font-bold text-teal">{{ stats.users }}</p>
            </Link>

            <div class="bg-white rounded-lg shadow p-6 border border-navy-50">
                <h3 class="text-lg font-semibold text-navy mb-2">Posts</h3>
                <p class="text-3xl font-bold text-teal">{{ stats.posts }}</p>
            </div>

            <Link :href="route('admin.categories.index')" class="bg-white rounded-lg shadow p-6 border border-navy-50 hover:shadow-lg transition-shadow">
                <h3 class="text-lg font-semibold text-navy mb-2">Categories</h3>
                <p class="text-3xl font-bold text-teal">{{ stats.categories }}</p>
                <p v-if="stats.pendingCategories > 0" class="text-sm text-amber mt-1">
                    {{ stats.pendingCategories }} pending approval
                </p>
            </Link>

            <Link :href="route('admin.cfm.study-years.index')" class="bg-white rounded-lg shadow p-6 border border-navy-50 hover:shadow-lg transition-shadow">
                <h3 class="text-lg font-semibold text-navy mb-2">CFM Study Years</h3>
                <p class="text-3xl font-bold text-teal">{{ stats.studyYears }}</p>
            </Link>
        </div>
    </AdminLayout>
</template>
