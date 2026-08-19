<script setup>
import { computed, ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DangerButton from '@/Components/DangerButton.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import TempleNav from '@/Components/Temples/TempleNav.vue'
import TempleMap from '@/Components/Temples/TempleMap.vue'
import VisitFormModal from '@/Components/Temples/VisitFormModal.vue'
import { haversineMiles } from '@/utils/geo.js'

const props = defineProps({
    trip: Object,
    allTemples: Array,
    visitedTempleIds: Array,
})

const visited = new Set(props.visitedTempleIds)

const completedCount = computed(() => props.trip.items.filter((i) => i.completed_at).length)

// Trip map: completed items show as "visited" (filled) pins.
const plannedTemples = computed(() =>
    props.trip.items.map((item) => ({ ...item.temple, visited: !!item.completed_at }))
)

// --- Nearby suggestions ---
const showNearby = ref(true)
const nearbyRadius = ref(100)

const plannedIds = computed(() => new Set(props.trip.items.map((i) => i.temple.id)))
const plottedPlanned = computed(() => plannedTemples.value.filter((t) => t.latitude != null))

// Temples not on the trip, within the radius of *some* planned stop, keyed by
// how far the nearest planned stop is so the list reads as a detour cost.
const nearbyTemples = computed(() => {
    if (!plottedPlanned.value.length) return []

    return props.allTemples
        .filter((t) => t.latitude != null && !plannedIds.value.has(t.id))
        .map((t) => ({
            ...t,
            nearby: true,
            distance: Math.min(
                ...plottedPlanned.value.map((p) => haversineMiles(p.latitude, p.longitude, t.latitude, t.longitude))
            ),
        }))
        .filter((t) => t.distance <= nearbyRadius.value)
        .sort((a, b) => a.distance - b.distance)
})

const mapTemples = computed(() =>
    showNearby.value ? [...plannedTemples.value, ...nearbyTemples.value] : plannedTemples.value
)

const addToTrip = (temple) => {
    router.post(
        route('temple-trips.items.store', props.trip.id),
        { temple_id: temple.id },
        { preserveScroll: true, preserveState: true }
    )
}

const toggleItem = (item) => {
    router.patch(
        route('temple-trips.items.toggle', [props.trip.id, item.id]),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                // Checking off a temple you haven't logged → offer to log the visit.
                if (!item.completed_at && !visited.has(item.temple.id)) {
                    logVisitFor.value = item.temple
                }
            },
        }
    )
}

const removeItem = (item) => {
    router.delete(route('temple-trips.items.destroy', [props.trip.id, item.id]), { preserveScroll: true })
}

// Add temples
const addTempleId = ref('')
const availableTemples = computed(() => {
    const inTrip = new Set(props.trip.items.map((i) => i.temple.id))
    return props.allTemples.filter((t) => !inTrip.has(t.id))
})

const addTemple = () => {
    if (!addTempleId.value) return
    router.post(
        route('temple-trips.items.store', props.trip.id),
        { temple_id: addTempleId.value },
        { preserveScroll: true, onSuccess: () => (addTempleId.value = '') }
    )
}

// Rename / notes
const editing = ref(false)
const editForm = useForm({ name: props.trip.name, notes: props.trip.notes ?? '' })
const saveTrip = () => {
    editForm.put(route('temple-trips.update', props.trip.id), {
        preserveScroll: true,
        onSuccess: () => (editing.value = false),
    })
}

const confirmingDelete = ref(false)
const deleteTrip = () => router.delete(route('temple-trips.destroy', props.trip.id))

const logVisitFor = ref(null)
</script>

<template>
    <AppLayout :title="trip.name">
        <template #header>
            <h2 class="font-semibold text-xl text-stone-800 leading-tight">
                {{ trip.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <TempleNav />

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Checklist -->
                    <div class="bg-white rounded-lg shadow border border-stone-100 p-6">
                        <div class="flex items-center justify-between gap-4 mb-1">
                            <p class="text-sm font-medium text-teal-700">
                                {{ completedCount }}/{{ trip.items.length }} visited
                            </p>
                            <div class="flex gap-3 text-sm">
                                <button type="button" class="text-stone-500 hover:text-teal-700" @click="editing = !editing">
                                    {{ editing ? 'Close' : 'Edit' }}
                                </button>
                                <button type="button" class="text-stone-500 hover:text-red-600" @click="confirmingDelete = true">
                                    Delete trip
                                </button>
                            </div>
                        </div>
                        <p v-if="trip.notes && !editing" class="text-sm text-stone-500 mb-3 whitespace-pre-line">{{ trip.notes }}</p>

                        <!-- Edit name/notes -->
                        <div v-if="editing" class="bg-stone-50 rounded-lg p-4 mb-4 space-y-3">
                            <input
                                v-model="editForm.name"
                                type="text"
                                class="w-full rounded-lg border-stone-300 focus:border-teal-500 focus:ring-teal-500"
                            >
                            <textarea
                                v-model="editForm.notes"
                                rows="2"
                                placeholder="Notes…"
                                class="w-full rounded-lg border-stone-300 focus:border-teal-500 focus:ring-teal-500"
                            />
                            <div class="text-end">
                                <PrimaryButton :disabled="editForm.processing || !editForm.name" @click="saveTrip">
                                    Save
                                </PrimaryButton>
                            </div>
                        </div>

                        <ul class="divide-y divide-stone-100">
                            <li v-for="item in trip.items" :key="item.id" class="py-3 flex items-center gap-3">
                                <input
                                    type="checkbox"
                                    :checked="!!item.completed_at"
                                    class="rounded border-stone-300 text-teal-600 focus:ring-teal-500 cursor-pointer"
                                    @change="toggleItem(item)"
                                >
                                <div class="min-w-0 flex-1">
                                    <Link
                                        :href="route('temples.show', item.temple.slug)"
                                        class="font-medium hover:text-teal-700"
                                        :class="item.completed_at ? 'text-stone-400 line-through' : 'text-stone-800'"
                                    >
                                        {{ item.temple.name }}
                                    </Link>
                                    <p class="text-xs text-stone-400">
                                        {{ [item.temple.city, item.temple.state, item.temple.country].filter(Boolean).join(', ') }}
                                        <span v-if="visited.has(item.temple.id)" class="text-teal-700"> · ✓ visited before</span>
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="shrink-0 text-stone-400 hover:text-red-600 text-sm"
                                    title="Remove from trip"
                                    @click="removeItem(item)"
                                >
                                    &times;
                                </button>
                            </li>
                            <li v-if="!trip.items.length" class="py-3 text-sm text-stone-500">
                                No temples yet — add one below or from the Explore map.
                            </li>
                        </ul>

                        <!-- Add a temple -->
                        <div class="flex gap-2 mt-4">
                            <select
                                v-model="addTempleId"
                                class="flex-1 rounded-lg border-stone-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                            >
                                <option value="" disabled>Add a temple…</option>
                                <option v-for="t in availableTemples" :key="t.id" :value="t.id">
                                    {{ t.name }}
                                </option>
                            </select>
                            <SecondaryButton :disabled="!addTempleId" @click="addTemple">Add</SecondaryButton>
                        </div>
                    </div>

                    <!-- Trip map -->
                    <div>
                        <div v-if="plottedPlanned.length" class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-2">
                            <label class="flex items-center gap-2 text-sm text-stone-600">
                                <input
                                    v-model="showNearby"
                                    type="checkbox"
                                    class="rounded border-stone-300 text-teal-600 focus:ring-teal-500 cursor-pointer"
                                >
                                Show temples nearby
                            </label>
                            <label v-if="showNearby" class="flex items-center gap-2 text-sm text-stone-600">
                                Within
                                <select
                                    v-model.number="nearbyRadius"
                                    class="rounded-lg border-stone-300 text-sm py-1 pe-8 focus:border-teal-500 focus:ring-teal-500"
                                >
                                    <option :value="50">50 mi</option>
                                    <option :value="100">100 mi</option>
                                    <option :value="250">250 mi</option>
                                    <option :value="500">500 mi</option>
                                </select>
                                of a stop
                            </label>
                        </div>

                        <TempleMap
                            v-if="mapTemples.some((t) => t.latitude != null)"
                            :temples="mapTemples"
                            nearby-action-label="+ Add to trip"
                            height-class="h-[32rem]"
                            @temple-action="addToTrip"
                        />
                        <p class="text-xs text-stone-400 mt-2">
                            <span class="inline-block w-3 h-3 rounded-full bg-teal-500 border border-teal-700 align-middle"></span>
                            Checked off
                            <span class="inline-block w-3 h-3 rounded-full bg-white border-2 border-navy-600 align-middle ms-3"></span>
                            Still to visit
                            <template v-if="showNearby">
                                <span class="inline-block w-2.5 h-2.5 rounded-full bg-gold-400 border border-gold-700 align-middle ms-3"></span>
                                Nearby, not on the trip
                            </template>
                        </p>

                        <!-- Nearby temples you could add -->
                        <div v-if="showNearby && plottedPlanned.length" class="bg-white rounded-lg shadow border border-stone-100 p-4 mt-4">
                            <h3 class="font-semibold text-stone-800 mb-2 text-sm">
                                {{ nearbyTemples.length }} temple{{ nearbyTemples.length === 1 ? '' : 's' }} nearby
                            </h3>
                            <ul class="divide-y divide-stone-100 max-h-64 overflow-y-auto">
                                <li v-for="temple in nearbyTemples" :key="temple.id" class="py-2 flex items-center gap-3">
                                    <div class="min-w-0 flex-1">
                                        <Link
                                            :href="route('temples.show', temple.slug)"
                                            class="text-sm font-medium text-stone-800 hover:text-teal-700"
                                        >
                                            {{ temple.name }}
                                        </Link>
                                        <p class="text-xs text-stone-400">
                                            {{ Math.round(temple.distance) }} mi from a stop
                                            <span v-if="visited.has(temple.id)" class="text-teal-700"> · ✓ visited before</span>
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        class="shrink-0 text-xs font-semibold text-teal-700 hover:text-teal-900"
                                        @click="addToTrip(temple)"
                                    >
                                        + Add
                                    </button>
                                </li>
                                <li v-if="!nearbyTemples.length" class="py-2 text-sm text-stone-500">
                                    No other temples within {{ nearbyRadius }} miles — widen the radius to see more.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Offer to log a visit after checking off -->
        <VisitFormModal
            :show="!!logVisitFor"
            :temple="logVisitFor"
            @close="logVisitFor = null"
        />

        <ConfirmationModal :show="confirmingDelete" @close="confirmingDelete = false">
            <template #title>Delete trip</template>
            <template #content>
                Delete "{{ trip.name }}"? Your logged visits are kept — only the trip checklist is removed.
            </template>
            <template #footer>
                <SecondaryButton @click="confirmingDelete = false">Cancel</SecondaryButton>
                <DangerButton class="ms-3" @click="deleteTrip">Delete</DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
