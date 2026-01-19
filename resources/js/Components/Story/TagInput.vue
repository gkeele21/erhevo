<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['update:modelValue'])

const inputValue = ref('')
const suggestions = ref([])
const showSuggestions = ref(false)
let debounceTimer = null

const searchTags = async (query) => {
    if (query.length < 2) {
        suggestions.value = []
        return
    }

    try {
        const response = await fetch(`/api/tags/search?q=${encodeURIComponent(query)}`)
        suggestions.value = await response.json()
        showSuggestions.value = true
    } catch (error) {
        console.error('Failed to search tags:', error)
    }
}

const handleInput = (e) => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        searchTags(e.target.value)
    }, 300)
}

const addTag = (tagName) => {
    const name = tagName.trim()
    if (name && !props.modelValue.includes(name)) {
        emit('update:modelValue', [...props.modelValue, name])
    }
    inputValue.value = ''
    suggestions.value = []
    showSuggestions.value = false
}

const removeTag = (index) => {
    const newTags = [...props.modelValue]
    newTags.splice(index, 1)
    emit('update:modelValue', newTags)
}

const handleKeydown = (e) => {
    if (e.key === 'Enter' && inputValue.value.trim()) {
        e.preventDefault()
        addTag(inputValue.value)
    } else if (e.key === 'Backspace' && !inputValue.value && props.modelValue.length) {
        removeTag(props.modelValue.length - 1)
    }
}
</script>

<template>
    <div class="space-y-2">
        <label class="block text-sm font-medium text-stone-700">
            Tags
        </label>
        <div class="relative">
            <div class="flex flex-wrap gap-2 p-2 border border-stone-300 rounded-lg bg-white min-h-[42px]">
                <span
                    v-for="(tag, index) in modelValue"
                    :key="index"
                    class="inline-flex items-center gap-1 px-2 py-1 bg-amber-100 text-amber-800 text-sm rounded"
                >
                    #{{ tag }}
                    <button
                        type="button"
                        @click="removeTag(index)"
                        class="text-amber-600 hover:text-amber-800"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </span>
                <input
                    v-model="inputValue"
                    type="text"
                    @input="handleInput"
                    @keydown="handleKeydown"
                    @blur="showSuggestions = false"
                    placeholder="Add tags..."
                    class="flex-1 min-w-[120px] border-0 bg-transparent focus:ring-0 text-sm p-0"
                >
            </div>

            <!-- Suggestions dropdown -->
            <div
                v-if="showSuggestions && suggestions.length"
                class="absolute z-10 mt-1 w-full bg-white border border-stone-300 rounded-lg shadow-lg max-h-48 overflow-auto"
            >
                <button
                    v-for="suggestion in suggestions"
                    :key="suggestion.id"
                    type="button"
                    @mousedown.prevent="addTag(suggestion.name)"
                    class="w-full px-4 py-2 text-left text-sm hover:bg-stone-100"
                >
                    #{{ suggestion.name }}
                </button>
            </div>
        </div>
        <p class="text-xs text-stone-500">Press Enter to add a tag</p>
    </div>
</template>
