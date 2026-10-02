<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import InputError from '@/Components/InputError.vue'
import FamilyHistoryNav from '@/Components/FamilyHistory/FamilyHistoryNav.vue'
import FamilyHistoryHeader from '@/Components/FamilyHistory/FamilyHistoryHeader.vue'
import { formatLocalDate } from '@/utils/date.js'

const props = defineProps({
    tree: Object,
    maxUploadMb: Number,
})

const form = useForm({
    file: null,
    root_fs_id: '',
})

const tooBig = ref(false)
const pick = (event) => {
    const file = event.target.files[0] ?? null
    tooBig.value = !!file && file.size > props.maxUploadMb * 1024 * 1024
    form.file = file
}

const submit = () => {
    form.transform((data) => ({ ...data, root_fs_id: data.root_fs_id.trim().toUpperCase() || null }))
        .post(route('family-history.import.store'), { forceFormData: true })
}

const confirmingDelete = ref(false)
const destroy = () => {
    router.delete(route('family-history.destroy'), { onFinish: () => (confirmingDelete.value = false) })
}
</script>

<template>
    <AppLayout title="Import Family Tree">
        <template #header>
            <FamilyHistoryHeader title="Import your tree" />
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                <FamilyHistoryNav v-if="tree" />

                <section v-if="tree" class="bg-white rounded-lg shadow border border-stone-100 p-6">
                    <h3 class="font-semibold text-navy">Your current tree</h3>
                    <p class="mt-1 text-sm text-stone-600">
                        {{ tree.ancestor_count.toLocaleString() }} ancestors of {{ tree.root_name }} ({{ tree.root_fs_id }}),
                        {{ tree.generation_count }} generations, imported
                        {{ formatLocalDate(tree.imported_at.slice(0, 10), { year: 'numeric', month: 'long', day: 'numeric' }) }}
                        <span v-if="tree.source">from {{ tree.source }}</span>.
                    </p>
                    <p class="mt-2 text-sm text-stone-500">
                        Importing again replaces the tree. Your notes and research carry over.
                    </p>
                </section>

                <section class="bg-white rounded-lg shadow border border-stone-100 p-6">
                    <h3 class="font-semibold text-navy">{{ tree ? 'Import an updated tree' : 'Bring in your family tree' }}</h3>
                    <p class="mt-1 text-sm text-stone-600">
                        Erhevo reads a GEDCOM file exported from free genealogy software that syncs with FamilySearch.
                        Ancestral Quest Basics works well:
                    </p>
                    <ol class="mt-3 space-y-2 text-sm text-stone-700 list-decimal ms-5">
                        <li>
                            Download <a href="https://www.ancquest.com" target="_blank" rel="noopener" class="text-teal hover:text-navy underline">Ancestral Quest</a>
                            (Mac or Windows) and choose <strong>Ancestral Quest Basics</strong>, the free version.
                        </li>
                        <li>In its preferences, turn on the <strong>LDS</strong> options so baptism dates come along.</li>
                        <li><strong>File → New</strong> to create a file, then <strong>FamilySearch → Import Family Lines</strong> and sign in with your Church Account.</li>
                        <li>Choose to download your ancestors, as many generations as you like.</li>
                        <li><strong>File → Export</strong>, include LDS data, and save as a <strong>.ged</strong> file.</li>
                    </ol>
                    <p class="mt-3 text-xs text-stone-500">
                        Only your direct-line ancestors are kept. Everyone else in the file, including living relatives, is discarded, and the file itself isn't stored.
                    </p>

                    <form class="mt-6 space-y-4" @submit.prevent="submit">
                        <div>
                            <label class="block text-sm font-medium text-stone-700" for="file">GEDCOM file</label>
                            <input id="file" type="file" accept=".ged" class="mt-1 block w-full text-sm text-stone-600" @change="pick" />
                            <p class="mt-1 text-xs text-stone-400">Up to {{ maxUploadMb }} MB.</p>
                            <p v-if="tooBig" class="mt-1 text-sm text-red-600">
                                That file is over {{ maxUploadMb }} MB. Export fewer generations and try again.
                            </p>
                            <InputError :message="form.errors.file" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-stone-700" for="root">Start from FamilySearch ID <span class="font-normal text-stone-400">(optional)</span></label>
                            <input id="root" v-model="form.root_fs_id" type="text" placeholder="e.g. KWCM-8HC" class="mt-1 w-48 rounded-md border-stone-300 text-sm focus:border-teal focus:ring-teal" />
                            <p class="mt-1 text-xs text-stone-400">Leave blank to start from the first person in the file, usually you.</p>
                            <InputError :message="form.errors.root_fs_id" />
                        </div>

                        <div class="flex items-center gap-3">
                            <PrimaryButton :disabled="!form.file || tooBig || form.processing">
                                {{ form.processing ? 'Importing…' : 'Import' }}
                            </PrimaryButton>
                            <span v-if="form.processing" class="text-sm text-stone-500">
                                {{ form.progress && form.progress.percentage < 100 ? `Uploading ${form.progress.percentage}%` : 'Reading your tree — large trees take a few seconds.' }}
                            </span>
                        </div>
                    </form>
                </section>

                <section v-if="tree" class="bg-white rounded-lg shadow border border-stone-100 p-6">
                    <h3 class="font-semibold text-navy">Delete your tree</h3>
                    <p class="mt-1 text-sm text-stone-600">Removes your imported ancestors and all your research notes from Erhevo. Nothing on FamilySearch is affected.</p>
                    <DangerButton class="mt-4" @click="confirmingDelete = true">Delete tree</DangerButton>
                </section>
            </div>
        </div>

        <ConfirmationModal :show="confirmingDelete" @close="confirmingDelete = false">
            <template #title>Delete your family tree</template>
            <template #content>
                This deletes all {{ tree?.ancestor_count.toLocaleString() }} imported ancestors and every research note you've written. This cannot be undone.
            </template>
            <template #footer>
                <SecondaryButton @click="confirmingDelete = false">Cancel</SecondaryButton>
                <DangerButton class="ms-3" @click="destroy">Delete</DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
