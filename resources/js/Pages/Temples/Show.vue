<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import TempleNav from '@/Components/Temples/TempleNav.vue'
import TempleMap from '@/Components/Temples/TempleMap.vue'
import VisitFormModal from '@/Components/Temples/VisitFormModal.vue'
import VisitList from '@/Components/Temples/VisitList.vue'
import StarIcon from '@/Components/Temples/StarIcon.vue'
import { formatLocalDate } from '@/utils/date.js'

const props = defineProps({
    temple: Object,
    visits: Array,
    isFavorite: Boolean,
})

const toggleFavorite = () => {
    router.post(
        route('temples.favorite', props.temple.slug),
        {},
        { preserveScroll: true, preserveState: true, only: ['isFavorite'] }
    )
}

const imageFailed = ref(false)
const showVisitForm = ref(false)
const editingVisit = ref(null)
const deletingVisit = ref(null)

const openNewVisit = () => {
    editingVisit.value = null
    showVisitForm.value = true
}

const openEditVisit = (visit) => {
    editingVisit.value = { ...visit, temple_id: props.temple.id }
    showVisitForm.value = true
}

const deleteVisit = () => {
    router.delete(route('temple-visits.destroy', deletingVisit.value.id), {
        preserveScroll: true,
        onSuccess: () => (deletingVisit.value = null),
    })
}
</script>

<template>
    <AppLayout :title="temple.name">
        <template #header>
            <h2 class="flex items-center gap-2 font-semibold text-xl text-stone-800 leading-tight">
                {{ temple.name }}
                <button
                    type="button"
                    class="p-1 rounded-full hover:bg-stone-100 transition"
                    :title="isFavorite ? 'Remove from favorites' : 'Mark as favorite'"
                    :aria-label="isFavorite ? `Remove ${temple.name} from favorites` : `Mark ${temple.name} as a favorite`"
                    :aria-pressed="isFavorite"
                    @click="toggleFavorite"
                >
                    <StarIcon
                        :filled="isFavorite"
                        class="w-5 h-5"
                        :class="isFavorite ? 'text-gold-600' : 'text-stone-400'"
                    />
                </button>
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <TempleNav />

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Details -->
                    <div class="bg-white rounded-lg shadow border border-stone-100 overflow-hidden">
                        <div v-if="temple.photo_url && !imageFailed" class="bg-gradient-to-br from-navy-100 to-teal-100">
                            <img
                                :src="temple.photo_url"
                                :alt="temple.name"
                                class="w-full max-h-80 object-cover"
                                @error="imageFailed = true"
                            >
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <h3 class="text-sm font-semibold text-stone-400 uppercase tracking-wide">Address</h3>
                                <p class="text-stone-700 mt-1">
                                    <span v-if="temple.address">{{ temple.address }}<br></span>
                                    {{ [temple.city, temple.state].filter(Boolean).join(', ') }}<br>
                                    {{ temple.country }}
                                </p>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-stone-400 uppercase tracking-wide">Dedicated</h3>
                                <p class="text-stone-700 mt-1">
                                    {{ formatLocalDate(temple.dedicated_on, { year: 'numeric', month: 'long', day: 'numeric' }) }}
                                </p>
                            </div>
                            <p class="text-xs text-stone-400">
                                <a :href="temple.source_url" target="_blank" rel="noopener noreferrer" class="hover:text-teal-700 underline">
                                    More details at ChurchofJesusChristTemples.org
                                </a>
                            </p>
                        </div>
                    </div>

                    <!-- Mini map -->
                    <TempleMap
                        v-if="temple.latitude != null"
                        :temples="[{ ...temple, favorite: isFavorite }]"
                        height-class="h-80 lg:h-full lg:min-h-[20rem]"
                    />
                </div>

                <!-- My visits -->
                <div class="bg-white rounded-lg shadow border border-stone-100 p-6 mt-6">
                    <div class="flex items-center justify-between gap-4 mb-2">
                        <h3 class="text-lg font-semibold text-stone-800">My visits</h3>
                        <PrimaryButton @click="openNewVisit">Log a visit</PrimaryButton>
                    </div>

                    <VisitList
                        v-if="visits.length"
                        :visits="visits"
                        @edit="openEditVisit"
                        @delete="deletingVisit = $event"
                    />
                    <p v-else class="text-stone-500 text-sm py-4">
                        You haven't logged any visits to this temple yet.
                    </p>
                </div>

                <p class="mt-6">
                    <Link :href="route('temples.index')" class="text-sm text-stone-500 hover:text-teal-700">
                        &larr; All temples
                    </Link>
                </p>
            </div>
        </div>

        <VisitFormModal
            :show="showVisitForm"
            :temple="temple"
            :visit="editingVisit"
            @close="showVisitForm = false"
        />

        <ConfirmationModal :show="!!deletingVisit" @close="deletingVisit = null">
            <template #title>Delete visit</template>
            <template #content>
                Delete your
                {{ deletingVisit ? formatLocalDate(deletingVisit.visited_on, { year: 'numeric', month: 'long', day: 'numeric' }) : '' }}
                visit to {{ temple.name }}? This cannot be undone.
            </template>
            <template #footer>
                <SecondaryButton @click="deletingVisit = null">Cancel</SecondaryButton>
                <DangerButton class="ms-3" @click="deleteVisit">Delete</DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
