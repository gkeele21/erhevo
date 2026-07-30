<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StoryCard from '@/Components/Story/StoryCard.vue'

defineProps({
    featuredPosts: Array,
    categories: Array,
    popularTags: Array
})
</script>

<template>
    <AppLayout title="Welcome to Erhevo">
        <!-- Hero -->
        <section class="bg-gradient-to-br from-aqua-50 via-teal-50 to-ivory py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-navy mb-6">
                    A Place Where Words Lift You
                </h1>
                <p class="text-xl text-teal mb-8 max-w-2xl mx-auto">
                    Discover inspiring stories, build lessons and talks worth sharing, and follow study plans
                    that keep your reading on track — with others who appreciate the quiet power of positive words.
                </p>
                <div class="flex justify-center gap-4">
                    <Link
                        :href="route('posts.index')"
                        class="px-6 py-3 bg-white text-navy rounded-lg font-semibold hover:bg-navy-50 shadow-sm transition-colors"
                    >
                        Explore Posts
                    </Link>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('posts.create')"
                        class="px-6 py-3 bg-amber text-white rounded-lg font-semibold hover:bg-amber-600 transition-colors"
                    >
                        Share a Post
                    </Link>
                    <Link
                        v-else
                        :href="route('register')"
                        class="px-6 py-3 bg-amber text-white rounded-lg font-semibold hover:bg-amber-600 transition-colors"
                    >
                        Get Started
                    </Link>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="py-16 bg-[#FAFAFA]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-lg border border-navy-50 shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-navy mb-2">Share Uplifting Posts</h3>
                        <p class="text-teal mb-4">
                            Write and share stories, quotes, and thoughts — publicly, with friends, or just for yourself.
                        </p>
                        <Link :href="route('posts.index')" class="text-amber hover:text-amber-600 font-medium">
                            Explore posts &rarr;
                        </Link>
                    </div>

                    <div class="bg-white rounded-lg border border-navy-50 shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-navy mb-2">Build Lessons &amp; Talks</h3>
                        <p class="text-teal mb-4">
                            Assemble scriptures, talks, quotes, images, and your own ideas into a lesson to teach —
                            or write your own talk and deliver it right from the app.
                        </p>
                        <Link :href="route('lessons.index')" class="text-amber hover:text-amber-600 font-medium">
                            Browse lessons &amp; talks &rarr;
                        </Link>
                    </div>

                    <div class="bg-white rounded-lg border border-navy-50 shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-navy mb-2">Follow a Study Plan</h3>
                        <p class="text-teal mb-4">
                            Set a goal — the Book of Mormon by year's end, or every talk from last conference —
                            and get a reading schedule that tracks your progress.
                        </p>
                        <Link
                            :href="$page.props.auth.user ? route('study-plans.index') : route('register')"
                            class="text-amber hover:text-amber-600 font-medium"
                        >
                            Start a study plan &rarr;
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Featured Posts -->
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-navy mb-8">
                    Featured Posts
                </h2>
                <div v-if="featuredPosts?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <StoryCard
                        v-for="post in featuredPosts"
                        :key="post.id"
                        :post="post"
                    />
                </div>
                <div v-else class="text-center py-8 text-teal">
                    No posts yet. Be the first to share one!
                </div>
                <div class="mt-8 text-center">
                    <Link
                        :href="route('posts.index')"
                        class="text-amber hover:text-amber-600 font-medium"
                    >
                        View all posts &rarr;
                    </Link>
                </div>
            </div>
        </section>

        <!-- Categories -->
        <section class="py-16 bg-[#FAFAFA]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-navy mb-8">
                    Browse by Category
                </h2>
                <div v-if="categories?.length" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Link
                        v-for="category in categories"
                        :key="category.id"
                        :href="route('categories.show', category.slug)"
                        class="p-4 bg-white rounded-lg hover:bg-ivory hover:shadow-sm transition-all border border-navy-50"
                    >
                        <h3 class="font-semibold text-navy">
                            {{ category.name }}
                        </h3>
                        <p class="text-sm text-teal">
                            {{ category.posts_count || 0 }} posts
                        </p>
                    </Link>
                </div>
                <div v-else class="text-center py-8 text-teal">
                    No categories yet.
                </div>
            </div>
        </section>

        <!-- Popular Tags -->
        <section v-if="popularTags?.length" class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-navy mb-8">
                    Popular Tags
                </h2>
                <div class="flex flex-wrap gap-3">
                    <Link
                        v-for="tag in popularTags"
                        :key="tag.id"
                        :href="route('posts.index', { tag: tag.slug })"
                        class="px-4 py-2 bg-navy-50 text-teal rounded-full hover:bg-aqua-50 hover:text-navy transition-colors"
                    >
                        #{{ tag.name }}
                        <span class="text-xs text-teal-300 ml-1">({{ tag.posts_count }})</span>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer (replaces the layout's default footer) -->
        <template #footer>
        <footer class="bg-navy py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <img src="/images/erhevo-logo.png" alt="Erhevo" class="h-40 mx-auto mb-2" />
                    <p class="text-aqua mb-4 italic">
                        A place where words lift you.
                    </p>
                    <div class="flex justify-center gap-6 mb-4">
                        <Link :href="route('about')" class="text-aqua-200 hover:text-gold text-sm">
                            About
                        </Link>
                        <Link :href="route('guide')" class="text-aqua-200 hover:text-gold text-sm">
                            Guide
                        </Link>
                        <Link :href="route('posts.index')" class="text-aqua-200 hover:text-gold text-sm">
                            Posts
                        </Link>
                        <Link :href="route('lessons.index')" class="text-aqua-200 hover:text-gold text-sm">
                            Lessons &amp; Talks
                        </Link>
                        <Link :href="route('categories.index')" class="text-aqua-200 hover:text-gold text-sm">
                            Categories
                        </Link>
                    </div>
                    <p class="text-sm text-teal-300">
                        &copy; {{ new Date().getFullYear() }} Erhevo. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
        </template>
    </AppLayout>
</template>
