<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    title: String,
    expiresAt: String,
});
</script>

<template>
    <div>
        <Head :title="title" />

        <div class="min-h-screen bg-[#FAFAFA]">
            <nav class="bg-white border-b border-stone-200 shadow-sm">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <span class="text-2xl font-bold text-navy">erhevo</span>
                        </div>

                        <div class="flex items-center gap-4">
                            <span class="text-sm text-stone-500 bg-amber-100 px-3 py-1 rounded-full">
                                Shared Edit Mode
                            </span>
                            <span v-if="expiresAt" class="text-xs text-stone-400">
                                Expires {{ new Date(expiresAt).toLocaleDateString() }}
                            </span>
                        </div>
                    </div>
                </div>
            </nav>

            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
