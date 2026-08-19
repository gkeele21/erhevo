<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import NavLink from '@/Components/NavLink.vue';
import NotificationsBell from '@/Components/NotificationsBell.vue';
import UserMenu from '@/Components/UserMenu.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

defineProps({
    title: String,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

const showingNavigationDropdown = ref(false);

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div>
        <Head :title="title" />

        <Banner />

        <div class="min-h-screen bg-[#FAFAFA]">
            <nav class="bg-white border-b border-navy-50 shadow-sm">
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('home')">
                                    <img src="/images/erhevo-logo.png" alt="Erhevo" class="h-10" />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 lg:-my-px lg:ms-10 lg:flex">
                                <NavLink v-if="user" :href="route('dashboard')" :active="route().current('dashboard')">
                                    Dashboard
                                </NavLink>
                                <NavLink :href="route('posts.index')" :active="route().current('posts.index')">
                                    Posts
                                </NavLink>
                                <NavLink :href="route('lessons.index')" :active="route().current('lessons.index')">
                                    Lessons/Talks
                                </NavLink>
                                <NavLink v-if="user" :href="route('study-plans.index')" :active="route().current('study-plans.*')">
                                    Study Plans
                                </NavLink>
                                <NavLink v-if="!user || page.props.userSettings?.show_lds_content" :href="route('talks.index')" :active="route().current('talks.index')">
                                    Library
                                </NavLink>
                                <!-- Auth-only (visits/trips are personal), unlike the guest-browsable Library. -->
                                <NavLink v-if="user && page.props.userSettings?.show_lds_content" :href="route('temples.index')" :active="route().current('temples.*') || route().current('temple-visits.*') || route().current('temple-trips.*')">
                                    Temples
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden lg:flex lg:items-center lg:ms-6">
                            <!-- Authenticated User Nav -->
                            <template v-if="user">
                                <!-- New Post Button -->
                                <Link
                                    :href="route('posts.create')"
                                    class="px-4 py-2 bg-amber text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors mr-4"
                                >
                                    New Post
                                </Link>

                                <!-- Notifications -->
                                <NotificationsBell />

                                <!-- Settings Dropdown -->
                                <div class="ms-3">
                                    <UserMenu />
                                </div>
                            </template>

                            <!-- Guest Nav -->
                            <template v-else>
                                <Link
                                    :href="route('login')"
                                    class="text-sm text-teal hover:text-navy transition-colors"
                                >
                                    Log in
                                </Link>
                                <Link
                                    :href="route('register')"
                                    class="ms-4 px-4 py-2 bg-amber text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors"
                                >
                                    Register
                                </Link>
                            </template>
                        </div>

                        <!-- Hamburger (with notifications alongside on mobile) -->
                        <div class="-me-2 flex items-center gap-1 lg:hidden">
                            <NotificationsBell v-if="user" />
                            <button class="inline-flex items-center justify-center p-2 rounded-md text-teal hover:text-navy hover:bg-navy-50 focus:outline-none focus:bg-navy-50 focus:text-navy transition duration-150 ease-in-out" @click="showingNavigationDropdown = ! showingNavigationDropdown">
                                <svg
                                    class="size-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="lg:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink v-if="user" :href="route('dashboard')" :active="route().current('dashboard')">
                            Dashboard
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('posts.index')" :active="route().current('posts.index')">
                            Posts
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('lessons.index')" :active="route().current('lessons.index')">
                            Lessons/Talks
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="user" :href="route('study-plans.index')" :active="route().current('study-plans.*')">
                            Study Plans
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="!user || page.props.userSettings?.show_lds_content" :href="route('talks.index')" :active="route().current('talks.index')">
                            Library
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="user && page.props.userSettings?.show_lds_content" :href="route('temples.index')" :active="route().current('temples.*') || route().current('temple-visits.*') || route().current('temple-trips.*')">
                            Temples
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="user" :href="route('posts.create')" :active="route().current('posts.create')">
                            New Post
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options (Authenticated) -->
                    <div v-if="user" class="pt-4 pb-1 border-t border-navy-100">
                        <div class="flex items-center px-4">
                            <div v-if="$page.props.jetstream.managesProfilePhotos" class="shrink-0 me-3">
                                <img class="size-10 rounded-full object-cover" :src="user.profile_photo_url" :alt="user.name">
                            </div>

                            <div>
                                <div class="font-medium text-base text-navy">
                                    {{ user.name }}
                                </div>
                                <div class="font-medium text-sm text-teal">
                                    {{ user.email }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('friends.index')" :active="route().current('friends.index')">
                                Friends
                            </ResponsiveNavLink>

                            <ResponsiveNavLink v-if="$page.props.isAdmin" :href="route('admin.dashboard')" :active="route().current('admin.*')">
                                Admin
                            </ResponsiveNavLink>

                            <ResponsiveNavLink :href="route('profile.show')" :active="route().current('profile.show')">
                                Profile
                            </ResponsiveNavLink>

                            <ResponsiveNavLink :href="route('user-categories.index')" :active="route().current('user-categories.index')">
                                My Categories
                            </ResponsiveNavLink>

                            <ResponsiveNavLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')" :active="route().current('api-tokens.index')">
                                API Tokens
                            </ResponsiveNavLink>

                            <!-- Authentication -->
                            <form method="POST" @submit.prevent="logout">
                                <ResponsiveNavLink as="button">
                                    Log Out
                                </ResponsiveNavLink>
                            </form>
                        </div>
                    </div>

                    <!-- Responsive Guest Options -->
                    <div v-else class="pt-4 pb-1 border-t border-navy-100">
                        <div class="space-y-1">
                            <ResponsiveNavLink :href="route('login')">
                                Log in
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('register')">
                                Register
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header v-if="$slots.header" class="bg-white shadow-sm">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <!-- Flash messages -->
                <div
                    v-if="$page.props.flash?.success || $page.props.flash?.error"
                    class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6"
                >
                    <div v-if="$page.props.flash.success" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                        {{ $page.props.flash.success }}
                    </div>
                    <div v-if="$page.props.flash.error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                        {{ $page.props.flash.error }}
                    </div>
                </div>

                <slot />
            </main>

            <!-- Footer (pages may supply their own via the footer slot) -->
            <slot name="footer">
                <footer class="border-t border-navy-50 bg-white">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-6">
                        <Link :href="route('about')" class="text-sm text-teal hover:text-navy transition-colors">
                            About
                        </Link>
                        <Link :href="route('guide')" class="text-sm text-teal hover:text-navy transition-colors">
                            Guide
                        </Link>
                        <Link :href="route('help')" class="text-sm text-teal hover:text-navy transition-colors">
                            Help
                        </Link>
                        <span class="text-sm text-teal-300">
                            &copy; {{ new Date().getFullYear() }} Erhevo
                        </span>
                    </div>
                </footer>
            </slot>
        </div>
    </div>
</template>
