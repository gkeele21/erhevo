<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    volumes: Array,
    recent: Array,
})

const search = ref('')
const openVolume = ref(props.volumes?.[0]?.name ?? null)

const matchingVolumes = computed(() => {
    const term = search.value.trim().toLowerCase()
    if (!term) return props.volumes

    return props.volumes
        .map((vol) => ({
            ...vol,
            books: vol.books.filter((b) => b.name.toLowerCase().includes(term)),
        }))
        .filter((vol) => vol.books.length)
})

// While searching, every matching volume stays open.
const isOpen = (name) => Boolean(search.value.trim()) || openVolume.value === name

const toggle = (name) => {
    openVolume.value = openVolume.value === name ? null : name
}
</script>

<template>
    <AppLayout title="Scriptures">
        <Head title="Scriptures" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-stone-800">Scriptures</h2>
        </template>

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <p class="text-stone-600">
                Open a chapter to read it alongside the thoughts, lessons and talks that reference it.
            </p>

            <!-- Recently written about -->
            <div v-if="recent.length" class="mt-6">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-stone-500">Recently written about</h3>
                <div class="mt-2 flex flex-wrap gap-2">
                    <Link
                        v-for="item in recent"
                        :key="item.url"
                        :href="item.url"
                        class="rounded-full border border-amber-300 bg-amber-50 px-3 py-1 text-sm text-amber-900 hover:bg-amber-100"
                    >
                        {{ item.reference }}
                    </Link>
                </div>
            </div>

            <!-- Book finder -->
            <div class="mt-8">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Find a book..."
                    class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500 sm:max-w-sm"
                >
            </div>

            <div class="mt-4 space-y-3">
                <div
                    v-for="volume in matchingVolumes"
                    :key="volume.name"
                    class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm"
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-between px-4 py-3 text-left hover:bg-stone-50"
                        @click="toggle(volume.name)"
                    >
                        <span class="font-semibold text-stone-800">{{ volume.name }}</span>
                        <svg
                            class="h-4 w-4 text-stone-400 transition-transform"
                            :class="isOpen(volume.name) ? 'rotate-180' : ''"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div v-if="isOpen(volume.name)" class="border-t border-stone-100 p-4">
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1 sm:grid-cols-3 lg:grid-cols-4">
                            <Link
                                v-for="book in volume.books"
                                :key="book.slug"
                                :href="route('scriptures.show', { book: book.slug, chapter: 1 })"
                                class="truncate rounded px-2 py-1 text-sm text-stone-700 hover:bg-amber-50 hover:text-amber-900"
                            >
                                {{ book.name }}
                            </Link>
                        </div>
                    </div>
                </div>

                <p v-if="!matchingVolumes.length" class="py-8 text-center text-stone-500">
                    No books match “{{ search }}”.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
