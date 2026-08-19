<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import HelpTip from '@/Components/HelpTip.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import TempleNav from '@/Components/Temples/TempleNav.vue'
import VisitFormModal from '@/Components/Temples/VisitFormModal.vue'
import VisitList from '@/Components/Temples/VisitList.vue'
import { formatLocalDate } from '@/utils/date.js'

const props = defineProps({
    visits: Array,
    temples: Array,
})

const showVisitForm = ref(false)
const editingVisit = ref(null)
const deletingVisit = ref(null)

const openNewVisit = () => {
    editingVisit.value = null
    showVisitForm.value = true
}

const openEditVisit = (visit) => {
    editingVisit.value = visit
    showVisitForm.value = true
}

const deleteVisit = () => {
    router.delete(route('temple-visits.destroy', deletingVisit.value.id), {
        preserveScroll: true,
        onSuccess: () => (deletingVisit.value = null),
    })
}

const uniqueTemples = computed(() => new Set(props.visits.map((v) => v.temple.id)).size)
</script>

<template>
    <AppLayout title="My Temple Visits">
        <template #header>
            <h2 class="flex items-center gap-1.5 font-semibold text-xl text-stone-800 leading-tight">
                My Temple Visits
                <HelpTip anchor="temple-tracker" tip="Every visit you've logged, across all temples. Open Help to learn more." />
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <TempleNav />

                <div class="bg-white rounded-lg shadow border border-stone-100 p-6">
                    <div class="flex items-center justify-between gap-4 mb-2">
                        <p class="text-sm text-stone-500">
                            {{ visits.length }} visit{{ visits.length === 1 ? '' : 's' }}
                            <span v-if="visits.length"> across {{ uniqueTemples }} temple{{ uniqueTemples === 1 ? '' : 's' }}</span>
                        </p>
                        <PrimaryButton @click="openNewVisit">Log a visit</PrimaryButton>
                    </div>

                    <VisitList
                        v-if="visits.length"
                        :visits="visits"
                        show-temple
                        @edit="openEditVisit"
                        @delete="deletingVisit = $event"
                    />
                    <p v-else class="text-stone-500 text-sm py-4">
                        No visits yet — log your first one, or browse the temple list to get started.
                    </p>
                </div>
            </div>
        </div>

        <VisitFormModal
            :show="showVisitForm"
            :temples="temples"
            :visit="editingVisit"
            @close="showVisitForm = false"
        />

        <ConfirmationModal :show="!!deletingVisit" @close="deletingVisit = null">
            <template #title>Delete visit</template>
            <template #content>
                Delete your
                {{ deletingVisit ? formatLocalDate(deletingVisit.visited_on, { year: 'numeric', month: 'long', day: 'numeric' }) : '' }}
                visit to {{ deletingVisit?.temple?.name }}? This cannot be undone.
            </template>
            <template #footer>
                <SecondaryButton @click="deletingVisit = null">Cancel</SecondaryButton>
                <DangerButton class="ms-3" @click="deleteVisit">Delete</DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
