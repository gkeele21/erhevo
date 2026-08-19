<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import HelpTip from '@/Components/HelpTip.vue'
import DialogModal from '@/Components/DialogModal.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TempleNav from '@/Components/Temples/TempleNav.vue'

defineProps({
    trips: Array,
})

const showCreate = ref(false)
const form = useForm({ name: '', notes: '' })

const createTrip = () => {
    form.post(route('temple-trips.store'), {
        onSuccess: () => {
            showCreate.value = false
            form.reset()
        },
    })
}
</script>

<template>
    <AppLayout title="Temple Trips">
        <template #header>
            <h2 class="flex items-center gap-1.5 font-semibold text-xl text-stone-800 leading-tight">
                Temple Trips
                <HelpTip anchor="temple-tracker" tip="Plan trips as temple checklists and mark them off as you go. Open Help to learn more." />
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <TempleNav />

                <div class="flex justify-end mb-4">
                    <PrimaryButton @click="showCreate = true">New trip</PrimaryButton>
                </div>

                <div v-if="trips.length" class="space-y-4">
                    <Link
                        v-for="trip in trips"
                        :key="trip.id"
                        :href="route('temple-trips.show', trip.id)"
                        class="block bg-white rounded-lg shadow p-5 border border-stone-100 hover:shadow-md hover:border-teal-200 transition"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <h3 class="text-lg font-semibold text-stone-800">{{ trip.name }}</h3>
                                <p v-if="trip.notes" class="text-sm text-stone-500 mt-1 line-clamp-2">{{ trip.notes }}</p>
                            </div>
                            <div class="shrink-0 text-end">
                                <p class="text-sm font-medium text-teal-700">
                                    {{ trip.completed_items_count }}/{{ trip.items_count }} visited
                                </p>
                                <div class="w-28 h-1.5 bg-stone-100 rounded-full mt-1.5 overflow-hidden">
                                    <div
                                        class="h-full bg-teal-500 rounded-full"
                                        :style="{ width: trip.items_count ? `${(trip.completed_items_count / trip.items_count) * 100}%` : '0%' }"
                                    />
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>

                <div v-else class="bg-white rounded-lg shadow p-12 text-center border border-stone-100">
                    <h3 class="text-lg font-semibold text-stone-800 mb-2">No trips yet</h3>
                    <p class="text-stone-500">
                        Create a trip to build a checklist of temples you plan to visit —
                        or add temples from the Explore map.
                    </p>
                </div>
            </div>
        </div>

        <DialogModal :show="showCreate" max-width="md" @close="showCreate = false">
            <template #title>New trip</template>
            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="trip-name" value="Trip name" />
                        <input
                            id="trip-name"
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Utah trip"
                            class="mt-1 w-full rounded-lg border-stone-300 focus:border-teal-500 focus:ring-teal-500"
                            @keyup.enter="createTrip"
                        >
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="trip-notes" value="Notes (optional)" />
                        <textarea
                            id="trip-notes"
                            v-model="form.notes"
                            rows="2"
                            class="mt-1 w-full rounded-lg border-stone-300 focus:border-teal-500 focus:ring-teal-500"
                        />
                        <InputError :message="form.errors.notes" class="mt-1" />
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="showCreate = false">Cancel</SecondaryButton>
                <PrimaryButton class="ms-3" :disabled="form.processing || !form.name" @click="createTrip">
                    Create trip
                </PrimaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>
