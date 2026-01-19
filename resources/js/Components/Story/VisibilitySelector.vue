<script setup>
const props = defineProps({
    modelValue: {
        type: String,
        default: 'public'
    },
    options: {
        type: Array,
        required: true
    }
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
    <div class="space-y-3">
        <label class="block text-sm font-medium text-stone-700">
            Visibility
        </label>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
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
    </div>
</template>
