<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import NavLink from '@/Components/NavLink.vue';

defineProps({
    title: String,
});

const page = usePage();
</script>

<template>
    <AppLayout :title="title">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-navy leading-tight">
                    Admin: {{ title }}
                </h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Admin Sub-navigation -->
                <div class="mb-6 bg-white rounded-lg shadow border border-navy-50 p-4">
                    <nav class="flex flex-wrap gap-4">
                        <NavLink :href="route('admin.dashboard')" :active="route().current('admin.dashboard')">
                            Dashboard
                        </NavLink>
                        <NavLink :href="route('admin.users.index')" :active="route().current('admin.users.*')">
                            Users
                        </NavLink>
                        <NavLink :href="route('admin.categories.index')" :active="route().current('admin.categories.*')">
                            Categories
                        </NavLink>
                        <NavLink :href="route('admin.cfm.study-years.index')" :active="route().current('admin.cfm.study-years.*')">
                            CFM Study Years
                        </NavLink>
                        <NavLink :href="route('admin.cfm.weeks.index')" :active="route().current('admin.cfm.weeks.*')">
                            CFM Weeks
                        </NavLink>
                        <NavLink :href="route('admin.cfm.special-topics.index')" :active="route().current('admin.cfm.special-topics.*')">
                            CFM Topics
                        </NavLink>
                        <NavLink :href="route('admin.cfm.publishers.index')" :active="route().current('admin.cfm.publishers.*') || route().current('admin.cfm.publisher-content.*')">
                            CFM Publishers
                        </NavLink>
                    </nav>
                </div>

                <!-- Flash Messages -->
                <div v-if="$page.props.flash.success" class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                    {{ $page.props.flash.success }}
                </div>

                <div v-if="$page.props.flash.error" class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    {{ $page.props.flash.error }}
                </div>

                <!-- Page Content -->
                <slot />
            </div>
        </div>
    </AppLayout>
</template>
