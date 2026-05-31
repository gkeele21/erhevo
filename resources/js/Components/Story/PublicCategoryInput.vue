<script setup>
import { computed } from 'vue'
import AiCategorySuggest from './AiCategorySuggest.vue'

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    categories: {
        type: Array,
        default: () => []
    },
    content: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue'])

const handleAiSuggestion = (suggestion) => {
    // Find matching category by name
    const existing = props.categories.find(
        c => c.name.toLowerCase() === suggestion.name.toLowerCase()
    )

    if (existing) {
        emit('update:modelValue', existing.id)
    }
    // For public categories, we can't create new ones inline
    // The suggestion will just help users pick from existing ones
}
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <label class="block text-sm font-medium text-stone-700">
                Public Category
                <span class="text-stone-400 font-normal">(helps others discover your post)</span>
            </label>
            <AiCategorySuggest
                :content="content"
                :existing-categories="categories"
                type="public"
                @select-category="handleAiSuggestion"
            />
        </div>

        <div class="relative">
            <select
                :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)"
                class="w-full appearance-none rounded-lg border border-stone-300 bg-white py-2.5 pl-4 pr-10 text-stone-900 transition-colors focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 hover:border-stone-400"
            >
                <option value="">Select a category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                </option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                <svg class="h-5 w-5 text-stone-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
    </div>
</template>
