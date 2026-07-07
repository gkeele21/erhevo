<script setup>
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    authors: Object,
    filters: Object,
    canMerge: Boolean,
})

const search = ref(props.filters?.search || '')

const runSearch = () => {
    router.get(route('authors.index'), { search: search.value || undefined }, { preserveState: true, replace: true })
}

// Admin merge tool
const mergeForm = useForm({ from_id: '', into_id: '' })
const submitMerge = () => {
    if (!mergeForm.from_id || !mergeForm.into_id || mergeForm.from_id === mergeForm.into_id) return
    if (!confirm('Merge these authors? All content from the duplicate moves to the surviving author, and the duplicate is deleted.')) return
    mergeForm.post(route('authors.merge'), {
        preserveScroll: true,
        onSuccess: () => mergeForm.reset(),
    })
}
</script>

<template>
    <Head title="Authors" />
    <AppLayout title="Authors">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-stone-800">Authors</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <!-- Search -->
                <form @submit.prevent="runSearch" class="mb-6">
                    <input
                        v-model="search"
                        type="text"
                        @input="runSearch"
                        class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        placeholder="Search authors by name..."
                    >
                </form>

                <!-- Merge tool (admins) -->
                <div v-if="canMerge" class="mb-6 rounded-lg border border-stone-200 bg-stone-50 p-4">
                    <h3 class="mb-2 text-sm font-semibold text-stone-700">Merge duplicates</h3>
                    <div class="flex flex-wrap items-end gap-2">
                        <div class="flex-1 min-w-[150px]">
                            <label class="mb-1 block text-xs text-stone-500">Duplicate (removed)</label>
                            <select v-model="mergeForm.from_id" class="w-full rounded-lg border-stone-300 text-sm">
                                <option value="">Select…</option>
                                <option v-for="a in authors.data" :key="a.id" :value="a.id">{{ a.full_name }}</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-[150px]">
                            <label class="mb-1 block text-xs text-stone-500">Merge into (kept)</label>
                            <select v-model="mergeForm.into_id" class="w-full rounded-lg border-stone-300 text-sm">
                                <option value="">Select…</option>
                                <option v-for="a in authors.data" :key="a.id" :value="a.id">{{ a.full_name }}</option>
                            </select>
                        </div>
                        <button
                            type="button"
                            @click="submitMerge"
                            :disabled="mergeForm.processing"
                            class="rounded-lg bg-stone-700 px-4 py-2 text-sm font-medium text-white hover:bg-stone-800 disabled:opacity-50"
                        >
                            Merge
                        </button>
                    </div>
                </div>

                <!-- List -->
                <div class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
                    <Link
                        v-for="author in authors.data"
                        :key="author.id"
                        :href="route('authors.show', author.slug)"
                        class="flex items-center justify-between px-4 py-3 hover:bg-stone-50"
                    >
                        <span class="font-medium text-stone-800">{{ author.full_name }}</span>
                        <span class="text-sm text-stone-400">
                            {{ author.posts_count }} {{ author.posts_count === 1 ? 'item' : 'items' }}
                        </span>
                    </Link>
                    <p v-if="!authors.data.length" class="px-4 py-8 text-center text-stone-400">No authors found.</p>
                </div>

                <!-- Pagination -->
                <div v-if="authors.links?.length > 3" class="mt-6 flex flex-wrap gap-1">
                    <component
                        :is="link.url ? Link : 'span'"
                        v-for="link in authors.links"
                        :key="link.label"
                        :href="link.url"
                        v-html="link.label"
                        class="rounded px-3 py-1 text-sm"
                        :class="link.active ? 'bg-amber-600 text-white' : (link.url ? 'text-stone-600 hover:bg-stone-100' : 'text-stone-300')"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
