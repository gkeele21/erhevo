<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    studyYears: Array,
    specialTopics: Array,
    volumes: Array,
});

const form = useForm({
    study_year_id: '',
    week_number: 1,
    start_date: '',
    end_date: '',
    title: '',
    description: '',
    is_special_topic: false,
    special_topic_ids: [],
    chapter_ids: [],
});

const expandedBooks = ref([]);

const selectedStudyYear = computed(() => {
    return props.studyYears.find(y => y.id === form.study_year_id);
});

const filteredVolumes = computed(() => {
    if (!selectedStudyYear.value) return [];
    const volumeIds = selectedStudyYear.value.volumes.map(v => v.id);
    return props.volumes.filter(v => volumeIds.includes(v.id));
});

const submit = () => {
    form.post(route('admin.cfm.weeks.store'));
};

const toggleTopic = (topicId) => {
    const index = form.special_topic_ids.indexOf(topicId);
    if (index > -1) {
        form.special_topic_ids.splice(index, 1);
    } else {
        form.special_topic_ids.push(topicId);
    }
};

const toggleBook = (bookId) => {
    const index = expandedBooks.value.indexOf(bookId);
    if (index > -1) {
        expandedBooks.value.splice(index, 1);
    } else {
        expandedBooks.value.push(bookId);
    }
};

const toggleChapter = (chapterId) => {
    const index = form.chapter_ids.indexOf(chapterId);
    if (index > -1) {
        form.chapter_ids.splice(index, 1);
    } else {
        form.chapter_ids.push(chapterId);
    }
};

const isBookExpanded = (bookId) => expandedBooks.value.includes(bookId);

const getSelectedChaptersForBook = (book) => {
    return book.chapters.filter(c => form.chapter_ids.includes(c.id));
};

const selectChapterRange = (book, startChapter, endChapter) => {
    book.chapters.forEach(chapter => {
        if (chapter.chapter_number >= startChapter && chapter.chapter_number <= endChapter) {
            if (!form.chapter_ids.includes(chapter.id)) {
                form.chapter_ids.push(chapter.id);
            }
        }
    });
};
</script>

<template>
    <AdminLayout title="Create Week">
        <div class="max-w-2xl">
            <div class="bg-white rounded-lg shadow border border-navy-50 p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel for="study_year_id" value="Study Year" />
                        <select
                            id="study_year_id"
                            v-model="form.study_year_id"
                            class="mt-1 block w-full border-navy-200 focus:border-teal focus:ring-teal rounded-md shadow-sm"
                            required
                        >
                            <option value="">Select a year</option>
                            <option v-for="year in studyYears" :key="year.id" :value="year.id">
                                {{ year.year }} - {{ year.title }}
                            </option>
                        </select>
                        <InputError :message="form.errors.study_year_id" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="week_number" value="Week Number" />
                            <TextInput
                                id="week_number"
                                v-model="form.week_number"
                                type="number"
                                min="1"
                                max="53"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.week_number" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="title" value="Title" />
                            <TextInput
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="e.g., 1 Nephi 1-5"
                                required
                            />
                            <InputError :message="form.errors.title" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="start_date" value="Start Date" />
                            <TextInput
                                id="start_date"
                                v-model="form.start_date"
                                type="date"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.start_date" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="end_date" value="End Date" />
                            <TextInput
                                id="end_date"
                                v-model="form.end_date"
                                type="date"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.end_date" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="description" value="Description (optional)" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            class="mt-1 block w-full border-navy-200 focus:border-teal focus:ring-teal rounded-md shadow-sm"
                            rows="3"
                        ></textarea>
                        <InputError :message="form.errors.description" class="mt-2" />
                    </div>

                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <Checkbox v-model:checked="form.is_special_topic" />
                            <span class="text-sm text-navy">This is a special topic week (e.g., Christmas, Easter)</span>
                        </label>
                    </div>

                    <!-- Chapter Selection (only for regular weeks) -->
                    <div v-if="!form.is_special_topic && form.study_year_id">
                        <InputLabel value="Scripture Chapters" />
                        <p class="text-sm text-teal mb-2">Select the chapters covered in this week's study.</p>

                        <div v-if="filteredVolumes.length === 0" class="text-sm text-gray-500 italic">
                            No volumes assigned to this study year.
                        </div>

                        <div v-else class="mt-2 border border-navy-100 rounded-md max-h-96 overflow-y-auto">
                            <div v-for="volume in filteredVolumes" :key="volume.id" class="border-b border-navy-100 last:border-b-0">
                                <div class="px-3 py-2 bg-navy-50 font-medium text-navy text-sm">
                                    {{ volume.name }}
                                </div>
                                <div v-for="book in volume.books" :key="book.id" class="border-t border-navy-50">
                                    <button
                                        type="button"
                                        @click="toggleBook(book.id)"
                                        class="w-full px-3 py-2 flex items-center justify-between hover:bg-gray-50 text-left"
                                    >
                                        <span class="text-sm text-navy">
                                            {{ book.name }}
                                            <span v-if="getSelectedChaptersForBook(book).length > 0" class="text-teal ml-1">
                                                ({{ getSelectedChaptersForBook(book).length }} selected)
                                            </span>
                                        </span>
                                        <span class="text-gray-400">{{ isBookExpanded(book.id) ? '−' : '+' }}</span>
                                    </button>
                                    <div v-if="isBookExpanded(book.id)" class="px-3 py-2 bg-gray-50">
                                        <div class="flex flex-wrap gap-1">
                                            <label
                                                v-for="chapter in book.chapters"
                                                :key="chapter.id"
                                                class="inline-flex items-center justify-center w-10 h-8 text-sm rounded cursor-pointer transition-colors"
                                                :class="form.chapter_ids.includes(chapter.id)
                                                    ? 'bg-teal text-white'
                                                    : 'bg-white border border-navy-200 text-navy hover:border-teal'"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :checked="form.chapter_ids.includes(chapter.id)"
                                                    @change="toggleChapter(chapter.id)"
                                                    class="sr-only"
                                                />
                                                {{ chapter.chapter_number }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="form.chapter_ids.length > 0" class="mt-2 text-sm text-teal">
                            {{ form.chapter_ids.length }} chapter(s) selected
                        </div>
                        <InputError :message="form.errors.chapter_ids" class="mt-2" />
                    </div>

                    <div v-if="form.is_special_topic">
                        <InputLabel value="Special Topics" />
                        <div class="mt-2 space-y-2">
                            <label
                                v-for="topic in specialTopics"
                                :key="topic.id"
                                class="flex items-center gap-2 cursor-pointer"
                            >
                                <Checkbox
                                    :checked="form.special_topic_ids.includes(topic.id)"
                                    @change="toggleTopic(topic.id)"
                                />
                                <span class="text-sm text-navy">{{ topic.name }}</span>
                            </label>
                        </div>
                        <InputError :message="form.errors.special_topic_ids" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <PrimaryButton :disabled="form.processing">
                            Create Week
                        </PrimaryButton>
                        <Link :href="route('admin.cfm.weeks.index')">
                            <SecondaryButton type="button">Cancel</SecondaryButton>
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
