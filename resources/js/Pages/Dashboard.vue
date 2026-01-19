<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StoryCard from '@/Components/Story/StoryCard.vue'

defineProps({
    myStories: Object,
    friendStories: Array,
    pendingFriendRequestsCount: Number
})
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-navy leading-tight">
                    Dashboard
                </h2>
                <Link
                    :href="route('stories.create')"
                    class="px-4 py-2 bg-amber text-white rounded-lg hover:bg-amber-600"
                >
                    New Story
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Stats/Quick Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6 border border-navy-50">
                        <h3 class="text-lg font-semibold text-navy mb-2">
                            My Stories
                        </h3>
                        <p class="text-3xl font-bold text-teal">
                            {{ myStories?.total || 0 }}
                        </p>
                    </div>

                    <Link
                        :href="route('friends.index')"
                        class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow border border-navy-50"
                    >
                        <h3 class="text-lg font-semibold text-navy mb-2">
                            Friend Requests
                        </h3>
                        <p class="text-3xl font-bold" :class="pendingFriendRequestsCount > 0 ? 'text-amber' : 'text-teal-300'">
                            {{ pendingFriendRequestsCount || 0 }}
                        </p>
                    </Link>

                    <Link
                        :href="route('stories.create')"
                        class="bg-gradient-to-r from-teal to-aqua rounded-lg shadow p-6 text-white hover:shadow-lg transition-shadow"
                    >
                        <h3 class="text-lg font-semibold mb-2">Share Something</h3>
                        <p class="text-sm opacity-90">
                            Write a new uplifting story or thought
                        </p>
                    </Link>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- My Stories -->
                    <div class="lg:col-span-2">
                        <h3 class="text-lg font-semibold text-navy mb-4">
                            My Stories
                        </h3>

                        <div v-if="myStories?.data?.length" class="space-y-4">
                            <div
                                v-for="story in myStories.data"
                                :key="story.id"
                                class="bg-white rounded-lg shadow p-4 border border-navy-50"
                            >
                                <div class="flex justify-between items-start">
                                    <div>
                                        <Link
                                            :href="route('stories.show', story.slug)"
                                            class="text-lg font-semibold text-navy hover:text-teal"
                                        >
                                            {{ story.title }}
                                        </Link>
                                        <div class="flex items-center gap-3 mt-2 text-sm text-teal">
                                            <span class="capitalize">{{ story.visibility }}</span>
                                            <span v-if="story.category">{{ story.category.name }}</span>
                                            <span v-if="story.published_at">
                                                Published {{ new Date(story.published_at).toLocaleDateString() }}
                                            </span>
                                            <span v-else class="text-amber">Draft</span>
                                        </div>
                                    </div>
                                    <Link
                                        :href="route('stories.edit', story.slug)"
                                        class="text-teal-300 hover:text-teal"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </Link>
                                </div>
                            </div>

                            <!-- Pagination -->
                            <div v-if="myStories.last_page > 1" class="flex justify-center gap-2 mt-4">
                                <Link
                                    v-for="page in myStories.last_page"
                                    :key="page"
                                    :href="route('dashboard', { page })"
                                    class="px-3 py-1 rounded"
                                    :class="page === myStories.current_page
                                        ? 'bg-teal text-white'
                                        : 'bg-navy-50 text-navy'"
                                >
                                    {{ page }}
                                </Link>
                            </div>
                        </div>

                        <div v-else class="bg-white rounded-lg shadow p-8 text-center border border-navy-50">
                            <p class="text-teal mb-4">
                                You haven't written any stories yet.
                            </p>
                            <Link
                                :href="route('stories.create')"
                                class="text-amber hover:text-amber-600 font-medium"
                            >
                                Write your first story &rarr;
                            </Link>
                        </div>
                    </div>

                    <!-- Friend Activity -->
                    <div>
                        <h3 class="text-lg font-semibold text-navy mb-4">
                            From Friends
                        </h3>

                        <div v-if="friendStories?.length" class="space-y-4">
                            <StoryCard
                                v-for="story in friendStories"
                                :key="story.id"
                                :story="story"
                            />
                        </div>

                        <div v-else class="bg-white rounded-lg shadow p-6 text-center border border-navy-50">
                            <p class="text-teal mb-4">
                                No stories from friends yet.
                            </p>
                            <Link
                                :href="route('friends.index')"
                                class="text-amber hover:text-amber-600 font-medium"
                            >
                                Find friends &rarr;
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
