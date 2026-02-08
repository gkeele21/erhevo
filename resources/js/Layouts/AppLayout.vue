<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
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
                                <Link :href="route('home')" class="flex items-center gap-2">
                                    <span class="text-2xl font-bold text-navy">erhevo</span>
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink v-if="user" :href="route('dashboard')" :active="route().current('dashboard')">
                                    Dashboard
                                </NavLink>
                                <NavLink :href="route('posts.index')" :active="route().current('posts.index')">
                                    Posts
                                </NavLink>
                                <NavLink :href="route('categories.index')" :active="route().current('categories.index')">
                                    Categories
                                </NavLink>
                                <NavLink v-if="user" :href="route('friends.index')" :active="route().current('friends.index')">
                                    Friends
                                </NavLink>
                                <NavLink :href="route('about')" :active="route().current('about')">
                                    About
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <!-- Authenticated User Nav -->
                            <template v-if="user">
                                <!-- New Post Button -->
                                <Link
                                    :href="route('posts.create')"
                                    class="px-4 py-2 bg-amber text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors mr-4"
                                >
                                    New Post
                                </Link>

                                <!-- Settings Dropdown -->
                                <div class="ms-3 relative">
                                    <Dropdown align="right" width="48">
                                        <template #trigger>
                                            <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-aqua transition">
                                                <img class="size-8 rounded-full object-cover" :src="user.profile_photo_url" :alt="user.name">
                                            </button>

                                            <span v-else class="inline-flex rounded-md">
                                                <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-teal bg-white hover:text-navy focus:outline-none focus:bg-navy-50 active:bg-navy-50 transition ease-in-out duration-150">
                                                    {{ user.name }}

                                                    <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                    </svg>
                                                </button>
                                            </span>
                                        </template>

                                        <template #content>
                                            <!-- Account Management -->
                                            <div class="block px-4 py-2 text-xs text-teal">
                                                Manage Account
                                            </div>

                                            <DropdownLink :href="route('profile.show')">
                                                Profile
                                            </DropdownLink>

                                            <DropdownLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')">
                                                API Tokens
                                            </DropdownLink>

                                            <div class="border-t border-navy-100" />

                                            <!-- Authentication -->
                                            <form @submit.prevent="logout">
                                                <DropdownLink as="button">
                                                    Log Out
                                                </DropdownLink>
                                            </form>
                                        </template>
                                    </Dropdown>
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

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
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
                <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="sm:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink v-if="user" :href="route('dashboard')" :active="route().current('dashboard')">
                            Dashboard
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('posts.index')" :active="route().current('posts.index')">
                            Posts
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('categories.index')" :active="route().current('categories.index')">
                            Categories
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="user" :href="route('friends.index')" :active="route().current('friends.index')">
                            Friends
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('about')" :active="route().current('about')">
                            About
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
                            <ResponsiveNavLink :href="route('profile.show')" :active="route().current('profile.show')">
                                Profile
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
                <slot />
            </main>
        </div>
    </div>
</template>
