<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    authorType: {
        type: String,
        default: 'self'
    },
    authorText: {
        type: String,
        default: ''
    },
    authorUserId: {
        type: [Number, String, null],
        default: null
    },
    authorTypes: {
        type: Array,
        required: true
    }
})

const emit = defineEmits([
    'update:authorType',
    'update:authorText',
    'update:authorUserId'
])

const userQuery = ref('')
const userSuggestions = ref([])
const selectedUser = ref(null)
const showSuggestions = ref(false)
let debounceTimer = null

const searchUsers = async (query) => {
    if (query.length < 2) {
        userSuggestions.value = []
        return
    }

    try {
        const response = await fetch(`/users/search?q=${encodeURIComponent(query)}`)
        userSuggestions.value = await response.json()
        showSuggestions.value = true
    } catch (error) {
        console.error('Failed to search users:', error)
    }
}

const handleUserInput = (e) => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        searchUsers(e.target.value)
    }, 300)
}

const selectUser = (user) => {
    selectedUser.value = user
    userQuery.value = user.name
    emit('update:authorUserId', user.id)
    userSuggestions.value = []
    showSuggestions.value = false
}

watch(() => props.authorType, (newType) => {
    if (newType !== 'user') {
        selectedUser.value = null
        userQuery.value = ''
        emit('update:authorUserId', null)
    }
    if (newType !== 'text') {
        emit('update:authorText', '')
    }
})
</script>

<template>
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-stone-700 mb-2">
                Author Attribution
            </label>
            <div class="grid grid-cols-3 gap-3">
                <label
                    v-for="type in authorTypes"
                    :key="type.value"
                    class="relative flex cursor-pointer rounded-lg border p-3 focus:outline-none"
                    :class="[
                        authorType === type.value
                            ? 'border-amber-600 ring-2 ring-amber-600'
                            : 'border-stone-300'
                    ]"
                >
                    <input
                        type="radio"
                        :value="type.value"
                        :checked="authorType === type.value"
                        @change="emit('update:authorType', type.value)"
                        class="sr-only"
                    >
                    <span class="text-sm font-medium text-stone-800">
                        {{ type.label }}
                    </span>
                </label>
            </div>
        </div>

        <!-- Custom Author Text -->
        <div v-if="authorType === 'text'">
            <label class="block text-sm font-medium text-stone-700 mb-1">
                Author Name
            </label>
            <input
                type="text"
                :value="authorText"
                @input="emit('update:authorText', $event.target.value)"
                placeholder="e.g., Maya Angelou, Unknown, Traditional"
                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
            >
        </div>

        <!-- User Selection -->
        <div v-if="authorType === 'user'" class="relative">
            <label class="block text-sm font-medium text-stone-700 mb-1">
                Select User
            </label>
            <input
                v-model="userQuery"
                type="text"
                @input="handleUserInput"
                @focus="showSuggestions = true"
                @blur="setTimeout(() => showSuggestions = false, 200)"
                placeholder="Search for a user..."
                class="w-full rounded-lg border-stone-300 focus:border-amber-500 focus:ring-amber-500"
            >

            <div
                v-if="showSuggestions && userSuggestions.length"
                class="absolute z-10 mt-1 w-full bg-white border border-stone-300 rounded-lg shadow-lg max-h-48 overflow-auto"
            >
                <button
                    v-for="user in userSuggestions"
                    :key="user.id"
                    type="button"
                    @click="selectUser(user)"
                    class="w-full px-4 py-2 text-left text-sm hover:bg-stone-100 flex items-center gap-2"
                >
                    <span class="font-medium">{{ user.name }}</span>
                    <span class="text-stone-500 text-xs">{{ user.email }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
