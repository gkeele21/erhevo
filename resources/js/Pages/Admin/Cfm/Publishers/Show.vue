<script setup>
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    publisher: Object,
});

const deleteContent = (content) => {
    if (confirm(`Delete "${content.title}"?`)) {
        router.delete(route('admin.cfm.publisher-content.destroy', content.id));
    }
};

const toggleFeatured = (content) => {
    router.post(route('admin.cfm.publisher-content.toggle-featured', content.id));
};

const contentTypeColors = {
    video: 'bg-red-100 text-red-700',
    podcast: 'bg-purple-100 text-purple-700',
    blog: 'bg-blue-100 text-blue-700',
    pdf: 'bg-orange-100 text-orange-700',
    other: 'bg-gray-100 text-gray-700',
};

const contentTypeLabels = {
    video: 'Video',
    podcast: 'Podcast',
    blog: 'Blog',
    pdf: 'PDF',
    other: 'Other',
};
</script>

<template>
    <AdminLayout :title="publisher.name">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Publisher Info -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow border border-navy-50 p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <img
                            v-if="publisher.logo_url"
                            :src="publisher.logo_url"
                            :alt="publisher.name"
                            class="w-16 h-16 rounded-full object-cover"
                        />
                        <div v-else class="w-16 h-16 rounded-full bg-navy-100 flex items-center justify-center">
                            <span class="text-navy text-2xl font-bold">{{ publisher.name.charAt(0) }}</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-navy">{{ publisher.name }}</h3>
                            <div class="flex gap-2 mt-1">
                                <span :class="publisher.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'" class="px-2 py-0.5 text-xs rounded">
                                    {{ publisher.is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <span v-if="publisher.is_verified" class="bg-blue-100 text-blue-700 px-2 py-0.5 text-xs rounded">
                                    Verified
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-if="publisher.description" class="text-sm text-navy mb-4">
                        {{ publisher.description }}
                    </div>

                    <div class="space-y-2 text-sm">
                        <div v-if="publisher.website_url">
                            <span class="text-teal">Website:</span>
                            <a :href="publisher.website_url" target="_blank" class="ml-2 text-navy hover:text-teal">
                                {{ publisher.website_url }}
                            </a>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-teal">Content Items:</span>
                            <span class="font-medium text-navy">{{ publisher.content_count || 0 }}</span>
                        </div>
                    </div>

                    <!-- Social Links -->
                    <div v-if="publisher.social_links && Object.keys(publisher.social_links).length > 0" class="mt-4 pt-4 border-t border-navy-50">
                        <p class="text-sm text-teal mb-2">Social Links:</p>
                        <div class="flex flex-wrap gap-2">
                            <a
                                v-for="(url, platform) in publisher.social_links"
                                :key="platform"
                                :href="url"
                                target="_blank"
                                class="px-2 py-1 bg-navy-50 text-navy text-xs rounded hover:bg-navy-100"
                            >
                                {{ platform }}
                            </a>
                        </div>
                    </div>

                    <div class="mt-6 space-y-2">
                        <Link :href="route('admin.cfm.publishers.edit', publisher.id)">
                            <PrimaryButton class="w-full justify-center">Edit Publisher</PrimaryButton>
                        </Link>
                        <Link :href="route('admin.cfm.publisher-content.create', { publisher_id: publisher.id })">
                            <SecondaryButton class="w-full justify-center">Add Content</SecondaryButton>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Content List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow border border-navy-50">
                    <div class="px-6 py-4 border-b border-navy-50 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-navy">Content ({{ publisher.content?.length || 0 }})</h3>
                    </div>
                    <div v-if="publisher.content && publisher.content.length > 0" class="divide-y divide-navy-50">
                        <div v-for="content in publisher.content" :key="content.id" class="px-6 py-4">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span :class="contentTypeColors[content.content_type]" class="px-2 py-0.5 text-xs rounded">
                                            {{ contentTypeLabels[content.content_type] }}
                                        </span>
                                        <button
                                            @click="toggleFeatured(content)"
                                            :class="content.is_featured ? 'bg-amber text-white' : 'bg-gray-100 text-gray-600'"
                                            class="px-2 py-0.5 text-xs rounded cursor-pointer hover:opacity-80"
                                        >
                                            {{ content.is_featured ? 'Featured' : 'Not Featured' }}
                                        </button>
                                    </div>
                                    <a :href="content.external_url" target="_blank" class="text-teal hover:text-navy font-medium">
                                        {{ content.title }}
                                    </a>
                                    <p v-if="content.cfm_week" class="text-sm text-teal mt-1">
                                        Week {{ content.cfm_week.week_number }}: {{ content.cfm_week.title }}
                                    </p>
                                    <p v-if="content.description" class="text-sm text-navy mt-1 line-clamp-2">
                                        {{ content.description }}
                                    </p>
                                </div>
                                <div class="flex gap-2 ml-4">
                                    <Link :href="route('admin.cfm.publisher-content.edit', content.id)" class="text-teal hover:text-navy text-sm">
                                        Edit
                                    </Link>
                                    <button @click="deleteContent(content)" class="text-red-500 hover:text-red-700 text-sm">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-6 py-8 text-center text-teal">
                        No content yet.
                        <Link :href="route('admin.cfm.publisher-content.create', { publisher_id: publisher.id })" class="text-navy hover:underline ml-1">
                            Add the first piece of content.
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
