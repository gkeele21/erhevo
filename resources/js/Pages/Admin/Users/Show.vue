<script setup>
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

defineProps({
    user: Object,
});

const toggleAdmin = (user) => {
    if (confirm(`${user.is_admin ? 'Revoke' : 'Grant'} admin access for ${user.name}?`)) {
        router.post(route('admin.users.toggle-admin', user.id));
    }
};

const deleteUser = (user) => {
    if (confirm(`Delete user ${user.name}? This action cannot be undone.`)) {
        router.delete(route('admin.users.destroy', user.id));
    }
};
</script>

<template>
    <AdminLayout :title="user.name">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- User Info -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow border border-navy-50 p-6">
                    <div class="flex items-center mb-4">
                        <img
                            v-if="user.profile_photo_url"
                            :src="user.profile_photo_url"
                            :alt="user.name"
                            class="w-16 h-16 rounded-full object-cover"
                        />
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-navy">{{ user.name }}</h3>
                            <p class="text-sm text-teal">{{ user.email }}</p>
                        </div>
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-teal">Posts:</span>
                            <span class="font-medium text-navy">{{ user.posts_count }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-teal">Categories:</span>
                            <span class="font-medium text-navy">{{ user.categories_count }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-teal">Admin:</span>
                            <span :class="user.is_admin ? 'text-teal' : 'text-navy'" class="font-medium">
                                {{ user.is_admin ? 'Yes' : 'No' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-teal">Joined:</span>
                            <span class="font-medium text-navy">{{ new Date(user.created_at).toLocaleDateString() }}</span>
                        </div>
                    </div>

                    <div class="mt-6 space-y-2">
                        <Link :href="route('admin.users.edit', user.id)">
                            <PrimaryButton class="w-full justify-center">Edit User</PrimaryButton>
                        </Link>
                        <button @click="toggleAdmin(user)" class="w-full px-4 py-2 text-sm font-medium rounded-lg border border-amber text-amber hover:bg-amber hover:text-white transition-colors">
                            {{ user.is_admin ? 'Revoke Admin' : 'Make Admin' }}
                        </button>
                        <DangerButton @click="deleteUser(user)" class="w-full justify-center">
                            Delete User
                        </DangerButton>
                    </div>
                </div>
            </div>

            <!-- Recent Posts -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow border border-navy-50">
                    <div class="px-6 py-4 border-b border-navy-50">
                        <h3 class="text-lg font-semibold text-navy">Recent Posts</h3>
                    </div>
                    <div v-if="user.posts && user.posts.length > 0" class="divide-y divide-navy-50">
                        <div v-for="post in user.posts" :key="post.id" class="px-6 py-4">
                            <Link :href="route('posts.show', post.slug)" class="text-teal hover:text-navy font-medium">
                                {{ post.title }}
                            </Link>
                            <p class="text-sm text-teal mt-1">{{ new Date(post.created_at).toLocaleDateString() }}</p>
                        </div>
                    </div>
                    <div v-else class="px-6 py-4 text-teal">
                        No posts yet.
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
