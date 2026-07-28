<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const page = usePage()
const user = page.props.auth?.user
</script>

<template>
    <AppLayout title="Guide">
        <template #header>
            <h2 class="font-semibold text-xl text-navy leading-tight">
                What can you do on Erhevo?
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <p class="text-lg text-teal px-4 sm:px-0">
                    Erhevo is a place where words lift you — for capturing what inspires you,
                    preparing to teach it, and keeping your own study on track. Here's the tour.
                </p>

                <!-- Posts -->
                <section class="bg-white rounded-lg shadow border border-navy-50 p-6">
                    <h3 class="text-xl font-semibold text-navy mb-3">📝 Posts</h3>
                    <p class="text-teal mb-3">
                        The heart of Erhevo: stories, quotes, and thoughts worth keeping. Every post
                        can be <strong class="text-navy">public</strong>, visible to
                        <strong class="text-navy">friends only</strong>, or completely
                        <strong class="text-navy">private</strong> — so the same app works as a journal
                        and as a way to share.
                    </p>
                    <ul class="list-disc list-inside text-teal space-y-1 mb-4">
                        <li>Organize with categories and tags — yours plus the community's</li>
                        <li>Attach the original author and source, so quotes keep their attribution</li>
                        <li>Link posts to Come Follow Me weeks and scripture passages, and they'll resurface when those chapters come around again</li>
                        <li>Connected an AI account? Get suggested titles, tags, and scripture references while you write</li>
                    </ul>
                    <Link :href="user ? route('posts.create') : route('posts.index')" class="text-amber hover:text-amber-600 font-medium">
                        {{ user ? 'Write a post' : 'Browse posts' }} &rarr;
                    </Link>
                </section>

                <!-- Library -->
                <section class="bg-white rounded-lg shadow border border-navy-50 p-6">
                    <h3 class="text-xl font-semibold text-navy mb-3">📚 The Library</h3>
                    <p class="text-teal mb-3">
                        Every General Conference talk since 1971 — over 4,000 of them — each with the
                        speaker's calling at the time, a one-line excerpt, and a link to the full talk.
                    </p>
                    <ul class="list-disc list-inside text-teal space-y-1 mb-4">
                        <li>Filter by conference, session, or session type (every Sunday Morning session ever, for instance)</li>
                        <li>Search titles, speakers, and excerpts</li>
                        <li>Follow topic tags like <span class="text-navy">#faith</span> or <span class="text-navy">#repentance</span> to find talks on a theme</li>
                    </ul>
                    <Link :href="route('talks.index')" class="text-amber hover:text-amber-600 font-medium">
                        Browse the Library &rarr;
                    </Link>
                </section>

                <!-- Study Plans -->
                <section class="bg-white rounded-lg shadow border border-navy-50 p-6">
                    <h3 class="text-xl font-semibold text-navy mb-3">🗓️ Study Plans</h3>
                    <p class="text-teal mb-3">
                        Set a reading goal and get a schedule that fits. Tell Erhevo
                        <em>what</em> — a volume of scripture, everything by one speaker, all talks
                        given in a calling, or a whole conference — and <em>when</em>, and it builds
                        the plan.
                    </p>
                    <ul class="list-disc list-inside text-teal space-y-1 mb-4">
                        <li>"The Book of Mormon by December 31, daily" — sessions are balanced by verse count, so a long chapter gets its own day while short ones share</li>
                        <li>"All of President Nelson's talks as Prophet" or "every Apostle's talk from the last 2 years"</li>
                        <li>Check off each reading with a green check; a progress bar tracks the whole plan</li>
                        <li>No end date? No problem — you'll get a simple ordered checklist instead</li>
                    </ul>
                    <Link :href="user ? route('study-plans.create') : route('register')" class="text-amber hover:text-amber-600 font-medium">
                        {{ user ? 'Create a study plan' : 'Sign up to create a study plan' }} &rarr;
                    </Link>
                </section>

                <!-- Lessons -->
                <section class="bg-white rounded-lg shadow border border-navy-50 p-6">
                    <h3 class="text-xl font-semibold text-navy mb-3">🧑‍🏫 Lessons</h3>
                    <p class="text-teal mb-3">
                        Build a lesson from blocks — scripture passages, conference talk quotes,
                        questions, images, and videos — arranged and grouped however you teach.
                    </p>
                    <ul class="list-disc list-inside text-teal space-y-1 mb-4">
                        <li>Search scriptures and the talk Library right from the editor</li>
                        <li>Tie a lesson to a Come Follow Me week so others studying it can find yours</li>
                        <li>Teach mode presents your lesson full-screen, block by block</li>
                        <li>Published lessons keep working drafts, so you can revise without breaking the shared version</li>
                    </ul>
                    <Link :href="route('lessons.index')" class="text-amber hover:text-amber-600 font-medium">
                        Explore lessons &rarr;
                    </Link>
                </section>

                <!-- Friends -->
                <section class="bg-white rounded-lg shadow border border-navy-50 p-6">
                    <h3 class="text-xl font-semibold text-navy mb-3">👥 Friends</h3>
                    <p class="text-teal mb-3">
                        Erhevo is quieter than a social network — there's no feed algorithm, just the
                        people you choose.
                    </p>
                    <ul class="list-disc list-inside text-teal space-y-1 mb-4">
                        <li>Friends see your friends-only posts on their dashboard, and you see theirs</li>
                        <li>Invite someone by email — when they sign up, you're automatically connected</li>
                        <li>Share individual posts with a private link, even to people without an account</li>
                    </ul>
                    <Link :href="user ? route('friends.index') : route('register')" class="text-amber hover:text-amber-600 font-medium">
                        {{ user ? 'Find friends' : 'Sign up to connect' }} &rarr;
                    </Link>
                </section>

                <!-- Make it yours -->
                <section class="bg-white rounded-lg shadow border border-navy-50 p-6">
                    <h3 class="text-xl font-semibold text-navy mb-3">⚙️ Make it yours</h3>
                    <ul class="list-disc list-inside text-teal space-y-1 mb-4">
                        <li><strong class="text-navy">Your categories</strong> — build a personal category tree for organizing private writing</li>
                        <li><strong class="text-navy">LDS content toggle</strong> — the Library, Come Follow Me, and scripture features can be switched off in your profile</li>
                        <li><strong class="text-navy">Bring your own AI</strong> — connect your Anthropic, OpenAI, or Gemini key and Erhevo adds writing suggestions, insights, and tagging, billed to your own account and never shared</li>
                    </ul>
                    <Link :href="user ? route('profile.show') : route('register')" class="text-amber hover:text-amber-600 font-medium">
                        {{ user ? 'Open your profile settings' : 'Create your account' }} &rarr;
                    </Link>
                </section>

                <div v-if="!user" class="text-center py-4">
                    <Link
                        :href="route('register')"
                        class="inline-block px-6 py-3 bg-amber text-white rounded-lg font-semibold hover:bg-amber-600 transition-colors"
                    >
                        Get started — it's free
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
