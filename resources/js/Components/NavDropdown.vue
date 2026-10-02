<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';

const props = defineProps({
    label: String,
    active: Boolean,
    items: Array,
});

// Matches NavLink so a group sits flush with the plain links beside it.
const triggerClasses = computed(() => {
    return props.active
        ? 'inline-flex h-full items-center px-1 pt-1 border-b-2 border-amber text-sm font-medium leading-5 text-navy focus:outline-none focus:border-amber-600 transition duration-150 ease-in-out'
        : 'inline-flex h-full items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-teal hover:text-navy hover:border-aqua focus:outline-none focus:text-navy focus:border-aqua transition duration-150 ease-in-out';
});
</script>

<template>
    <Dropdown align="left" width="48" class="flex">
        <template #trigger>
            <button type="button" :class="triggerClasses">
                {{ label }}
                <svg class="ms-1 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
        </template>

        <template #content>
            <Link
                v-for="item in items"
                :key="item.label"
                :href="item.href"
                class="block px-4 py-2 text-sm leading-5 hover:bg-navy-50 focus:outline-none focus:bg-navy-50 transition duration-150 ease-in-out"
                :class="item.active ? 'text-navy font-semibold' : 'text-navy'"
            >
                {{ item.label }}
            </Link>
        </template>
    </Dropdown>
</template>
