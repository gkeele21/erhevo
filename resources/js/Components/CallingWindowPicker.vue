<script setup>
import { computed } from 'vue'

const props = defineProps({
    // [{ id, label, start_date, end_date }]
    callings: { type: Array, default: () => [] },
    // Selected AuthorCalling ids.
    modelValue: { type: Array, default: () => [] },
    // Which page's palette to wear: the library's amber, or the plan form's teal.
    variant: { type: String, default: 'library' }
})

const emit = defineEmits(['update:modelValue'])

const isPlan = computed(() => props.variant === 'plan')

const boxClass = computed(() => isPlan.value
    ? 'border-navy-50 divide-navy-50'
    : 'border-stone-200 divide-stone-100')

const rowClass = computed(() => isPlan.value
    ? 'text-navy hover:bg-ivory'
    : 'text-stone-700 hover:bg-amber-50')

const checkClass = computed(() => isPlan.value
    ? 'rounded border-navy-100 text-teal focus:ring-aqua'
    : 'rounded border-stone-300 text-amber-600 focus:ring-amber-500')

const hintClass = computed(() => isPlan.value ? 'text-teal' : 'text-stone-500')

// Slice the year off the string rather than parsing it — Date-parsing a
// date-only value shifts it a day (and possibly a year) west of UTC.
const periodLabel = (calling) => {
    const start = calling.start_date ? calling.start_date.slice(0, 4) : '?'
    const end = calling.end_date ? calling.end_date.slice(0, 4) : 'present'

    return `${calling.label} (${start}–${end})`
}

const isSelected = (id) => props.modelValue.includes(id)

const toggle = (id) => {
    emit('update:modelValue', isSelected(id)
        ? props.modelValue.filter(selected => selected !== id)
        : [...props.modelValue, id])
}

const clear = () => emit('update:modelValue', [])
</script>

<template>
    <div>
        <div class="max-h-40 overflow-y-auto rounded-lg border divide-y" :class="boxClass">
            <label
                v-for="calling in callings"
                :key="calling.id"
                class="flex items-start gap-2 px-3 py-2 text-sm cursor-pointer"
                :class="rowClass"
            >
                <input
                    type="checkbox"
                    class="mt-0.5 shrink-0"
                    :class="checkClass"
                    :checked="isSelected(calling.id)"
                    @change="toggle(calling.id)"
                >
                <span>{{ periodLabel(calling) }}</span>
            </label>
        </div>

        <div class="flex items-center justify-between gap-2 mt-1">
            <p class="text-xs" :class="hintClass">
                {{ modelValue.length
                    ? `${modelValue.length} of ${callings.length} selected`
                    : 'Any calling they held' }}
            </p>
            <button
                v-if="modelValue.length"
                type="button"
                class="text-xs underline"
                :class="isPlan ? 'text-teal hover:text-navy' : 'text-amber-700 hover:text-amber-900'"
                @click="clear"
            >
                Clear
            </button>
        </div>
    </div>
</template>
