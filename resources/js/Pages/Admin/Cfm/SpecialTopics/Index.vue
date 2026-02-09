<script setup>
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    specialTopics: Array,
});

const deleteTopic = (topic) => {
    if (confirm(`Delete special topic "${topic.name}"?`)) {
        router.delete(route('admin.cfm.special-topics.destroy', topic.id));
    }
};
</script>

<template>
    <AdminLayout title="CFM Special Topics">
        <div class="mb-4 flex justify-end">
            <Link :href="route('admin.cfm.special-topics.create')">
                <PrimaryButton>Add Special Topic</PrimaryButton>
            </Link>
        </div>

        <div class="bg-white rounded-lg shadow border border-navy-50">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-navy-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Name</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Slug</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Weeks</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-navy">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-navy-50">
                        <tr v-for="topic in specialTopics" :key="topic.id">
                            <td class="px-4 py-3">
                                <Link :href="route('admin.cfm.special-topics.edit', topic.id)" class="text-teal hover:text-navy font-medium">
                                    {{ topic.name }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-sm text-teal">{{ topic.slug }}</td>
                            <td class="px-4 py-3 text-sm text-navy">{{ topic.weeks_count || 0 }}</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <Link :href="route('admin.cfm.special-topics.edit', topic.id)" class="text-teal hover:text-navy text-sm">
                                        Edit
                                    </Link>
                                    <button @click="deleteTopic(topic)" class="text-red-500 hover:text-red-700 text-sm">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="specialTopics.length === 0">
                            <td colspan="4" class="px-4 py-8 text-center text-teal">
                                No special topics yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
