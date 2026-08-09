<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import HelpTip from '@/Components/HelpTip.vue'

defineProps({
    friends: Array,
    pendingRequests: Array,
    sentRequests: Array,
    invitations: Array
})

const inviteForm = useForm({
    emails: [],
    message: ''
})

const emailInput = ref('')
const emailInputError = ref('')
const emailInputEl = ref(null)

const isValidEmail = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)

// Turn whatever is typed in the text box into a chip. Returns false if the
// text is there but not a valid email (so submit can block on it).
const commitEmailInput = () => {
    const raw = emailInput.value.trim().replace(/[,;]+$/, '').trim()
    if (!raw) {
        emailInput.value = ''
        return true
    }
    if (!isValidEmail(raw)) {
        emailInputError.value = `"${raw}" doesn't look like a valid email address.`
        return false
    }
    const email = raw.toLowerCase()
    if (!inviteForm.emails.includes(email)) {
        inviteForm.emails.push(email)
    }
    emailInput.value = ''
    emailInputError.value = ''
    return true
}

const handleEmailKeydown = (event) => {
    if (event.key === 'Enter' || event.key === ',' || event.key === ';' || event.key === ' ') {
        event.preventDefault()
        commitEmailInput()
    } else if (event.key === 'Backspace' && !emailInput.value && inviteForm.emails.length) {
        inviteForm.emails.pop()
    }
}

// Pasting "a@x.com, b@y.com; c@z.com" adds each address as its own chip.
const handleEmailPaste = (event) => {
    const text = event.clipboardData?.getData('text') ?? ''
    if (!/[,;\s]/.test(text.trim())) return

    event.preventDefault()
    for (const part of text.split(/[,;\s]+/)) {
        const email = part.trim().toLowerCase()
        if (email && isValidEmail(email) && !inviteForm.emails.includes(email)) {
            inviteForm.emails.push(email)
        }
    }
}

const removeEmail = (index) => {
    inviteForm.emails.splice(index, 1)
}

// Server-side errors for individual emails arrive keyed as "emails.0" etc.
const serverEmailsError = computed(() => {
    if (inviteForm.errors.emails) return inviteForm.errors.emails
    const key = Object.keys(inviteForm.errors).find(k => k.startsWith('emails.'))
    return key ? inviteForm.errors[key] : null
})

const sendInvite = () => {
    if (!commitEmailInput()) return

    if (!inviteForm.emails.length) {
        emailInputError.value = 'Please add at least one email address.'
        return
    }

    inviteForm.post(route('friends.invite'), {
        preserveScroll: true,
        onSuccess: () => {
            inviteForm.reset()
            emailInputError.value = ''
        }
    })
}

const cancelInvitation = (invitationId) => {
    router.delete(route('friends.invitations.cancel', invitationId), {
        preserveScroll: true
    })
}

const searchQuery = ref('')
const searchResults = ref([])
const isSearching = ref(false)
let debounceTimer = null

const searchUsers = async () => {
    if (searchQuery.value.length < 2) {
        searchResults.value = []
        return
    }

    isSearching.value = true
    try {
        const response = await fetch(`/users/search?q=${encodeURIComponent(searchQuery.value)}`)
        searchResults.value = await response.json()
    } catch (error) {
        console.error('Search failed:', error)
    } finally {
        isSearching.value = false
    }
}

const handleSearchInput = () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(searchUsers, 300)
}

const sendRequest = (userId) => {
    router.post(route('friends.request', userId), {}, {
        preserveScroll: true,
        onSuccess: () => {
            searchResults.value = searchResults.value.filter(u => u.id !== userId)
        }
    })
}

const acceptRequest = (friendshipId) => {
    router.post(route('friends.accept', friendshipId), {}, {
        preserveScroll: true
    })
}

const declineRequest = (friendshipId) => {
    router.post(route('friends.decline', friendshipId), {}, {
        preserveScroll: true
    })
}

const removeFriend = (userId) => {
    if (confirm('Are you sure you want to remove this friend?')) {
        router.delete(route('friends.remove', userId), {
            preserveScroll: true
        })
    }
}
</script>

<template>
    <AppLayout title="Friends">
        <template #header>
            <h2 class="flex items-center gap-1.5 font-semibold text-xl text-stone-800 leading-tight">
                Friends
                <HelpTip anchor="friends" tip="Invite anyone by email — joining through your link connects you automatically. Friends' shared posts show on your dashboard. Open Help to learn more." />
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Search Users -->
                <div class="bg-white rounded-lg shadow p-6 mb-8 border border-stone-100">
                    <h3 class="text-lg font-semibold text-stone-800 mb-4">
                        Find Friends
                    </h3>
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            @input="handleSearchInput"
                            type="text"
                            placeholder="Search by name or email..."
                            class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                        >
                        <div v-if="isSearching" class="absolute right-3 top-3">
                            <svg class="animate-spin h-5 w-5 text-stone-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>

                    <div v-if="searchResults.length" class="mt-4 space-y-2">
                        <div
                            v-for="user in searchResults"
                            :key="user.id"
                            class="flex items-center justify-between p-3 bg-stone-50 rounded-lg"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                                    <span class="text-amber-700 font-medium">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium text-stone-800">{{ user.name }}</p>
                                    <p class="text-sm text-stone-500">{{ user.email }}</p>
                                </div>
                            </div>
                            <button
                                @click="sendRequest(user.id)"
                                class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 text-sm"
                            >
                                Add Friend
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Invite by Email -->
                <div class="bg-white rounded-lg shadow p-6 mb-8 border border-stone-100">
                    <h3 class="text-lg font-semibold text-stone-800 mb-1">
                        Invite Someone to Erhevo
                    </h3>
                    <p class="text-sm text-stone-500 mb-4">
                        Not here yet? Send them an email invitation. When they register, you'll automatically become friends.
                        You can invite several people at once — press Enter, space, or comma after each address.
                    </p>
                    <form @submit.prevent="sendInvite" class="space-y-3">
                        <div>
                            <div
                                class="w-full rounded-lg border border-stone-300 bg-white px-2 py-1.5 flex flex-wrap items-center gap-1.5 cursor-text focus-within:border-amber-500 focus-within:ring-1 focus-within:ring-amber-500"
                                @click="emailInputEl?.focus()"
                            >
                                <span
                                    v-for="(email, index) in inviteForm.emails"
                                    :key="email"
                                    class="inline-flex items-center gap-1 rounded-md bg-green-100 text-green-800 px-2 py-1 text-sm"
                                >
                                    {{ email }}
                                    <button
                                        type="button"
                                        class="text-green-600 hover:text-green-900 leading-none"
                                        :aria-label="`Remove ${email}`"
                                        @click.stop="removeEmail(index)"
                                    >
                                        &times;
                                    </button>
                                </span>
                                <input
                                    ref="emailInputEl"
                                    v-model="emailInput"
                                    type="text"
                                    :placeholder="inviteForm.emails.length ? 'Add another...' : 'friend@example.com'"
                                    class="flex-1 min-w-[10rem] border-0 p-1 text-sm focus:ring-0 placeholder-stone-400"
                                    @keydown="handleEmailKeydown"
                                    @paste="handleEmailPaste"
                                    @blur="commitEmailInput"
                                >
                            </div>
                            <p v-if="emailInputError" class="mt-1 text-sm text-red-600">{{ emailInputError }}</p>
                            <p v-else-if="serverEmailsError" class="mt-1 text-sm text-red-600">{{ serverEmailsError }}</p>
                        </div>
                        <div>
                            <textarea
                                v-model="inviteForm.message"
                                rows="3"
                                maxlength="1000"
                                placeholder="Add a personal message (optional)"
                                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
                            ></textarea>
                            <p v-if="inviteForm.errors.message" class="mt-1 text-sm text-red-600">{{ inviteForm.errors.message }}</p>
                        </div>
                        <button
                            type="submit"
                            :disabled="inviteForm.processing"
                            class="px-6 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50 whitespace-nowrap"
                        >
                            {{ inviteForm.processing ? 'Sending...' : 'Send Invitation' }}
                        </button>
                    </form>

                    <div v-if="invitations?.length" class="mt-6">
                        <h4 class="text-sm font-medium text-stone-600 mb-2">
                            Pending Invitations
                        </h4>
                        <div class="space-y-2">
                            <div
                                v-for="invitation in invitations"
                                :key="invitation.id"
                                class="flex items-center justify-between p-3 bg-stone-50 rounded-lg"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-stone-200 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-stone-800">{{ invitation.email }}</p>
                                        <p class="text-sm text-stone-500">Invitation sent</p>
                                    </div>
                                </div>
                                <button
                                    @click="cancelInvitation(invitation.id)"
                                    class="text-red-600 hover:text-red-800 text-sm"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Requests -->
                <div v-if="pendingRequests?.length" class="bg-white rounded-lg shadow p-6 mb-8 border border-stone-100">
                    <h3 class="text-lg font-semibold text-stone-800 mb-4">
                        Friend Requests ({{ pendingRequests.length }})
                    </h3>
                    <div class="space-y-3">
                        <div
                            v-for="request in pendingRequests"
                            :key="request.id"
                            class="flex items-center justify-between p-3 bg-stone-50 rounded-lg"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                                    <span class="text-orange-600 font-medium">
                                        {{ request.requester.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium text-stone-800">{{ request.requester.name }}</p>
                                    <p class="text-sm text-stone-500">{{ request.requester.email }}</p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    @click="acceptRequest(request.id)"
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm"
                                >
                                    Accept
                                </button>
                                <button
                                    @click="declineRequest(request.id)"
                                    class="px-4 py-2 bg-stone-300 text-stone-700 rounded-lg hover:bg-stone-400 text-sm"
                                >
                                    Decline
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sent Requests -->
                <div v-if="sentRequests?.length" class="bg-white rounded-lg shadow p-6 mb-8 border border-stone-100">
                    <h3 class="text-lg font-semibold text-stone-800 mb-4">
                        Sent Requests
                    </h3>
                    <div class="space-y-3">
                        <div
                            v-for="request in sentRequests"
                            :key="request.id"
                            class="flex items-center justify-between p-3 bg-stone-50 rounded-lg"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-stone-200 rounded-full flex items-center justify-center">
                                    <span class="text-stone-600 font-medium">
                                        {{ request.addressee.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium text-stone-800">{{ request.addressee.name }}</p>
                                    <p class="text-sm text-stone-500">Pending...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Friends List -->
                <div class="bg-white rounded-lg shadow p-6 border border-stone-100">
                    <h3 class="text-lg font-semibold text-stone-800 mb-4">
                        My Friends ({{ friends?.length || 0 }})
                    </h3>

                    <div v-if="friends?.length" class="space-y-3">
                        <div
                            v-for="friend in friends"
                            :key="friend.id"
                            class="flex items-center justify-between p-3 bg-stone-50 rounded-lg"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <span class="text-green-600 font-medium">
                                        {{ friend.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium text-stone-800">{{ friend.name }}</p>
                                    <p class="text-sm text-stone-500">{{ friend.email }}</p>
                                </div>
                            </div>
                            <button
                                @click="removeFriend(friend.id)"
                                class="text-red-600 hover:text-red-800 text-sm"
                            >
                                Remove
                            </button>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-stone-500">
                        <p>You don't have any friends yet.</p>
                        <p class="text-sm mt-2">Use the search above to find and add friends.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
