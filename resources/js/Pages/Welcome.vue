<script setup>
import { Head, Link } from '@inertiajs/vue3'
import StoryCard from '@/Components/Story/StoryCard.vue'

defineProps({
    featuredStories: Array,
    categories: Array,
    popularTags: Array
})
</script>

<template>
    <Head title="Welcome to Erhevo" />

    <div class="min-h-screen bg-[#FAFAFA]">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex justify-between items-center">
                    <Link href="/">
                        <img src="/images/erhevo-logo.png" alt="Erhevo" class="h-16" />
                    </Link>
                    <nav class="flex items-center gap-6">
                        <Link
                            :href="route('posts.index')"
                            class="text-teal hover:text-navy transition-colors"
                        >
                            Posts
                        </Link>
                        <Link
                            :href="route('categories.index')"
                            class="text-teal hover:text-navy transition-colors"
                        >
                            Categories
                        </Link>
                        <Link
                            :href="route('about')"
                            class="text-teal hover:text-navy transition-colors"
                        >
                            About
                        </Link>
                        <template v-if="$page.props.auth.user">
                            <Link
                                :href="route('dashboard')"
                                class="text-teal hover:text-navy transition-colors"
                            >
                                Dashboard
                            </Link>
                        </template>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-teal hover:text-navy transition-colors"
                            >
                                Log in
                            </Link>
                            <Link
                                :href="route('register')"
                                class="px-4 py-2 bg-amber text-white rounded-lg hover:bg-amber-600 transition-colors"
                            >
                                Sign up
                            </Link>
                        </template>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Hero -->
        <section class="bg-gradient-to-br from-aqua-50 via-teal-50 to-ivory py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-navy mb-6">
                    A Place Where Words Lift You
                </h1>
                <p class="text-xl text-teal mb-8 max-w-2xl mx-auto">
                    Discover inspiring stories, share uplifting thoughts, and connect with others who appreciate the quiet power of positive words.
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

        <!-- Featured Posts -->
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-navy mb-8">
                    Featured Posts
                </h2>
                <div v-if="featuredStories?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <StoryCard
                        v-for="story in featuredStories"
                        :key="story.id"
                        :story="story"
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
                            {{ category.stories_count || 0 }} stories
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
                        <span class="text-xs text-teal-300 ml-1">({{ tag.stories_count }})</span>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
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
                        <Link :href="route('posts.index')" class="text-aqua-200 hover:text-gold text-sm">
                            Posts
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
    </div>
</template>
