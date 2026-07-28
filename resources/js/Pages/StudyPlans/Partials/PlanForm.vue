<script setup>
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    form: Object,
    volumes: Array,
    authors: Array,
    churchCallings: Array,
    conferences: Array,
    submitLabel: String,
    cancelHref: String
})

defineEmits(['submit'])

const authorSearch = ref('')

const filteredAuthors = computed(() => {
    if (!authorSearch.value) return props.authors
    const term = authorSearch.value.toLowerCase()
    return props.authors.filter(a => a.name.toLowerCase().includes(term))
})

const selectedAuthor = computed(() =>
    props.authors.find(a => a.id === props.form.author_id)
)

const selectedVolume = computed(() =>
    props.volumes.find(v => v.id === props.form.volume_id)
)

const callingPeriodLabel = (calling) => {
    // Slice the year straight off the date string — Date-parsing a date-only
    // value shifts it a day (and possibly a year) in timezones behind UTC.
    const start = calling.start_date ? calling.start_date.slice(0, 4) : '?'
    const end = calling.end_date ? calling.end_date.slice(0, 4) : 'present'
    return `${calling.label} (${start}–${end})`
}

const selectAuthor = (author) => {
    props.form.author_id = author.id
    props.form.author_calling_id = null
    authorSearch.value = ''
}

const toggleBook = (bookId) => {
    const idx = props.form.book_ids.indexOf(bookId)
    if (idx === -1) {
        props.form.book_ids.push(bookId)
    } else {
        props.form.book_ids.splice(idx, 1)
    }
}
</script>

<template>
    <form class="bg-white rounded-lg shadow border border-navy-50 p-6 space-y-8" @submit.prevent="$emit('submit')">
        <!-- Name -->
        <div>
            <label class="block font-medium text-navy mb-1" for="plan-name">Plan name</label>
            <input
                id="plan-name"
                v-model="form.name"
                type="text"
                placeholder="e.g. Book of Mormon in 2026"
                class="w-full rounded-md border-navy-100 focus:border-aqua focus:ring-aqua"
                required
            />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
        </div>

        <!-- Type -->
        <div>
            <span class="block font-medium text-navy mb-2">What do you want to study?</span>
            <div class="grid grid-cols-2 gap-4">
                <button
                    type="button"
                    class="p-4 rounded-lg border-2 text-left transition-colors"
                    :class="form.type === 'scripture' ? 'border-teal bg-aqua-50' : 'border-navy-50 hover:border-navy-100'"
                    @click="form.type = 'scripture'"
                >
                    <span class="block font-semibold text-navy">Scriptures</span>
                    <span class="block text-sm text-teal mt-1">Read a volume of scripture chapter by chapter</span>
                </button>
                <button
                    type="button"
                    class="p-4 rounded-lg border-2 text-left transition-colors"
                    :class="form.type === 'talks' ? 'border-teal bg-aqua-50' : 'border-navy-50 hover:border-navy-100'"
                    @click="form.type = 'talks'"
                >
                    <span class="block font-semibold text-navy">Talks</span>
                    <span class="block text-sm text-teal mt-1">Read talks by an author or calling</span>
                </button>
            </div>
        </div>

        <!-- Scripture criteria -->
        <div v-if="form.type === 'scripture'" class="space-y-4">
            <div>
                <label class="block font-medium text-navy mb-1" for="volume">Volume</label>
                <select
                    id="volume"
                    v-model="form.volume_id"
                    class="w-full rounded-md border-navy-100 focus:border-aqua focus:ring-aqua"
                >
                    <option :value="null" disabled>Choose a volume&hellip;</option>
                    <option v-for="volume in volumes" :key="volume.id" :value="volume.id">
                        {{ volume.name }}
                    </option>
                </select>
                <p v-if="form.errors.volume_id" class="mt-1 text-sm text-red-600">{{ form.errors.volume_id }}</p>
            </div>

            <div v-if="selectedVolume?.books?.length">
                <span class="block font-medium text-navy mb-1">Books</span>
                <p class="text-sm text-teal mb-2">Leave all unchecked to read the whole volume, or pick specific books.</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-1 max-h-48 overflow-y-auto border border-navy-50 rounded-md p-3">
                    <label
                        v-for="book in selectedVolume.books"
                        :key="book.id"
                        class="flex items-center gap-2 text-sm text-navy py-0.5"
                    >
                        <input
                            type="checkbox"
                            :checked="form.book_ids.includes(book.id)"
                            class="rounded border-navy-100 text-teal focus:ring-aqua"
                            @change="toggleBook(book.id)"
                        />
                        {{ book.name }}
                    </label>
                </div>
            </div>
        </div>

        <!-- Talk criteria -->
        <div v-else class="space-y-4">
            <div>
                <span class="block font-medium text-navy mb-2">Which talks?</span>
                <div class="flex gap-6">
                    <label class="flex items-center gap-2 text-navy">
                        <input v-model="form.mode" type="radio" value="author" class="border-navy-100 text-teal focus:ring-aqua" />
                        By a specific author
                    </label>
                    <label class="flex items-center gap-2 text-navy">
                        <input v-model="form.mode" type="radio" value="calling" class="border-navy-100 text-teal focus:ring-aqua" />
                        By calling
                    </label>
                    <label class="flex items-center gap-2 text-navy">
                        <input v-model="form.mode" type="radio" value="conference" class="border-navy-100 text-teal focus:ring-aqua" />
                        A General Conference
                    </label>
                </div>
            </div>

            <template v-if="form.mode === 'author'">
                <div>
                    <label class="block font-medium text-navy mb-1" for="author-search">Author</label>
                    <div v-if="selectedAuthor" class="flex items-center justify-between bg-aqua-50 border border-teal rounded-md px-3 py-2">
                        <span class="font-medium text-navy">{{ selectedAuthor.name }}</span>
                        <button type="button" class="text-sm text-teal hover:text-navy" @click="form.author_id = null; form.author_calling_id = null">
                            Change
                        </button>
                    </div>
                    <template v-else>
                        <input
                            id="author-search"
                            v-model="authorSearch"
                            type="text"
                            placeholder="Search authors&hellip;"
                            class="w-full rounded-md border-navy-100 focus:border-aqua focus:ring-aqua"
                        />
                        <div class="mt-1 max-h-48 overflow-y-auto border border-navy-50 rounded-md divide-y divide-navy-50">
                            <button
                                v-for="author in filteredAuthors"
                                :key="author.id"
                                type="button"
                                class="block w-full text-left px-3 py-2 text-navy hover:bg-ivory"
                                @click="selectAuthor(author)"
                            >
                                {{ author.name }}
                            </button>
                            <p v-if="!filteredAuthors.length" class="px-3 py-2 text-sm text-teal">No matching authors.</p>
                        </div>
                    </template>
                    <p v-if="form.errors.author_id" class="mt-1 text-sm text-red-600">{{ form.errors.author_id }}</p>
                </div>

                <div v-if="selectedAuthor?.callings?.length">
                    <label class="block font-medium text-navy mb-1" for="author-calling">Limit to a calling</label>
                    <p class="text-sm text-teal mb-1">Only include talks given while holding this calling.</p>
                    <select
                        id="author-calling"
                        v-model="form.author_calling_id"
                        class="w-full rounded-md border-navy-100 focus:border-aqua focus:ring-aqua"
                    >
                        <option :value="null">All talks, any calling</option>
                        <option v-for="calling in selectedAuthor.callings" :key="calling.id" :value="calling.id">
                            {{ callingPeriodLabel(calling) }}
                        </option>
                    </select>
                </div>
            </template>

            <template v-else-if="form.mode === 'calling'">
                <div>
                    <label class="block font-medium text-navy mb-1" for="church-calling">Calling</label>
                    <p class="text-sm text-teal mb-1">Include talks from everyone who held this calling when they spoke.</p>
                    <select
                        id="church-calling"
                        v-model="form.church_calling_id"
                        class="w-full rounded-md border-navy-100 focus:border-aqua focus:ring-aqua"
                    >
                        <option :value="null" disabled>Choose a calling&hellip;</option>
                        <option v-for="calling in churchCallings" :key="calling.id" :value="calling.id">
                            {{ calling.label }}
                        </option>
                    </select>
                    <p v-if="form.errors.church_calling_id" class="mt-1 text-sm text-red-600">{{ form.errors.church_calling_id }}</p>
                </div>

                <div>
                    <label class="block font-medium text-navy mb-1" for="years-back">Within the last&hellip;</label>
                    <div class="flex items-center gap-2">
                        <input
                            id="years-back"
                            v-model.number="form.years_back"
                            type="number"
                            min="1"
                            max="100"
                            placeholder="e.g. 2"
                            class="w-24 rounded-md border-navy-100 focus:border-aqua focus:ring-aqua"
                        />
                        <span class="text-navy">years <span class="text-teal text-sm">(leave blank for all time)</span></span>
                    </div>
                </div>
            </template>

            <template v-else>
                <div>
                    <label class="block font-medium text-navy mb-1" for="conference">Conference</label>
                    <p class="text-sm text-teal mb-1">Every talk from the conference, in speaking order.</p>
                    <select
                        id="conference"
                        v-model="form.general_conference_id"
                        class="w-full rounded-md border-navy-100 focus:border-aqua focus:ring-aqua"
                    >
                        <option :value="null" disabled>Choose a conference&hellip;</option>
                        <option v-for="conference in conferences" :key="conference.id" :value="conference.id">
                            {{ conference.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.general_conference_id" class="mt-1 text-sm text-red-600">{{ form.errors.general_conference_id }}</p>
                </div>
            </template>
        </div>

        <!-- Schedule -->
        <div>
            <span class="block font-medium text-navy mb-1">Schedule</span>
            <p class="text-sm text-teal mb-3">
                All fields optional. With a start and end date we spread the reading so you finish on time;
                without an end date you get one reading per session; without dates you get a simple checklist.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-navy mb-1" for="start-date">Start date</label>
                    <input
                        id="start-date"
                        v-model="form.start_date"
                        type="date"
                        class="w-full rounded-md border-navy-100 focus:border-aqua focus:ring-aqua"
                    />
                    <p v-if="form.errors.start_date" class="mt-1 text-sm text-red-600">{{ form.errors.start_date }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-navy mb-1" for="end-date">End date</label>
                    <input
                        id="end-date"
                        v-model="form.end_date"
                        type="date"
                        class="w-full rounded-md border-navy-100 focus:border-aqua focus:ring-aqua"
                    />
                    <p v-if="form.errors.end_date" class="mt-1 text-sm text-red-600">{{ form.errors.end_date }}</p>
                </div>
                <div :class="{ 'opacity-50': !form.start_date }">
                    <label class="block text-sm font-medium text-navy mb-1" for="frequency">Frequency</label>
                    <select
                        id="frequency"
                        v-model="form.frequency"
                        class="w-full rounded-md border-navy-100 focus:border-aqua focus:ring-aqua disabled:bg-navy-50 disabled:cursor-not-allowed"
                        :disabled="!form.start_date"
                        :title="form.start_date ? undefined : 'Set a start date to choose a frequency'"
                    >
                        <option value="daily">Daily</option>
                        <option value="weekdays">Weekdays (Mon&ndash;Fri)</option>
                        <option value="weekly">Weekly</option>
                    </select>
                    <p v-if="!form.start_date" class="mt-1 text-xs text-teal">Set a start date first</p>
                    <p v-if="form.errors.frequency" class="mt-1 text-sm text-red-600">{{ form.errors.frequency }}</p>
                </div>
            </div>
        </div>

        <slot name="notice" />

        <p v-if="form.errors.criteria" class="text-sm text-red-600">{{ form.errors.criteria }}</p>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-2 border-t border-navy-50">
            <Link
                :href="cancelHref"
                class="px-4 py-2 text-teal hover:text-navy transition-colors"
            >
                Cancel
            </Link>
            <button
                type="submit"
                class="px-4 py-2 bg-amber text-white rounded-lg hover:bg-amber-600 disabled:opacity-50"
                :disabled="form.processing"
            >
                {{ submitLabel }}
            </button>
        </div>
    </form>
</template>
