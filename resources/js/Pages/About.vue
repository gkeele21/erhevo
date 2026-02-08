<script setup>
import { Head, Link } from '@inertiajs/vue3'
</script>

<template>
    <Head title="About Erhevo" />

    <div class="min-h-screen bg-[#FAFAFA]">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex justify-between items-center">
                    <Link href="/" class="text-2xl font-bold text-navy">
                        erhevo
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
                            class="text-navy font-medium"
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

        <!-- Hero Section -->
        <section class="bg-gradient-to-br from-aqua-50 via-teal-50 to-ivory py-20">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-navy mb-6">
                    About Erhevo
                </h1>
                <p class="text-xl text-teal italic">
                    A place where words lift you.
                </p>
            </div>
        </section>

        <!-- Main Content -->
        <main class="py-16">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- The Name Section -->
                <section class="mb-16">
                    <h2 class="text-2xl font-bold text-navy mb-6">
                        The Meaning Behind the Name
                    </h2>
                    <div class="prose prose-lg">
                        <p class="text-teal leading-relaxed mb-4">
                            The name <strong class="text-navy">Erhevo</strong> is inspired by the German verb <em>"erheben"</em>,
                            which means <strong>to lift up, elevate, or raise</strong>.
                        </p>
                        <p class="text-teal leading-relaxed mb-4">
                            It's a word that carries a sense of quiet elevation&mdash;not forceful or dramatic, but
                            gentle and uplifting. Just as a kind word can lift someone's spirits, or a story can
                            raise our hopes, Erhevo aims to be a space where words have the power to elevate.
                        </p>
                    </div>
                </section>

                <!-- Our Mission Section -->
                <section class="mb-16">
                    <h2 class="text-2xl font-bold text-navy mb-6">
                        Our Mission
                    </h2>
                    <div class="bg-white rounded-xl shadow-sm p-8 border border-navy-50">
                        <p class="text-teal leading-relaxed mb-4">
                            In a world full of noise, negativity, and endless scrolling, Erhevo exists as a
                            sanctuary for uplifting stories and positive thoughts.
                        </p>
                        <p class="text-teal leading-relaxed mb-4">
                            We believe in the power of words to inspire, heal, and connect. Whether it's a
                            story of perseverance, a moment of gratitude, an act of kindness, or simply a
                            thought that made your day brighter&mdash;these are the stories worth sharing.
                        </p>
                        <p class="text-teal leading-relaxed">
                            Erhevo is a community where you can share these moments, discover others' stories,
                            and find that quiet uplift we all need sometimes.
                        </p>
                    </div>
                </section>

                <!-- Values Section -->
                <section class="mb-16">
                    <h2 class="text-2xl font-bold text-navy mb-6">
                        What We Value
                    </h2>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="bg-white rounded-xl shadow-sm p-6 border border-navy-50">
                            <div class="w-12 h-12 bg-aqua-50 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-navy mb-2">Authenticity</h3>
                            <p class="text-teal text-sm">
                                Real stories from real people. No algorithms optimizing for outrage, just genuine moments of uplift.
                            </p>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm p-6 border border-navy-50">
                            <div class="w-12 h-12 bg-aqua-50 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-navy mb-2">Privacy</h3>
                            <p class="text-teal text-sm">
                                Share your story your way. Control who sees it, hide your name if you prefer, or keep it just for friends.
                            </p>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm p-6 border border-navy-50">
                            <div class="w-12 h-12 bg-gold-50 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-navy mb-2">Kindness</h3>
                            <p class="text-teal text-sm">
                                A community built on lifting each other up, celebrating small wins, and spreading positivity.
                            </p>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm p-6 border border-navy-50">
                            <div class="w-12 h-12 bg-gold-50 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-navy mb-2">Connection</h3>
                            <p class="text-teal text-sm">
                                Share with the world or just with friends. Build meaningful connections through shared experiences.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Call to Action -->
                <section class="text-center">
                    <div class="bg-gradient-to-r from-teal to-aqua rounded-xl p-8 text-white">
                        <h2 class="text-2xl font-bold mb-4">
                            Ready to Share Your Story?
                        </h2>
                        <p class="mb-6 opacity-90">
                            Join our community and help spread a little more light in the world.
                        </p>
                        <div class="flex justify-center gap-4">
                            <Link
                                :href="route('posts.index')"
                                class="px-6 py-3 bg-white text-navy rounded-lg font-semibold hover:bg-navy-50 transition-colors"
                            >
                                Explore Posts
                            </Link>
                            <Link
                                v-if="!$page.props.auth.user"
                                :href="route('register')"
                                class="px-6 py-3 bg-amber text-white rounded-lg font-semibold hover:bg-amber-600 transition-colors"
                            >
                                Get Started
                            </Link>
                            <Link
                                v-else
                                :href="route('posts.create')"
                                class="px-6 py-3 bg-amber text-white rounded-lg font-semibold hover:bg-amber-600 transition-colors"
                            >
                                Share a Post
                            </Link>
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-navy py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h3 class="text-xl font-bold text-gold mb-2">erhevo</h3>
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
