<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import DialogModal from '@/Components/DialogModal.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import Checkbox from '@/Components/Checkbox.vue'
import { ORDINANCES } from '@/utils/ordinances.js'

const props = defineProps({
    show: { type: Boolean, default: false },
    // Preselected temple ({ id, name }); when null, a temple select is
    // shown using `temples`.
    temple: { type: Object, default: null },
    temples: { type: Array, default: () => [] },
    // Existing visit to edit; null = log a new visit.
    visit: { type: Object, default: null },
})

const emit = defineEmits(['close'])

const localToday = () => {
    const now = new Date()
    return [
        now.getFullYear(),
        String(now.getMonth() + 1).padStart(2, '0'),
        String(now.getDate()).padStart(2, '0'),
    ].join('-')
}

const form = useForm({
    temple_id: null,
    visited_on: localToday(),
    ordinances: [],
    notes: '',
})

watch(
    () => props.show,
    (show) => {
        if (!show) return
        form.clearErrors()
        form.temple_id = props.visit?.temple?.id ?? props.visit?.temple_id ?? props.temple?.id ?? null
        form.visited_on = props.visit?.visited_on ?? localToday()
        form.ordinances = [...(props.visit?.ordinances ?? [])]
        form.notes = props.visit?.notes ?? ''
    }
)

const editing = computed(() => !!props.visit)

const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => emit('close') }

    if (editing.value) {
        form.put(route('temple-visits.update', props.visit.id), options)
    } else {
        form.post(route('temple-visits.store'), options)
    }
}
</script>

<template>
    <DialogModal :show="show" max-width="lg" @close="$emit('close')">
        <template #title>
            {{ editing ? 'Edit visit' : `Log a visit${temple ? ` — ${temple.name}` : ''}` }}
        </template>

        <template #content>
            <form class="space-y-4" @submit.prevent="submit">
                <div v-if="!temple && !editing">
                    <InputLabel for="visit-temple" value="Temple" />
                    <select
                        id="visit-temple"
                        v-model="form.temple_id"
                        class="mt-1 w-full rounded-lg border-stone-300 focus:border-teal-500 focus:ring-teal-500"
                    >
                        <option :value="null" disabled>Choose a temple…</option>
                        <option v-for="t in temples" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>
                    <InputError :message="form.errors.temple_id" class="mt-1" />
                </div>

                <div>
                    <InputLabel for="visit-date" value="Date of visit" />
                    <input
                        id="visit-date"
                        v-model="form.visited_on"
                        type="date"
                        :max="localToday()"
                        class="mt-1 rounded-lg border-stone-300 focus:border-teal-500 focus:ring-teal-500"
                    >
                    <InputError :message="form.errors.visited_on" class="mt-1" />
                </div>

                <div>
                    <InputLabel value="Ordinances" />
                    <div class="mt-2 space-y-2">
                        <label
                            v-for="ordinance in ORDINANCES"
                            :key="ordinance.value"
                            class="flex items-center gap-2 text-sm text-stone-700"
                        >
                            <Checkbox v-model:checked="form.ordinances" :value="ordinance.value" />
                            {{ ordinance.label }}
                        </label>
                    </div>
                    <p class="text-xs text-stone-400 mt-2">
                        Leave all unchecked for &ldquo;just a visit&rdquo;.
                    </p>
                    <InputError :message="form.errors.ordinances" class="mt-1" />
                </div>

                <div>
                    <InputLabel for="visit-notes" value="Notes (optional)" />
                    <textarea
                        id="visit-notes"
                        v-model="form.notes"
                        rows="3"
                        class="mt-1 w-full rounded-lg border-stone-300 focus:border-teal-500 focus:ring-teal-500"
                        placeholder="Anything you want to remember about this visit…"
                    />
                    <InputError :message="form.errors.notes" class="mt-1" />
                </div>
            </form>
        </template>

        <template #footer>
            <SecondaryButton @click="$emit('close')">Cancel</SecondaryButton>
            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing || !form.temple_id"
                @click="submit"
            >
                {{ editing ? 'Save changes' : 'Log visit' }}
            </PrimaryButton>
        </template>
    </DialogModal>
</template>
