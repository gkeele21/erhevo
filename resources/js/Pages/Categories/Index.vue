<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
    categories: Array
})
</script>

<template>
    <AppLayout title="Categories">
        <template #header>
            <h2 class="font-semibold text-xl text-stone-800 leading-tight">
                Categories
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="categories?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link
                        v-for="category in categories"
                        :key="category.id"
                        :href="route('categories.show', category.slug)"
                        class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow border border-stone-100"
                    >
                        <h3 class="text-lg font-semibold text-stone-800 mb-2">
                            {{ category.name }}
                        </h3>
                        <p v-if="category.description" class="text-stone-600 text-sm mb-4">
                            {{ category.description }}
                        </p>
                        <p class="text-amber-600 font-medium">
                            {{ category.stories_count || 0 }} stories
                        </p>
                    </Link>
                </div>

                <div v-else class="bg-white rounded-lg shadow p-12 text-center border border-stone-100">
                    <svg class="w-16 h-16 mx-auto text-stone-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-stone-800 mb-2">
                        No categories yet
                    </h3>
                    <p class="text-stone-500">
                        Categories will appear here once they are created.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
