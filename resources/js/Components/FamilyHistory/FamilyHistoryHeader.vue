<script setup>
import { Link } from '@inertiajs/vue3'

// Page header for the Family History sub-pages: a breadcrumb back to the
// section's main page, then the title. Extra title text (e.g. lifespan) goes
// in the default slot, a line under the title in #subtitle.
defineProps({
    title: String,
    // Crumbs between "Family History" and this page: [{ label, href }].
    crumbs: { type: Array, default: () => [] },
})
</script>

<template>
    <div class="flex flex-col gap-1">
        <nav aria-label="Breadcrumb" class="text-sm">
            <ol class="flex flex-wrap items-center gap-1.5 text-stone-500">
                <li>
                    <Link :href="route('family-history.index')" class="text-teal hover:text-navy">Family History</Link>
                </li>
                <template v-for="crumb in crumbs" :key="crumb.label">
                    <li aria-hidden="true" class="text-stone-300">›</li>
                    <li><Link :href="crumb.href" class="text-teal hover:text-navy">{{ crumb.label }}</Link></li>
                </template>
                <li aria-hidden="true" class="text-stone-300">›</li>
                <li aria-current="page" class="truncate">{{ title }}</li>
            </ol>
        </nav>
        <h2 class="font-semibold text-xl text-stone-800 leading-tight">
            {{ title }} <slot />
        </h2>
        <slot name="subtitle" />
    </div>
</template>
