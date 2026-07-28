<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StoryCard from '@/Components/Story/StoryCard.vue'
import AiInsights from '@/Components/Dashboard/AiInsights.vue'
import GettingStarted from '@/Components/Dashboard/GettingStarted.vue'

defineProps({
    myPosts: Array,
    myPostsCount: Number,
    myLessons: Array,
    friendPosts: Array,
    pendingFriendRequestsCount: Number,
    userCategories: Array,
    gettingStarted: Array
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
                    :href="route('posts.create')"
                    class="px-4 py-2 bg-amber text-white rounded-lg hover:bg-amber-600"
                >
                    New Post
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Getting Started checklist (new users, until dismissed) -->
                <GettingStarted v-if="gettingStarted" :steps="gettingStarted" class="mb-8" />

                <!-- Stats/Quick Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6 border border-navy-50">
                        <h3 class="text-lg font-semibold text-navy mb-2">
                            My Posts
                        </h3>
                        <p class="text-3xl font-bold text-teal">
                            {{ myPostsCount || 0 }}
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
                        :href="route('posts.create')"
                        class="bg-gradient-to-r from-teal to-aqua rounded-lg shadow p-6 text-white hover:shadow-lg transition-shadow"
                    >
                        <h3 class="text-lg font-semibold mb-2">Share Something</h3>
                        <p class="text-sm opacity-90">
                            Write a new uplifting story or thought
                        </p>
                    </Link>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- My Posts -->
                    <div class="lg:col-span-2">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-navy">
                                My Posts
                            </h3>
                            <Link
                                v-if="myPosts?.length"
                                :href="route('posts.index', { mine: 1 })"
                                class="text-sm text-amber hover:text-amber-600 font-medium"
                            >
                                View all &rarr;
                            </Link>
                        </div>

                        <div v-if="myPosts?.length" class="space-y-4">
                            <div
                                v-for="post in myPosts"
                                :key="post.id"
                                class="bg-white rounded-lg shadow p-4 border border-navy-50"
                            >
                                <div class="flex justify-between items-start">
                                    <div>
                                        <Link
                                            :href="route('posts.show', post.slug)"
                                            class="text-lg font-semibold text-navy hover:text-teal"
                                        >
                                            {{ post.title }}
                                        </Link>
                                        <div class="flex items-center gap-3 mt-2 text-sm text-teal">
                                            <span class="capitalize">{{ post.visibility }}</span>
                                            <span v-if="post.category">{{ post.category.name }}</span>
                                            <span v-if="post.published_at">
                                                Published {{ new Date(post.published_at).toLocaleDateString() }}
                                            </span>
                                            <span v-else class="text-amber">Draft</span>
                                        </div>
                                    </div>
                                    <Link
                                        :href="route('posts.edit', post.slug)"
                                        class="text-teal-300 hover:text-teal"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </Link>
                                </div>
                            </div>

                        </div>

                        <div v-else class="bg-white rounded-lg shadow p-8 text-center border border-navy-50">
                            <p class="text-teal mb-4">
                                You haven't written any posts yet.
                            </p>
                            <Link
                                :href="route('posts.create')"
                                class="text-amber hover:text-amber-600 font-medium"
                            >
                                Write your first post &rarr;
                            </Link>
                        </div>

                        <!-- My Lessons -->
                        <div class="mt-8">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-navy">
                                    My Lessons &amp; Talks
                                </h3>
                                <Link
                                    v-if="myLessons?.length"
                                    :href="route('lessons.index', { mine: 1 })"
                                    class="text-sm text-amber hover:text-amber-600 font-medium"
                                >
                                    View all &rarr;
                                </Link>
                            </div>

                            <div v-if="myLessons?.length" class="space-y-4">
                                <div
                                    v-for="lesson in myLessons"
                                    :key="lesson.id"
                                    class="bg-white rounded-lg shadow p-4 border border-navy-50"
                                >
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <Link
                                                :href="route('lessons.show', lesson.slug)"
                                                class="text-lg font-semibold text-navy hover:text-teal"
                                            >
                                                {{ lesson.title }}
                                            </Link>
                                            <div class="flex items-center gap-3 mt-2 text-sm text-teal">
                                                <span v-if="lesson.kind === 'talk'" class="px-2 py-0.5 bg-aqua-50 text-navy rounded-full text-xs font-medium">Talk</span>
                                                <span class="capitalize">{{ lesson.visibility }}</span>
                                                <span v-if="lesson.cfm_week">{{ lesson.cfm_week.title }}</span>
                                                <span v-if="lesson.published_at">
                                                    Published {{ new Date(lesson.published_at).toLocaleDateString() }}
                                                </span>
                                                <span v-else class="text-amber">Draft</span>
                                                <span v-if="lesson.published_at && lesson.has_draft" class="text-amber">
                                                    Unpublished changes
                                                </span>
                                            </div>
                                        </div>
                                        <Link
                                            :href="route('lessons.edit', lesson.slug)"
                                            class="text-teal-300 hover:text-teal"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="bg-white rounded-lg shadow p-8 text-center border border-navy-50">
                                <p class="text-teal mb-4">
                                    You haven't created any lessons yet.
                                </p>
                                <Link
                                    :href="route('lessons.create')"
                                    class="text-amber hover:text-amber-600 font-medium"
                                >
                                    Create your first lesson &rarr;
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Friend Activity -->
                    <div>
                        <h3 class="text-lg font-semibold text-navy mb-4">
                            From Friends
                        </h3>

                        <div v-if="friendPosts?.length" class="space-y-4">
                            <StoryCard
                                v-for="post in friendPosts"
                                :key="post.id"
                                :post="post"
                            />
                        </div>

                        <div v-else class="bg-white rounded-lg shadow p-6 text-center border border-navy-50">
                            <p class="text-teal mb-4">
                                No posts from friends yet.
                            </p>
                            <Link
                                :href="route('friends.index')"
                                class="text-amber hover:text-amber-600 font-medium"
                            >
                                Find friends &rarr;
                            </Link>
                        </div>

                        <!-- My Categories -->
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold text-navy mb-4">
                                My Categories
                            </h3>

                            <div class="bg-white rounded-lg shadow border border-navy-50">
                                <div v-if="userCategories?.length" class="divide-y divide-navy-50">
                                    <div
                                        v-for="category in userCategories"
                                        :key="category.id"
                                        class="px-4 py-3 flex items-center justify-between"
                                    >
                                        <span class="font-medium text-navy">{{ category.name }}</span>
                                        <span class="text-sm text-teal">
                                            {{ category.children_count }} subcategories
                                        </span>
                                    </div>
                                </div>
                                <div v-else class="p-4 text-center text-teal">
                                    No categories yet
                                </div>
                                <div class="px-4 py-3 bg-navy-50 rounded-b-lg">
                                    <Link
                                        :href="route('user-categories.index')"
                                        class="text-amber hover:text-amber-600 font-medium text-sm"
                                    >
                                        Manage Categories &rarr;
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI Insights -->
                <AiInsights class="mt-8" />
            </div>
        </div>
    </AppLayout>
</template>
