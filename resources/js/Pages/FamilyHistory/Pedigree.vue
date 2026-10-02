<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import FamilyHistoryNav from '@/Components/FamilyHistory/FamilyHistoryNav.vue'

const props = defineProps({
    generations: Number,
    // Heap-numbered: 1 is the focus person, n's parents are 2n and 2n+1.
    slots: Object,
    focusChild: Object,
})

const rows = computed(() => 2 ** (props.generations - 1))

const cells = computed(() => {
    const cells = []
    for (let g = 0; g < props.generations; g++) {
        const count = 2 ** g
        const span = rows.value / count
        for (let i = 0; i < count; i++) {
            const slot = count + i
            cells.push({
                slot,
                generation: g,
                person: props.slots[slot] ?? null,
                style: { gridColumn: g + 1, gridRow: `${i * span + 1} / span ${span}` },
                // Unknown parents only matter where the child is known.
                show: !!props.slots[slot] || !!props.slots[Math.floor(slot / 2)],
            })
        }
    }
    return cells.filter((c) => c.show)
})

const focus = computed(() => props.slots[1])
const lastGeneration = (cell) => cell.generation === props.generations - 1
const years = (p) => (p.birth_year || p.death_year) ? `${p.birth_year ?? '?'}–${p.death_year ?? ''}` : ''

const ring = (p) => {
    if (p.baptized_while_living) return 'border-gold-500 ring-2 ring-gold-300'
    if (p.pioneer) return 'border-teal-300 ring-2 ring-teal-100'
    return 'border-stone-200'
}
</script>

<template>
    <AppLayout :title="`Pedigree · ${focus.name}`">
        <template #header>
            <h2 class="font-semibold text-xl text-stone-800 leading-tight">Pedigree</h2>
        </template>

        <div class="py-12">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <FamilyHistoryNav />

                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 mb-4 text-sm text-stone-600">
                    <Link
                        v-if="focus.generation > 0"
                        :href="route('family-history.pedigree')"
                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-navy text-white text-sm font-medium hover:bg-navy-700"
                    >
                        ⌂ Back to me
                    </Link>
                    <Link
                        v-if="focusChild"
                        :href="route('family-history.pedigree', focusChild.fs_id)"
                        class="text-teal hover:text-navy"
                    >
                        ← Down to {{ focusChild.name }}
                    </Link>
                    <span class="inline-flex items-center gap-1.5"><span class="size-3 rounded-sm border-2 border-gold-500 bg-white" /> Baptized while living</span>
                    <span class="inline-flex items-center gap-1.5"><span class="size-3 rounded-sm border-2 border-teal-300 bg-white" /> Pioneer</span>
                    <span class="inline-flex items-center gap-1.5"><span class="size-3 rounded-sm bg-green-100 border border-green-300" /> Researched</span>
                </div>

                <div class="overflow-x-auto pb-2 pe-4">
                    <div
                        class="grid gap-x-4 gap-y-2 min-w-[760px]"
                        :style="{ gridTemplateColumns: `repeat(${generations}, minmax(0, 1fr))`, gridTemplateRows: `repeat(${rows}, minmax(4.5rem, auto))` }"
                    >
                        <div v-for="cell in cells" :key="cell.slot" :style="cell.style" class="flex items-center">
                            <div
                                v-if="cell.person"
                                class="relative w-full rounded-lg border p-3 shadow-sm"
                                :class="[ring(cell.person), cell.person.researched ? 'bg-green-50' : 'bg-white']"
                            >
                                <Link :href="route('family-history.show', cell.person.fs_id)" class="block">
                                    <p class="font-medium text-navy text-sm leading-snug hover:text-teal">{{ cell.person.name }}</p>
                                    <p class="text-xs text-stone-500">
                                        {{ years(cell.person) }}
                                        <span v-if="cell.person.baptism_date && cell.person.baptized_while_living" class="text-gold-800"> · bapt. {{ cell.person.baptism_date }}</span>
                                    </p>
                                    <p v-if="cell.generation > 0" class="text-xs text-stone-400 truncate">{{ cell.person.relationship }}</p>
                                </Link>
                                <Link
                                    v-if="lastGeneration(cell) && cell.person.has_parents"
                                    :href="route('family-history.pedigree', cell.person.fs_id)"
                                    class="absolute -end-3 top-1/2 -translate-y-1/2 size-6 rounded-full bg-navy text-white text-xs flex items-center justify-center hover:bg-teal"
                                    :title="`Continue up from ${cell.person.name}`"
                                >
                                    →
                                </Link>
                            </div>
                            <div v-else class="w-full rounded-lg border border-dashed border-stone-200 p-3 text-xs text-stone-400">
                                Not in your tree
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
