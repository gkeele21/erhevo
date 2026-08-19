<script setup>
import { computed, ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import HelpTip from '@/Components/HelpTip.vue'
import DialogModal from '@/Components/DialogModal.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TempleNav from '@/Components/Temples/TempleNav.vue'
import TempleMap from '@/Components/Temples/TempleMap.vue'
import { haversineMiles } from '@/utils/geo.js'

const props = defineProps({
    temples: Array,
    visitedTempleIds: Array,
    trips: Array,
})

const visited = new Set(props.visitedTempleIds)

const center = ref(null) // { lat, lng }
const centerLabel = ref('') // resolved address, when the center came from a search
const radius = ref(100)
const locating = ref(false)
const geoError = ref('')

const address = ref('')
const geocoding = ref(false)

const setCenter = (point, label = '') => {
    center.value = point
    centerLabel.value = label
}

const useMyLocation = () => {
    geoError.value = ''
    if (!navigator.geolocation) {
        geoError.value = 'Geolocation is not available in this browser.'
        return
    }
    locating.value = true
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            setCenter({ lat: pos.coords.latitude, lng: pos.coords.longitude })
            locating.value = false
        },
        () => {
            geoError.value = 'Could not get your location — enter an address or click the map instead.'
            locating.value = false
        }
    )
}

// Address → center, resolved server-side (see TempleController@geocode).
const searchAddress = async () => {
    const query = address.value.trim()
    if (!query) return

    geoError.value = ''
    geocoding.value = true

    try {
        const response = await fetch(route('temples.geocode', { q: query }), {
            headers: { Accept: 'application/json' },
        })
        const data = await response.json().catch(() => ({}))

        if (!response.ok) {
            geoError.value = data.message || 'Could not look up that address — try again.'
            return
        }

        setCenter({ lat: data.lat, lng: data.lng }, data.label)
    } catch {
        geoError.value = 'Could not look up that address — try again.'
    } finally {
        geocoding.value = false
    }
}

const allWithVisited = computed(() =>
    props.temples.map((t) => ({ ...t, visited: visited.has(t.id) }))
)

// Temples inside the radius, nearest first. No center picked yet = empty.
const inRadius = computed(() => {
    if (!center.value) return []
    return allWithVisited.value
        .filter((t) => t.latitude != null)
        .map((t) => ({
            ...t,
            distance: haversineMiles(center.value.lat, center.value.lng, t.latitude, t.longitude),
        }))
        .filter((t) => t.distance <= radius.value)
        .sort((a, b) => a.distance - b.distance)
})

// --- Add to trip ---
const addToTrip = (temple, tripId) => {
    router.post(
        route('temple-trips.items.store', tripId),
        { temple_id: temple.id },
        { preserveScroll: true, preserveState: true }
    )
}

const newTripFor = ref(null) // temple the new trip starts with
const newTripForm = useForm({ name: '' })

const createTrip = () => {
    newTripForm
        .transform((data) => ({ ...data, temple_ids: [newTripFor.value.id] }))
        .post(route('temple-trips.store'), {
            onSuccess: () => {
                newTripFor.value = null
                newTripForm.reset()
            },
        })
}
</script>

<template>
    <AppLayout title="Explore Temples">
        <template #header>
            <h2 class="flex items-center gap-1.5 font-semibold text-xl text-stone-800 leading-tight">
                Explore Temples
                <HelpTip anchor="temple-tracker" tip="Pick a spot and radius to see nearby temples — visited ones are marked. Open Help to learn more." />
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <TempleNav />

                <!-- Controls -->
                <div class="bg-white rounded-lg shadow p-4 mb-6 border border-stone-100">
                    <div class="flex flex-col md:flex-row md:items-center gap-4">
                        <SecondaryButton :disabled="locating" @click="useMyLocation">
                            {{ locating ? 'Locating…' : 'Use my location' }}
                        </SecondaryButton>

                        <form class="flex items-center gap-2 w-full md:max-w-sm" @submit.prevent="searchAddress">
                            <input
                                id="address"
                                v-model="address"
                                type="text"
                                placeholder="…or enter an address, city, or ZIP"
                                aria-label="Address to center the map on"
                                class="w-full rounded-lg border-stone-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                            >
                            <SecondaryButton type="submit" :disabled="geocoding || !address.trim()">
                                {{ geocoding ? 'Searching…' : 'Search' }}
                            </SecondaryButton>
                        </form>

                        <div class="flex items-center gap-3 md:ms-auto">
                            <label for="radius" class="text-sm text-stone-600 whitespace-nowrap">
                                Within {{ radius }} mi
                            </label>
                            <input
                                id="radius"
                                v-model.number="radius"
                                type="range"
                                min="25"
                                max="500"
                                step="25"
                                class="w-48 accent-teal-600"
                            >
                        </div>
                    </div>
                    <p class="text-sm text-stone-500 mt-2">
                        …or click anywhere on the map to set the center.
                    </p>
                    <p v-if="centerLabel" class="text-sm text-stone-600 mt-1">
                        Centered on <span class="font-medium">{{ centerLabel }}</span>
                    </p>
                    <p v-if="geoError" class="text-sm text-red-600 mt-2">{{ geoError }}</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2">
                        <TempleMap
                            :temples="allWithVisited"
                            :fit-on-change="!center"
                            :circle-center="center"
                            :radius-miles="center ? radius : null"
                            click-to-set-center
                            height-class="h-[36rem]"
                            @map-click="setCenter($event)"
                        />
                        <p class="text-xs text-stone-400 mt-2">
                            <span class="inline-block w-3 h-3 rounded-full bg-teal-500 border border-teal-700 align-middle"></span>
                            Visited
                            <span class="inline-block w-3 h-3 rounded-full bg-white border-2 border-navy-600 align-middle ms-3"></span>
                            Not yet visited
                        </p>
                    </div>

                    <!-- Nearby list -->
                    <div class="bg-white rounded-lg shadow border border-stone-100 p-4 max-h-[36rem] overflow-y-auto">
                        <h3 class="font-semibold text-stone-800 mb-3">
                            <template v-if="center">
                                {{ inRadius.length }} temple{{ inRadius.length === 1 ? '' : 's' }} within {{ radius }} miles
                            </template>
                            <template v-else>Nearby temples</template>
                        </h3>

                        <p v-if="!center" class="text-sm text-stone-500">
                            Set a location to list temples in the area, sorted by distance.
                        </p>

                        <ul v-else class="divide-y divide-stone-100">
                            <li v-for="temple in inRadius" :key="temple.id" class="py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <Link
                                            :href="route('temples.show', temple.slug)"
                                            class="font-medium text-stone-800 hover:text-teal-700"
                                        >
                                            {{ temple.name }}
                                        </Link>
                                        <p class="text-xs text-stone-500 mt-0.5">
                                            {{ Math.round(temple.distance) }} mi
                                            <span v-if="temple.visited" class="text-teal-700"> · ✓ visited</span>
                                        </p>
                                    </div>
                                    <select
                                        class="shrink-0 text-xs rounded-lg border-stone-300 py-1 pe-7 focus:border-teal-500 focus:ring-teal-500"
                                        :value="''"
                                        @change="(e) => {
                                            const v = e.target.value
                                            e.target.value = ''
                                            if (v === 'new') newTripFor = temple
                                            else if (v) addToTrip(temple, Number(v))
                                        }"
                                    >
                                        <option value="" disabled>+ Trip</option>
                                        <option v-for="trip in trips" :key="trip.id" :value="trip.id">
                                            {{ trip.name }}
                                        </option>
                                        <option value="new">New trip…</option>
                                    </select>
                                </div>
                            </li>
                            <li v-if="!inRadius.length" class="py-3 text-sm text-stone-500">
                                No temples within {{ radius }} miles — widen the radius or move the center.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- New trip modal -->
        <DialogModal :show="!!newTripFor" max-width="md" @close="newTripFor = null">
            <template #title>New trip</template>
            <template #content>
                <p class="text-sm text-stone-500 mb-3">
                    Starts with <span class="font-medium">{{ newTripFor?.name }}</span> — add more temples any time.
                </p>
                <InputLabel for="new-trip-name" value="Trip name" />
                <input
                    id="new-trip-name"
                    v-model="newTripForm.name"
                    type="text"
                    placeholder="e.g. Utah trip"
                    class="mt-1 w-full rounded-lg border-stone-300 focus:border-teal-500 focus:ring-teal-500"
                    @keyup.enter="createTrip"
                >
                <InputError :message="newTripForm.errors.name" class="mt-1" />
            </template>
            <template #footer>
                <SecondaryButton @click="newTripFor = null">Cancel</SecondaryButton>
                <PrimaryButton
                    class="ms-3"
                    :disabled="newTripForm.processing || !newTripForm.name"
                    @click="createTrip"
                >
                    Create trip
                </PrimaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>
