<script setup>
// Favorite marker used on temple cards, the list heading, and the detail
// page. Size/color come from the parent via class.
defineProps({
    filled: { type: Boolean, default: false },
})
</script>

<template>
    <svg
        :fill="filled ? 'currentColor' : 'none'"
        stroke="currentColor"
        stroke-width="1.5"
        viewBox="0 0 24 24"
        aria-hidden="true"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.12 5.1 5.5.44c.5.04.7.66.32 1l-4.19 3.59 1.28 5.37c.12.49-.41.88-.83.62L12 16.72l-4.72 2.9c-.42.26-.95-.13-.83-.62l1.28-5.37-4.19-3.6c-.38-.33-.18-.95.32-.99l5.5-.44 2.12-5.1Z"
        />
    </svg>
</template>
