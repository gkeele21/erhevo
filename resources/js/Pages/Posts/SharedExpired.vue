<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({
    message: String,
})
</script>

<template>
    <Head title="Link Expired" />

    <div class="min-h-screen bg-[#FAFAFA] flex items-center justify-center">
        <div class="max-w-md w-full mx-4">
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <div class="mx-auto w-16 h-16 bg-stone-100 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>

                <h1 class="text-xl font-semibold text-stone-800 mb-2">
                    Link No Longer Valid
                </h1>

                <p class="text-stone-600 mb-6">
                    {{ message }}
                </p>

                <p class="text-sm text-stone-500">
                    Please contact the post owner for a new link.
                </p>
            </div>

            <div class="text-center mt-6">
                <span class="text-2xl font-bold text-navy">erhevo</span>
            </div>
        </div>
    </div>
</template>
