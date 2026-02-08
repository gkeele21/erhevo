<script setup>
import { Head, useForm, Link, usePage } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    story: Object,
    tokens: Array,
})

const page = usePage()

const form = useForm({
    name: '',
    expires_in_days: 7,
})

const showNewLinkModal = ref(false)
const newShareUrl = ref('')

watch(() => page.props.flash.shareUrl, (url) => {
    if (url) {
        newShareUrl.value = url
        showNewLinkModal.value = true
        form.reset()
    }
})

const submit = () => {
    form.post(route('posts.share.store', props.story.slug), {
        preserveScroll: true,
    })
}

const revokeToken = (tokenId) => {
    if (confirm('Are you sure you want to revoke this link? Anyone with this link will no longer be able to edit.')) {
        useForm({}).delete(route('posts.share.destroy', [props.story.slug, tokenId]), {
            preserveScroll: true,
        })
    }
}

const copyToClipboard = (url) => {
    navigator.clipboard.writeText(url)
    alert('Link copied to clipboard!')
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    })
}

const activeTokens = computed(() => props.tokens.filter(t => t.is_valid))
const expiredTokens = computed(() => props.tokens.filter(t => !t.is_valid))
</script>

<template>
    <AppLayout title="Share Post">
        <template #header>
            <div class="flex items-center gap-4">
                <Link
                    :href="route('posts.edit', story.slug)"
                    class="text-stone-400 hover:text-stone-600"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-stone-800 leading-tight">
                    Share "{{ story.title }}"
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- Create New Link -->
                <div class="bg-white rounded-lg shadow p-6 border border-stone-100">
                    <h3 class="text-lg font-semibold text-stone-800 mb-4">Create Share Link</h3>
                    <p class="text-stone-600 mb-6">
                        Create a link that allows someone to edit this post without logging in.
                        They will only be able to edit the content, not change visibility or author settings.
                    </p>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">
                                Link Name (optional)
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="e.g., For Editor John"
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">
                                Expires In
                            </label>
                            <select
                                v-model="form.expires_in_days"
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                            >
                                <option :value="7">7 days</option>
                                <option :value="14">14 days</option>
                                <option :value="30">30 days</option>
                            </select>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-3 bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50"
                        >
                            Create Link
                        </button>
                    </form>
                </div>

                <!-- Active Links -->
                <div v-if="activeTokens.length > 0" class="bg-white rounded-lg shadow p-6 border border-stone-100">
                    <h3 class="text-lg font-semibold text-stone-800 mb-4">Active Links</h3>

                    <div class="space-y-4">
                        <div
                            v-for="token in activeTokens"
                            :key="token.id"
                            class="flex items-center justify-between p-4 bg-stone-50 rounded-lg"
                        >
                            <div>
                                <div class="font-medium text-stone-800">
                                    {{ token.name || 'Unnamed link' }}
                                </div>
                                <div class="text-sm text-stone-500">
                                    Expires {{ formatDate(token.expires_at) }}
                                    <span v-if="token.last_used_at" class="ml-2">
                                        &middot; Last used {{ formatDate(token.last_used_at) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    @click="copyToClipboard(token.url)"
                                    class="px-3 py-1.5 text-sm border border-stone-300 text-stone-700 rounded hover:bg-stone-100"
                                >
                                    Copy Link
                                </button>
                                <button
                                    @click="revokeToken(token.id)"
                                    class="px-3 py-1.5 text-sm text-red-600 hover:text-red-800"
                                >
                                    Revoke
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expired/Revoked Links -->
                <div v-if="expiredTokens.length > 0" class="bg-white rounded-lg shadow p-6 border border-stone-100">
                    <h3 class="text-lg font-semibold text-stone-800 mb-4">Expired & Revoked Links</h3>

                    <div class="space-y-4">
                        <div
                            v-for="token in expiredTokens"
                            :key="token.id"
                            class="flex items-center justify-between p-4 bg-stone-50 rounded-lg opacity-60"
                        >
                            <div>
                                <div class="font-medium text-stone-800">
                                    {{ token.name || 'Unnamed link' }}
                                </div>
                                <div class="text-sm text-stone-500">
                                    <span v-if="!token.is_active" class="text-red-600">Revoked</span>
                                    <span v-else-if="token.is_expired" class="text-amber-600">Expired {{ formatDate(token.expires_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- New Link Created Modal -->
        <div v-if="showNewLinkModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black opacity-50" @click="showNewLinkModal = false"></div>

                <div class="relative bg-white rounded-lg max-w-lg w-full p-6">
                    <h3 class="text-lg font-semibold text-stone-800 mb-2">
                        Share Link Created
                    </h3>
                    <p class="text-stone-600 mb-4">
                        Copy this link and send it to your collaborator. They can use it to edit your story.
                    </p>

                    <div class="flex items-center gap-2 p-3 bg-stone-100 rounded-lg mb-6">
                        <input
                            type="text"
                            :value="newShareUrl"
                            readonly
                            class="flex-1 bg-transparent border-none focus:ring-0 text-sm text-stone-700"
                        >
                        <button
                            @click="copyToClipboard(newShareUrl)"
                            class="px-3 py-1.5 text-sm bg-amber-600 text-white rounded hover:bg-amber-700"
                        >
                            Copy
                        </button>
                    </div>

                    <div class="flex justify-end">
                        <button
                            @click="showNewLinkModal = false"
                            class="px-4 py-2 text-stone-600 hover:text-stone-800"
                        >
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
