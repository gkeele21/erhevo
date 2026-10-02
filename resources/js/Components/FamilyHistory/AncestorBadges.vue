<script setup>
defineProps({
    person: Object,
    compact: Boolean,
})
</script>

<template>
    <span class="inline-flex flex-wrap gap-1">
        <span
            v-if="person.baptized_while_living"
            class="inline-flex items-center rounded-full bg-gold-100 text-gold-800 px-2 py-0.5 text-xs font-medium"
            :title="person.baptism_date ? `Baptized ${person.baptism_date}` : 'Baptized while living'"
        >
            Baptized{{ !compact && person.baptism_date ? ` ${person.baptism_date}` : '' }}
        </span>
        <span v-if="person.pioneer" class="inline-flex items-center rounded-full bg-teal-50 text-teal-700 px-2 py-0.5 text-xs font-medium">
            Pioneer
        </span>
        <template v-if="!compact">
            <span
                v-for="place in person.church_places"
                :key="place"
                class="inline-flex items-center rounded-full bg-navy-50 text-navy px-2 py-0.5 text-xs font-medium"
            >
                {{ place }}
            </span>
        </template>
        <span v-else-if="person.church_places?.length" class="inline-flex items-center rounded-full bg-navy-50 text-navy px-2 py-0.5 text-xs font-medium" :title="person.church_places.join(', ')">
            {{ person.church_places.length === 1 ? person.church_places[0].split(',')[0].split(' (')[0] : `${person.church_places.length} Church places` }}
        </span>
        <span v-if="person.researched" class="inline-flex items-center rounded-full bg-green-50 text-green-700 px-2 py-0.5 text-xs font-medium">
            ✓ Researched
        </span>
    </span>
</template>
