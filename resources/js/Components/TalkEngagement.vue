<script setup>
import { ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import StarRating from '@/Components/StarRating.vue'

const props = defineProps({
    talk: { type: Object, required: true },
    // False for guests and anyone who can't act on the library.
    canEngage: { type: Boolean, default: false }
})

// The date input's ceiling and default — local time, not UTC, so "today"
// matches the user's calendar.
const todayIso = () => {
    const now = new Date()

    return [
        now.getFullYear(),
        String(now.getMonth() + 1).padStart(2, '0'),
        String(now.getDate()).padStart(2, '0')
    ].join('-')
}

const showReadForm = ref(false)
const readForm = useForm({ read_on: todayIso() })

// Every action redirects back, so the card re-renders from fresh server state
// and we never have to reconcile an optimistic guess.
const visitOptions = { preserveScroll: true, preserveState: true }

const rate = (rating) => {
    router.put(route('talks.rate', props.talk.id), { rating }, visitOptions)
}

const clearRating = () => {
    router.delete(route('talks.rating.destroy', props.talk.id), visitOptions)
}

const toggleFavorite = () => {
    router.post(route('talks.favorite', props.talk.id), {}, visitOptions)
}

const submitRead = () => {
    readForm.post(route('talks.reads.store', props.talk.id), {
        preserveScroll: true,
        // Scoped so a bad date on one card doesn't light up every other card.
        errorBag: `talkRead${props.talk.id}`,
        onSuccess: () => {
            showReadForm.value = false
            readForm.read_on = todayIso()
        }
    })
}

const removeRead = (read) => {
    router.delete(route('talks.reads.destroy', [props.talk.id, read.id]), visitOptions)
}
</script>

<template>
    <div class="border-t border-stone-100 px-5 py-3 space-y-2">
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
            <!-- Everyone's average -->
            <div class="flex items-center gap-1.5">
                <StarRating :model-value="talk.average_rating" readonly size="sm" />
                <span v-if="talk.ratings_count" class="text-xs text-stone-500">
                    {{ talk.average_rating.toFixed(1) }}
                    <span class="text-stone-400">({{ talk.ratings_count }})</span>
                </span>
                <span v-else class="text-xs text-stone-400">Not yet rated</span>
            </div>

            <template v-if="canEngage">
                <!-- The user's own rating -->
                <div class="flex items-center gap-1.5">
                    <span class="text-xs text-stone-500">{{ talk.my_rating ? 'Your rating' : 'Rate it' }}</span>
                    <StarRating :model-value="talk.my_rating" size="sm" @update:model-value="rate" />
                    <button
                        v-if="talk.my_rating"
                        type="button"
                        class="text-xs text-stone-400 hover:text-stone-600 underline"
                        @click.prevent.stop="clearRating"
                    >
                        Clear
                    </button>
                </div>

                <button
                    type="button"
                    class="flex items-center gap-1 text-xs transition-colors"
                    :class="talk.is_favorite ? 'text-rose-600 hover:text-rose-700' : 'text-stone-500 hover:text-rose-600'"
                    :title="talk.is_favorite ? 'Remove from favorites' : 'Add to favorites'"
                    @click.prevent.stop="toggleFavorite"
                >
                    <svg
                        class="size-4"
                        viewBox="0 0 24 24"
                        :fill="talk.is_favorite ? 'currentColor' : 'none'"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25s-7.5-4.36-7.5-9.44a4.06 4.06 0 017.5-2.16 4.06 4.06 0 017.5 2.16c0 5.08-7.5 9.44-7.5 9.44z" />
                    </svg>
                    {{ talk.is_favorite ? 'Favorited' : 'Favorite' }}
                </button>

                <button
                    type="button"
                    class="flex items-center gap-1 text-xs text-stone-500 hover:text-amber-700 transition-colors"
                    @click.prevent.stop="showReadForm = ! showReadForm"
                >
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M12 3.75a8.25 8.25 0 100 16.5 8.25 8.25 0 000-16.5z" />
                    </svg>
                    Mark as read
                </button>
            </template>

            <Link
                v-else
                :href="route('login')"
                class="text-xs text-stone-500 hover:text-amber-700 underline"
                @click.stop
            >
                Log in to rate and track this talk
            </Link>
        </div>

        <!-- Read-date entry. A talk can be read more than once, so this stays
             available even after the first date is logged. -->
        <form v-if="canEngage && showReadForm" class="flex flex-wrap items-center gap-2" @submit.prevent="submitRead" @click.stop>
            <label :for="`read-on-${talk.id}`" class="text-xs text-stone-500">Date read</label>
            <input
                :id="`read-on-${talk.id}`"
                v-model="readForm.read_on"
                type="date"
                :max="todayIso()"
                required
                class="rounded-md border-stone-300 text-xs py-1 focus:border-amber-500 focus:ring-amber-500"
            >
            <button
                type="submit"
                class="px-2.5 py-1 bg-amber-600 text-white text-xs rounded-md hover:bg-amber-700 disabled:opacity-50"
                :disabled="readForm.processing"
            >
                Save
            </button>
            <button
                type="button"
                class="text-xs text-stone-400 hover:text-stone-600"
                @click="showReadForm = false"
            >
                Cancel
            </button>
            <span v-if="readForm.errors.read_on" class="text-xs text-red-600">
                {{ readForm.errors.read_on }}
            </span>
        </form>

        <!-- Every date this user has read the talk -->
        <div v-if="talk.reads?.length" class="flex flex-wrap items-center gap-1.5">
            <span class="text-xs text-stone-500">
                Read {{ talk.reads.length === 1 ? 'on' : `${talk.reads.length} times:` }}
            </span>
            <span
                v-for="read in talk.reads"
                :key="read.id"
                class="inline-flex items-center gap-1 px-2 py-0.5 bg-teal-50 text-teal-800 rounded-full text-xs"
            >
                {{ read.label }}
                <button
                    v-if="canEngage"
                    type="button"
                    class="font-bold hover:text-teal-950"
                    :title="`Remove ${read.label}`"
                    @click.prevent.stop="removeRead(read)"
                >
                    &times;
                </button>
            </span>
        </div>
    </div>
</template>
