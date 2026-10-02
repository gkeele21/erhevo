<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import InputError from '@/Components/InputError.vue'
import FamilyHistoryNav from '@/Components/FamilyHistory/FamilyHistoryNav.vue'
import AncestorBadges from '@/Components/FamilyHistory/AncestorBadges.vue'

const props = defineProps({
    ancestor: Object,
    research: Object,
    parents: Array,
    child: Object,
})

const form = useForm({
    researched: props.research.researched,
    notes: props.research.notes ?? '',
    lds_baptism_on: props.research.lds_baptism_on ?? '',
    // Tri-state: null follows the imported tree, true/false overrides it.
    baptized_while_living: props.research.baptized_while_living,
    pioneer: props.research.pioneer,
})

const save = () => {
    form.transform((data) => ({ ...data, lds_baptism_on: data.lds_baptism_on || null }))
        .put(route('family-history.research.update', props.ancestor.fs_id), { preserveScroll: true })
}

const markResearched = () => {
    form.researched = true
    save()
}

const lifespan = computed(() => {
    const a = props.ancestor
    return (a.birth_year || a.death_year) ? `${a.birth_year ?? '?'}–${a.death_year ?? ''}` : ''
})

const vitals = computed(() => [
    { label: 'Born', date: props.ancestor.birth_date, place: props.ancestor.birth_place },
    { label: 'Died', date: props.ancestor.death_date, place: props.ancestor.death_place, hidden: !props.ancestor.deceased && !props.ancestor.death_date },
    { label: 'Buried', date: props.ancestor.burial_date, place: props.ancestor.burial_place },
    { label: 'LDS baptism', date: props.ancestor.baptism_date, place: props.ancestor.baptism_date ? (props.ancestor.baptized_while_living ? 'While living' : 'After death (by proxy)') : null },
].filter((v) => !v.hidden && (v.date || v.place)))

const treeSays = (value) => (value ? 'yes' : 'no')
</script>

<template>
    <AppLayout :title="ancestor.name">
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="font-semibold text-xl text-stone-800 leading-tight">
                    {{ ancestor.name }} <span class="font-normal text-stone-400">{{ lifespan }}</span>
                </h2>
                <p class="text-sm text-stone-500">
                    <template v-if="ancestor.generation === 0">You</template>
                    <template v-else>Your {{ ancestor.relationship.toLowerCase() }}<span v-if="ancestor.side"> on your {{ ancestor.side }}'s side</span></template>
                    · {{ ancestor.fs_id }}
                </p>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <FamilyHistoryNav>
                    <Link :href="route('family-history.pedigree', ancestor.fs_id)" class="ms-auto text-sm text-teal hover:text-navy">
                        View in pedigree →
                    </Link>
                    <Link :href="route('family-history.random')" class="px-4 py-2 bg-amber text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors">
                        Surprise me
                    </Link>
                </FamilyHistoryNav>

                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Vitals -->
                        <section class="bg-white rounded-lg shadow border border-stone-100 p-6">
                            <AncestorBadges :person="ancestor" class="mb-4" />
                            <dl class="grid gap-4 sm:grid-cols-2">
                                <div v-for="v in vitals" :key="v.label">
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-stone-400">{{ v.label }}</dt>
                                    <dd class="text-stone-800">{{ v.date || 'Date unknown' }}</dd>
                                    <dd v-if="v.place" class="text-sm text-stone-500">{{ v.place }}</dd>
                                </div>
                            </dl>

                            <div class="mt-6 flex flex-wrap gap-2">
                                <a :href="ancestor.links.memories" target="_blank" rel="noopener" class="px-3 py-2 rounded-lg bg-navy text-white text-sm font-medium hover:bg-navy-700">
                                    Stories &amp; photos on FamilySearch ↗
                                </a>
                                <a :href="ancestor.links.familysearch" target="_blank" rel="noopener" class="px-3 py-2 rounded-lg border border-stone-200 text-sm text-stone-700 hover:bg-stone-50">
                                    Full record ↗
                                </a>
                                <a :href="ancestor.links.ordinances" target="_blank" rel="noopener" class="px-3 py-2 rounded-lg border border-stone-200 text-sm text-stone-700 hover:bg-stone-50">
                                    Ordinances ↗
                                </a>
                            </div>
                        </section>

                        <!-- Church history -->
                        <section
                            v-if="ancestor.church_place_details.length || ancestor.pioneer_signals.length || ancestor.lds_affiliation"
                            class="bg-white rounded-lg shadow border border-stone-100 p-6"
                        >
                            <h3 class="font-semibold text-navy mb-3">Church history clues</h3>
                            <ul class="space-y-1.5 text-sm text-stone-700">
                                <li v-for="place in ancestor.church_place_details" :key="place.key">
                                    Lived {{ place.proximity === 'near' ? 'near' : 'in' }}
                                    <Link :href="`${route('family-history.church-sites')}#${place.key}`" class="font-semibold text-navy hover:text-teal">{{ place.label }}</Link>
                                    ({{ place.from === place.to ? place.from : `${place.from}–${place.to}` }})
                                    <span v-if="place.proximity === 'near' && place.where" class="text-stone-400">· {{ place.where }}</span>
                                </li>
                                <li v-for="signal in ancestor.pioneer_signals" :key="signal">{{ signal }}</li>
                                <li v-if="ancestor.lds_affiliation">Religion recorded as Latter-day Saint</li>
                            </ul>
                            <p class="mt-4 text-sm text-stone-500">
                                Confirm a pioneer crossing in the
                                <a href="https://history.churchofjesuschrist.org/chd/search?lang=eng" target="_blank" rel="noopener" class="text-teal hover:text-navy underline">Church History Biographical Database</a>
                                (search for {{ ancestor.name }}).
                            </p>
                        </section>

                        <!-- Timeline -->
                        <section v-if="ancestor.events.length" class="bg-white rounded-lg shadow border border-stone-100 p-6">
                            <h3 class="font-semibold text-navy mb-3">Life events</h3>
                            <ol class="relative border-s border-stone-200 ms-2 space-y-3">
                                <li v-for="(event, i) in ancestor.events" :key="i" class="ms-4">
                                    <span class="absolute -start-1.5 mt-1.5 size-3 rounded-full border border-white bg-teal-300" />
                                    <p class="text-sm">
                                        <span class="font-medium text-stone-800">{{ event.label }}</span>
                                        <span v-if="event.date" class="text-stone-500"> · {{ event.date }}</span>
                                    </p>
                                    <p v-if="event.place" class="text-sm text-stone-500">{{ event.place }}</p>
                                    <p v-if="event.detail" class="text-sm text-stone-400">{{ event.detail }}</p>
                                </li>
                            </ol>
                        </section>
                    </div>

                    <div class="space-y-6">
                        <!-- Research -->
                        <section class="bg-white rounded-lg shadow border border-stone-100 p-6">
                            <h3 class="font-semibold text-navy mb-3">Your research</h3>

                            <button
                                v-if="!research.researched"
                                type="button"
                                class="w-full mb-4 px-4 py-2 rounded-lg bg-teal-500 text-white text-sm font-medium hover:bg-teal-600"
                                :disabled="form.processing"
                                @click="markResearched"
                            >
                                Mark as researched
                            </button>
                            <p v-else class="mb-4 text-sm text-green-700">
                                ✓ Researched
                                <button type="button" class="ms-2 text-stone-400 hover:text-stone-600 underline" @click="form.researched = false; save()">undo</button>
                            </p>

                            <form class="space-y-4" @submit.prevent="save">
                                <div>
                                    <label class="block text-sm font-medium text-stone-700" for="notes">Notes</label>
                                    <textarea id="notes" v-model="form.notes" rows="5" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-teal focus:ring-teal" placeholder="What you learned, stories worth retelling…" />
                                    <InputError :message="form.errors.notes" />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-stone-700" for="baptism">LDS baptism date</label>
                                    <input id="baptism" v-model="form.lds_baptism_on" type="date" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-teal focus:ring-teal" />
                                    <p v-if="ancestor.gedcom_baptism_date" class="mt-1 text-xs text-stone-400">From your tree: {{ ancestor.gedcom_baptism_date }}</p>
                                    <p v-else class="mt-1 text-xs text-stone-400">Check the Ordinances tab on FamilySearch.</p>
                                    <InputError :message="form.errors.lds_baptism_on" />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-stone-700" for="bwl">Baptized while living?</label>
                                    <select id="bwl" v-model="form.baptized_while_living" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-teal focus:ring-teal">
                                        <option :value="null">Go by my tree ({{ treeSays(ancestor.gedcom_baptized_while_living) }})</option>
                                        <option :value="true">Yes</option>
                                        <option :value="false">No</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-stone-700" for="pioneer">Pioneer?</label>
                                    <select id="pioneer" v-model="form.pioneer" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-teal focus:ring-teal">
                                        <option :value="null">Go by the clues ({{ ancestor.likely_pioneer ? 'likely' : 'no' }})</option>
                                        <option :value="true">Yes — crossed the plains</option>
                                        <option :value="false">No</option>
                                    </select>
                                </div>

                                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                            </form>
                        </section>

                        <!-- Line -->
                        <section class="bg-white rounded-lg shadow border border-stone-100 p-6">
                            <h3 class="font-semibold text-navy mb-3">In your line</h3>
                            <ul class="space-y-2 text-sm">
                                <li v-for="parent in parents" :key="parent.fs_id">
                                    <span class="text-stone-400">{{ parent.sex === 'F' ? 'Mother' : 'Father' }}:</span>{{ ' ' }}
                                    <Link :href="route('family-history.show', parent.fs_id)" class="text-teal hover:text-navy">{{ parent.name }}</Link>
                                </li>
                                <li v-if="!parents.length" class="text-stone-400">No parents in your tree yet — a research opportunity.</li>
                                <li v-if="child">
                                    <span class="text-stone-400">Child in your line:</span>{{ ' ' }}
                                    <Link :href="route('family-history.show', child.fs_id)" class="text-teal hover:text-navy">{{ child.name }}</Link>
                                </li>
                            </ul>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
