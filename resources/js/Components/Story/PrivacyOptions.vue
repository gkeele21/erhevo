<script setup>
const props = defineProps({
    hideCreator: Boolean,
    hideAuthor: Boolean,
    anonymizeNames: Boolean,
    visibility: String
})

const emit = defineEmits([
    'update:hideCreator',
    'update:hideAuthor',
    'update:anonymizeNames'
])
</script>

<template>
    <div v-if="visibility === 'public'" class="space-y-4 p-4 bg-stone-50 rounded-lg">
        <h4 class="text-sm font-medium text-stone-700">
            Privacy Options (for public stories)
        </h4>

        <div class="space-y-3">
            <label class="flex items-center gap-3 cursor-pointer">
                <input
                    type="checkbox"
                    :checked="hideCreator"
                    @change="emit('update:hideCreator', $event.target.checked)"
                    class="rounded border-stone-300 text-amber-600 focus:ring-amber-500"
                >
                <div>
                    <span class="text-sm font-medium text-stone-800">Hide my name</span>
                    <p class="text-xs text-stone-500">Your name won't be shown as the creator</p>
                </div>
            </label>

            <label class="flex items-center gap-3 cursor-pointer">
                <input
                    type="checkbox"
                    :checked="hideAuthor"
                    @change="emit('update:hideAuthor', $event.target.checked)"
                    class="rounded border-stone-300 text-amber-600 focus:ring-amber-500"
                >
                <div>
                    <span class="text-sm font-medium text-stone-800">Hide author</span>
                    <p class="text-xs text-stone-500">The author attribution won't be shown</p>
                </div>
            </label>

            <label class="flex items-center gap-3 cursor-pointer">
                <input
                    type="checkbox"
                    :checked="anonymizeNames"
                    @change="emit('update:anonymizeNames', $event.target.checked)"
                    class="rounded border-stone-300 text-amber-600 focus:ring-amber-500"
                >
                <div>
                    <span class="text-sm font-medium text-stone-800">Anonymize names in content</span>
                    <p class="text-xs text-stone-500">Names mentioned in your story will be automatically replaced</p>
                </div>
            </label>
        </div>
    </div>
</template>
