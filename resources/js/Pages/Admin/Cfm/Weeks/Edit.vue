<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    week: Object,
    studyYears: Array,
    specialTopics: Array,
});

const form = useForm({
    study_year_id: props.week.study_year_id,
    week_number: props.week.week_number,
    start_date: props.week.start_date,
    end_date: props.week.end_date,
    title: props.week.title,
    description: props.week.description || '',
    is_special_topic: props.week.is_special_topic,
    special_topic_ids: props.week.special_topics?.map(t => t.id) || [],
});

const submit = () => {
    form.put(route('admin.cfm.weeks.update', props.week.id));
};

const toggleTopic = (topicId) => {
    const index = form.special_topic_ids.indexOf(topicId);
    if (index > -1) {
        form.special_topic_ids.splice(index, 1);
    } else {
        form.special_topic_ids.push(topicId);
    }
};
</script>

<template>
    <AdminLayout :title="`Edit Week ${week.week_number}`">
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
                            Save Changes
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
