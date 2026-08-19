<script setup>
// Feature training content. Conventions, the section template, and the
// "definition of done" for documenting new features live in
// docs/HELP_CONTENT.md. Section ids are deep-link anchors used by
// HelpTip components — don't rename them without grepping for usages.
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const page = usePage()
const user = page.props.auth?.user

const topics = [
    { id: 'posts', label: 'Posts' },
    { id: 'visibility', label: 'Who sees what' },
    { id: 'lessons-talks', label: 'Building lessons & talks' },
    { id: 'teach-mode', label: 'Teach mode & presenting' },
    { id: 'study-plans', label: 'Study plans' },
    { id: 'library', label: 'The Library' },
    { id: 'friends', label: 'Friends & invitations' },
    { id: 'settings', label: 'Settings & AI' },
]
</script>

<template>
    <AppLayout title="Help">
        <template #header>
            <h2 class="font-semibold text-xl text-navy leading-tight">
                Help &amp; Training
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="lg:grid lg:grid-cols-4 lg:gap-8">
                    <!-- Topic nav -->
                    <nav class="mb-8 lg:mb-0">
                        <div class="lg:sticky lg:top-6 bg-white rounded-lg shadow border border-navy-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-teal-300 mb-2">Topics</p>
                            <ul class="space-y-1">
                                <li v-for="topic in topics" :key="topic.id">
                                    <a :href="`#${topic.id}`" class="block rounded px-2 py-1 text-sm text-teal hover:bg-navy-50 hover:text-navy">
                                        {{ topic.label }}
                                    </a>
                                </li>
                            </ul>
                            <p class="mt-4 border-t border-navy-50 pt-3 text-xs text-teal-300">
                                New here? Start with the
                                <Link :href="route('guide')" class="text-amber hover:text-amber-600">quick tour</Link>.
                            </p>
                        </div>
                    </nav>

                    <!-- Content -->
                    <div class="lg:col-span-3 space-y-8">
                        <!-- Posts -->
                        <section id="posts" class="scroll-mt-6 bg-white rounded-lg shadow border border-navy-50 p-6">
                            <h3 class="text-xl font-semibold text-navy mb-3">📝 Posts</h3>
                            <p class="text-teal mb-3">
                                Posts are the things you capture: <strong class="text-navy">Stories</strong> (longer writing),
                                <strong class="text-navy">Thoughts</strong>, <strong class="text-navy">Notes</strong>,
                                <strong class="text-navy">Quotes</strong> (someone else's words, with attribution),
                                <strong class="text-navy">Videos/Links</strong>, <strong class="text-navy">Images</strong>,
                                <strong class="text-navy">Scripture Helps</strong> (insight or context for a passage), and
                                <strong class="text-navy">Meeting Notes</strong>.
                            </p>
                            <ul class="list-disc list-inside text-teal space-y-1 mb-4">
                                <li>Save without publishing to keep a <strong class="text-navy">draft</strong> — drafts show only to you, marked "Draft" on the Posts page.</li>
                                <li>Organize with tags and categories; link posts to Come Follow Me weeks and scripture passages.</li>
                                <li>Pasting a source link (YouTube, church site, etc.) can pre-fill the post for you.</li>
                                <li>Paste an Instagram, TikTok, YouTube, or Facebook video link and hit <strong class="text-navy">Transcribe video</strong> — the spoken words become your post text, the account that posted it is attributed as the <strong class="text-navy">author</strong>, and the link is kept as the source. Takes a minute for longer videos; review and trim the text before saving.</li>
                            </ul>
                            <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                                💡 <strong>Did you know?</strong> A post linked to scripture chapters resurfaces automatically
                                when those chapters come around again in Come Follow Me — write it once in 2024, meet it
                                again in 2028. And every post shows which lessons and talks use it.
                            </div>
                        </section>

                        <!-- Visibility -->
                        <section id="visibility" class="scroll-mt-6 bg-white rounded-lg shadow border border-navy-50 p-6">
                            <h3 class="text-xl font-semibold text-navy mb-3">🔒 Who sees what</h3>
                            <p class="text-teal mb-3">
                                Posts, lessons, and talks all use the same four visibility levels:
                            </p>
                            <ul class="list-disc list-inside text-teal space-y-1 mb-4">
                                <li><strong class="text-navy">Private</strong> — only you.</li>
                                <li><strong class="text-navy">All Friends</strong> — everyone on your friends list.</li>
                                <li><strong class="text-navy">Specific Friends</strong> — a checklist appears and only the friends you tick can see it.</li>
                                <li><strong class="text-navy">Public</strong> — everyone, including visitors.</li>
                            </ul>
                            <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                                💡 <strong>Did you know?</strong> You can also share a single post by private link —
                                even with someone who has no account — from the post's Share option.
                            </div>
                        </section>

                        <!-- Lessons & Talks -->
                        <section id="lessons-talks" class="scroll-mt-6 bg-white rounded-lg shadow border border-navy-50 p-6">
                            <h3 class="text-xl font-semibold text-navy mb-3">🧑‍🏫 Building lessons &amp; talks</h3>
                            <p class="text-teal mb-3">
                                Lessons and talks are built from <strong class="text-navy">blocks</strong>: scriptures,
                                Library talks, quotes, your own posts, scripture helps, videos, images, your own words,
                                and questions. Drag to arrange; use <strong class="text-navy">Groups</strong> to make
                                named sections.
                            </p>
                            <ul class="list-disc list-inside text-teal space-y-1 mb-4">
                                <li><strong class="text-navy">Auto-save</strong> runs every 30 seconds while you edit — you can't lose work.</li>
                                <li>Editing a <strong class="text-navy">published</strong> lesson creates a pending draft: readers keep seeing the live version until you hit Publish Changes.</li>
                                <li><strong class="text-navy">Emphasis flags</strong>: the star marks a block as key ("make sure to cover"), the clock marks it optional ("if time allows"). Teach mode uses these — see below.</li>
                                <li>Blocks flow both ways with your posts: <strong class="text-navy">Save as a Post</strong> keeps writing, quotes, videos, and images for reuse, and every block type can pull in a matching saved post — the <strong class="text-navy">My Writing</strong> block's "From my posts" mode brings in any story, thought, or note as an editable excerpt.</li>
                                <li>New quotes created in the builder are automatically saved to your Posts (private), so they're searchable in every future lesson.</li>
                                <li>The quote form takes a source link too — paste a video post (an Instagram reel, say) and <strong class="text-navy">Transcribe video</strong> turns the spoken words into the quote, author included.</li>
                            </ul>
                            <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                                💡 <strong>Did you know?</strong> The excerpt in a block is <em>your copy</em> — trim a long
                                story to the paragraph you'll actually share, and the original post stays untouched.
                            </div>
                        </section>

                        <!-- Teach mode -->
                        <section id="teach-mode" class="scroll-mt-6 bg-white rounded-lg shadow border border-navy-50 p-6">
                            <h3 class="text-xl font-semibold text-navy mb-3">📺 Teach mode &amp; presenting</h3>
                            <p class="text-teal mb-3">
                                Open any lesson or talk and hit <strong class="text-navy">Teach</strong> for a clean,
                                full-width view built for the moment you're up front.
                            </p>
                            <ul class="list-disc list-inside text-teal space-y-1 mb-4">
                                <li>Collapse blocks you've covered; <strong class="text-navy">Expand all / Collapse all</strong> in the toolbar.</li>
                                <li>The header tallies your blocks — <em>"2 key · 4 normal · 3 optional"</em> — so you can see the lesson's shape at a glance.</li>
                                <li>Running long? <strong class="text-navy">Skip optionals</strong> hides every "if time" block in one click; nothing is deleted, and "Show optionals" brings them back.</li>
                            </ul>
                            <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                                💡 <strong>Did you know?</strong> The little <strong>TV icon</strong> next to each block opens
                                just that block in a new tab — huge, clean type with a full-screen button. Drag that tab to a
                                connected TV or projector to show the class your timeline, image, or scripture while your own
                                screen keeps the full teaching view. Group sections present as one screen, and your private
                                "key / if time" markings are never shown to the class.
                            </div>
                        </section>

                        <!-- Study plans -->
                        <section id="study-plans" class="scroll-mt-6 bg-white rounded-lg shadow border border-navy-50 p-6">
                            <h3 class="text-xl font-semibold text-navy mb-3">🗓️ Study plans</h3>
                            <p class="text-teal mb-3">
                                Pick what to study — a volume of scripture, everything by one speaker (even limited to one
                                calling, like "talks given as Prophet"), all talks from a calling, or a whole General
                                Conference — set your dates and pace, and get a schedule with check-off tracking.
                            </p>
                            <ul class="list-disc list-inside text-teal space-y-1 mb-4">
                                <li>Scripture plans balance by <strong class="text-navy">verse count</strong>: a long chapter gets its own day; short ones share.</li>
                                <li>No end date? You get a simple ordered checklist instead of dated sessions.</li>
                                <li>Editing a plan reflows the schedule but <strong class="text-navy">keeps everything you've already checked off</strong>.</li>
                                <li><strong class="text-navy">Share a plan</strong> with friends to study together: one shared checklist, anyone can check a reading off, and each reading shows who read it. New shared plans light up the bell in the menu.</li>
                            </ul>
                            <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                                💡 <strong>Did you know?</strong> "The Book of Mormon by December 31, daily" schedules all 239
                                chapters so you land on Moroni 10 exactly on the 31st.
                            </div>
                        </section>

                        <!-- Library -->
                        <section id="library" class="scroll-mt-6 bg-white rounded-lg shadow border border-navy-50 p-6">
                            <h3 class="text-xl font-semibold text-navy mb-3">📚 The Library</h3>
                            <p class="text-teal mb-3">
                                Thousands of General Conference talks (back to 1971) and BYU Speeches (back to 1891), each
                                with the speaker's calling at the time, a one-line excerpt, topic tags, and a link to the
                                full talk.
                            </p>
                            <ul class="list-disc list-inside text-teal space-y-1 mb-4">
                                <li>Filter by source, conference, session, or session type — or search titles, speakers, and excerpts.</li>
                                <li>Click any <strong class="text-navy">#topic tag</strong> to see every talk on that theme, across both sources.</li>
                                <li>Sort oldest-first, newest-first, or alphabetically by title or speaker.</li>
                            </ul>
                            <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                                💡 <strong>Did you know?</strong> Library talks are searchable right inside the lesson builder —
                                add one to a lesson without ever leaving the editor.
                            </div>
                        </section>

                        <!-- Temple Tracker -->
                        <section id="temple-tracker" class="scroll-mt-6 bg-white rounded-lg shadow border border-navy-50 p-6">
                            <h3 class="text-xl font-semibold text-navy mb-3">🕍 Temple Tracker</h3>
                            <p class="text-teal mb-3">
                                Every dedicated temple — with its address, photo, and dedication date — in a filterable
                                list and on a world map. Log your visits (with the ordinances you performed), and plan
                                trips to temples you haven't been to yet.
                            </p>
                            <ul class="list-disc list-inside text-teal space-y-1 mb-4">
                                <li>Filter by <strong class="text-navy">country and state</strong>, or flip to the map — visited temples show as filled pins.</li>
                                <li>Log a visit from any temple's page: date, ordinances (Baptism &amp; Confirmation, Initiatory, Endowment, Sealing) — or none for "just a visit" — and a note.</li>
                                <li><strong class="text-navy">Explore</strong> shows every temple within a chosen radius of you (or any spot you click), nearest first.</li>
                                <li><strong class="text-navy">Trips</strong> are named checklists ("Utah trip") you mark off as you go — checking one off offers to log the visit too.</li>
                            </ul>
                            <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                                💡 <strong>Did you know?</strong> Temple Tracker only appears when "Show LDS content" is
                                enabled in your profile settings.
                            </div>
                        </section>

                        <!-- Friends -->
                        <section id="friends" class="scroll-mt-6 bg-white rounded-lg shadow border border-navy-50 p-6">
                            <h3 class="text-xl font-semibold text-navy mb-3">👥 Friends &amp; invitations</h3>
                            <ul class="list-disc list-inside text-teal space-y-1 mb-4">
                                <li>Invite anyone by email from the Friends page — when they join through the link, you're connected automatically (already a member? The link connects you instantly).</li>
                                <li>Friend requests and newly shared study plans appear under the <strong class="text-navy">bell</strong> in the menu.</li>
                                <li>Friends' shared posts show on your dashboard under "From Friends".</li>
                            </ul>
                        </section>

                        <!-- Settings -->
                        <section id="settings" class="scroll-mt-6 bg-white rounded-lg shadow border border-navy-50 p-6">
                            <h3 class="text-xl font-semibold text-navy mb-3">⚙️ Settings &amp; AI</h3>
                            <ul class="list-disc list-inside text-teal space-y-1 mb-4">
                                <li><strong class="text-navy">Bring your own AI</strong>: connect Anthropic, OpenAI, or Gemini keys in your profile (one or several) and get suggested titles, tags, scriptures, excerpts, and journal insights — billed to your own account, never shared. Your <strong class="text-navy">default</strong> provider handles general features; anything it can't do (like transcribing video) automatically uses another of your connections.</li>
                                <li><strong class="text-navy">LDS content toggle</strong>: the Library, Come Follow Me, and scripture features can be switched off in your profile.</li>
                                <li><strong class="text-navy">My Categories</strong>: build your own category tree for organizing private writing, separate from the community categories.</li>
                            </ul>
                            <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                                💡 <strong>Did you know?</strong> The AI tag suggester is available anywhere you tag — including
                                the "Save as a Post" panel inside the lesson builder.
                            </div>
                        </section>

                        <div v-if="!user" class="text-center py-2">
                            <Link :href="route('register')" class="inline-block px-6 py-3 bg-amber text-white rounded-lg font-semibold hover:bg-amber-600 transition-colors">
                                Create your account
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
