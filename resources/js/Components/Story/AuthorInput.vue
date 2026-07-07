<script setup>
import { watch } from 'vue'
import AuthorSelect from '@/Components/Story/AuthorSelect.vue'

const props = defineProps({
    authorType: {
        type: String,
        default: 'self'
    },
    // The typed author name; carries a new author's name to the server, which
    // find-or-creates the Author entity when no authorId is chosen.
    authorText: {
        type: String,
        default: ''
    },
    authorId: {
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
    'update:authorId'
])

// Leaving "Someone else" clears the chosen/typed author.
watch(() => props.authorType, (type) => {
    if (type !== 'author') {
        emit('update:authorText', '')
        emit('update:authorId', null)
    }
})
</script>

<template>
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-stone-700 mb-2">
                Author Attribution
            </label>
            <div class="grid grid-cols-2 gap-3">
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

        <!-- Author entity (search existing or add new) -->
        <div v-if="authorType === 'author'">
            <label class="block text-sm font-medium text-stone-700 mb-1">
                Author
            </label>
            <AuthorSelect
                :model-value="authorId"
                @update:model-value="emit('update:authorId', $event)"
                :name="authorText"
                @update:name="emit('update:authorText', $event)"
                placeholder="e.g. Maya Angelou — search or add a new author..."
            />
        </div>
    </div>
</template>
