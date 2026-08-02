<script setup>
const props = defineProps({
    modelValue: {
        type: String,
        default: 'public'
    },
    options: {
        type: Array,
        required: true
    },
    // For the "specific friends" option: the user's friends and the chosen ids.
    friends: {
        type: Array,
        default: () => []
    },
    sharedUserIds: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['update:modelValue', 'update:sharedUserIds'])

const toggleFriend = (id) => {
    emit('update:sharedUserIds', props.sharedUserIds.includes(id)
        ? props.sharedUserIds.filter(existing => existing !== id)
        : [...props.sharedUserIds, id])
}
</script>

<template>
    <div class="space-y-3">
        <label class="block text-sm font-medium text-stone-700">
            Visibility
        </label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label
                v-for="option in options"
                :key="option.value"
                class="relative flex cursor-pointer rounded-lg border p-4 focus:outline-none"
                :class="[
                    modelValue === option.value
                        ? 'border-amber-600 ring-2 ring-amber-600'
                        : 'border-stone-300'
                ]"
            >
                <input
                    type="radio"
                    :value="option.value"
                    :checked="modelValue === option.value"
                    @change="emit('update:modelValue', option.value)"
                    class="sr-only"
                >
                <div class="flex flex-col">
                    <span class="block text-sm font-medium text-stone-800">
                        {{ option.label }}
                    </span>
                    <span class="mt-1 text-xs text-stone-500">
                        {{ option.description }}
                    </span>
                </div>
            </label>
        </div>

        <!-- Friend picker for "specific friends" visibility -->
        <div v-if="modelValue === 'custom'" class="rounded-lg border border-stone-200 bg-stone-50 p-4">
            <p class="text-sm font-medium text-stone-700 mb-2">Who can see this?</p>
            <div v-if="friends.length" class="space-y-1.5 max-h-48 overflow-y-auto">
                <label
                    v-for="friend in friends"
                    :key="friend.id"
                    class="flex items-center gap-2.5 text-sm text-stone-700"
                >
                    <input
                        type="checkbox"
                        :checked="sharedUserIds.includes(friend.id)"
                        class="rounded border-stone-300 text-amber-600 focus:ring-amber-500"
                        @change="toggleFriend(friend.id)"
                    />
                    {{ friend.name }}
                </label>
            </div>
            <p v-else class="text-sm text-stone-500">
                You don't have any friends yet — add some from the Friends page first.
            </p>
            <p v-if="friends.length && !sharedUserIds.length" class="mt-2 text-xs text-amber-700">
                No one selected yet — until you pick friends, only you can see this.
            </p>
        </div>
    </div>
</template>
