<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import Dropdown from '@/Components/Dropdown.vue'

const page = usePage()
const notifications = computed(() => page.props.notifications ?? { count: 0, items: [] })
</script>

<template>
    <Dropdown align="right" width="60">
        <template #trigger>
            <button
                type="button"
                class="relative p-2 rounded-full text-teal hover:text-navy hover:bg-navy-50 focus:outline-none focus:bg-navy-50 transition"
                aria-label="Notifications"
            >
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <span
                    v-if="notifications.count > 0"
                    class="absolute -top-0.5 -end-0.5 flex items-center justify-center rounded-full bg-amber text-white text-[10px] font-bold h-4 min-w-4 px-1"
                >
                    {{ notifications.count }}
                </span>
            </button>
        </template>

        <template #content>
            <div class="block px-4 py-2 text-xs text-teal border-b border-navy-50">
                Notifications
            </div>

            <template v-if="notifications.items.length">
                <Link
                    v-for="(item, index) in notifications.items"
                    :key="index"
                    :href="item.href"
                    class="block px-4 py-2.5 text-sm text-navy hover:bg-navy-50 border-b border-navy-50 last:border-0"
                >
                    {{ item.label }}
                </Link>
            </template>

            <div v-else class="px-4 py-4 text-sm text-teal text-center">
                You're all caught up!
            </div>
        </template>
    </Dropdown>
</template>
