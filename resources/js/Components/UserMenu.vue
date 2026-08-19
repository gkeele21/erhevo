<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div v-if="user" class="relative">
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
                <DropdownLink :href="route('friends.index')">
                    Friends
                </DropdownLink>

                <DropdownLink v-if="$page.props.isAdmin" :href="route('admin.dashboard')">
                    Admin
                </DropdownLink>

                <div class="border-t border-navy-100" />

                <!-- Account Management -->
                <div class="block px-4 py-2 text-xs text-teal">
                    Manage Account
                </div>

                <DropdownLink :href="route('profile.show')">
                    Profile
                </DropdownLink>

                <DropdownLink :href="route('user-categories.index')">
                    My Categories
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
