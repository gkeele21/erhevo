<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import StarIcon from '@/Components/Temples/StarIcon.vue'
import { formatLocalDate } from '@/utils/date.js'

const props = defineProps({
    temple: { type: Object, required: true },
    visited: { type: Boolean, default: false },
    favorite: { type: Boolean, default: false },
})

defineEmits(['toggle-favorite'])

const imageFailed = ref(false)

const location = [props.temple.city, props.temple.state].filter(Boolean).join(', ')
</script>

<template>
    <Link
        :href="route('temples.show', temple.slug)"
        class="block bg-white rounded-lg shadow border border-stone-100 overflow-hidden hover:shadow-md hover:border-teal-200 transition"
    >
        <div class="relative h-40 bg-gradient-to-br from-navy-100 to-teal-100">
            <img
                v-if="temple.photo_url && !imageFailed"
                :src="temple.photo_url"
                :alt="temple.name"
                loading="lazy"
                class="w-full h-full object-cover"
                @error="imageFailed = true"
            >
            <!-- Inside the card's <Link>, so the click has to be stopped. -->
            <button
                type="button"
                class="absolute top-2 end-2 p-1.5 rounded-full bg-white/85 shadow hover:bg-white transition"
                :title="favorite ? 'Remove from favorites' : 'Mark as favorite'"
                :aria-label="favorite ? `Remove ${temple.name} from favorites` : `Mark ${temple.name} as a favorite`"
                :aria-pressed="favorite"
                @click.prevent.stop="$emit('toggle-favorite', temple)"
            >
                <StarIcon
                    :filled="favorite"
                    class="w-4 h-4"
                    :class="favorite ? 'text-gold-600' : 'text-stone-400'"
                />
            </button>
        </div>
        <div class="p-4">
            <div class="flex items-start justify-between gap-2">
                <h3 class="font-semibold text-stone-800">{{ temple.name }}</h3>
                <span
                    v-if="visited"
                    class="shrink-0 px-2 py-0.5 bg-teal-50 text-teal-700 rounded-full text-xs whitespace-nowrap"
                >
                    ✓ Visited
                </span>
            </div>
            <p class="text-sm text-stone-600 mt-1">
                {{ location }}<span v-if="location"> · </span>{{ temple.country }}
            </p>
            <p class="text-xs text-stone-400 mt-2">
                Dedicated {{ formatLocalDate(temple.dedicated_on, { year: 'numeric', month: 'long', day: 'numeric' }) }}
            </p>
        </div>
    </Link>
</template>
