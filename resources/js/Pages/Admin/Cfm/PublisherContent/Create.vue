<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    publishers: Array,
    studyYears: Array,
    contentTypes: Array,
    selectedPublisherId: [String, Number],
});

const selectedStudyYearId = ref('');

const form = useForm({
    publisher_id: props.selectedPublisherId || '',
    cfm_week_id: '',
    title: '',
    content_type: 'video',
    external_url: '',
    description: '',
    thumbnail_url: '',
    duration_seconds: null,
    is_featured: false,
    redirect_to: props.selectedPublisherId ? 'publisher' : 'index',
});

const availableWeeks = computed(() => {
    if (!selectedStudyYearId.value) return [];
    const year = props.studyYears.find(y => y.id === parseInt(selectedStudyYearId.value));
    return year?.weeks || [];
});

watch(selectedStudyYearId, () => {
    form.cfm_week_id = '';
});

const submit = () => {
    form.post(route('admin.cfm.publisher-content.store'));
};

const parseDuration = (value) => {
    if (!value) {
        form.duration_seconds = null;
        return;
    }

    const parts = value.split(':').map(Number);
    if (parts.length === 2) {
        // MM:SS
        form.duration_seconds = parts[0] * 60 + parts[1];
    } else if (parts.length === 3) {
        // HH:MM:SS
        form.duration_seconds = parts[0] * 3600 + parts[1] * 60 + parts[2];
    }
};

const formattedDuration = computed(() => {
    if (!form.duration_seconds) return '';
    const hours = Math.floor(form.duration_seconds / 3600);
    const minutes = Math.floor((form.duration_seconds % 3600) / 60);
    const seconds = form.duration_seconds % 60;
    if (hours > 0) {
        return `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }
    return `${minutes}:${String(seconds).padStart(2, '0')}`;
});
</script>

<template>
    <AdminLayout title="Add Publisher Content">
        <div class="max-w-2xl">
            <div class="bg-white rounded-lg shadow border border-navy-50 p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel for="publisher_id" value="Publisher" />
                        <select
                            id="publisher_id"
                            v-model="form.publisher_id"
                            class="mt-1 block w-full border-navy-200 focus:border-teal focus:ring-teal rounded-md shadow-sm"
                            required
                        >
                            <option value="">Select a publisher</option>
                            <option v-for="publisher in publishers" :key="publisher.id" :value="publisher.id">
                                {{ publisher.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.publisher_id" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="study_year" value="Study Year" />
                            <select
                                id="study_year"
                                v-model="selectedStudyYearId"
                                class="mt-1 block w-full border-navy-200 focus:border-teal focus:ring-teal rounded-md shadow-sm"
                                required
                            >
                                <option value="">Select a year</option>
                                <option v-for="year in studyYears" :key="year.id" :value="year.id">
                                    {{ year.year }} - {{ year.title }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="cfm_week_id" value="Week" />
                            <select
                                id="cfm_week_id"
                                v-model="form.cfm_week_id"
                                class="mt-1 block w-full border-navy-200 focus:border-teal focus:ring-teal rounded-md shadow-sm"
                                :disabled="!selectedStudyYearId"
                                required
                            >
                                <option value="">Select a week</option>
                                <option v-for="week in availableWeeks" :key="week.id" :value="week.id">
                                    Week {{ week.week_number }}: {{ week.title }}
                                </option>
                            </select>
                            <InputError :message="form.errors.cfm_week_id" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="title" value="Title" />
                        <TextInput
                            id="title"
                            v-model="form.title"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="form.errors.title" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="content_type" value="Content Type" />
                            <select
                                id="content_type"
                                v-model="form.content_type"
                                class="mt-1 block w-full border-navy-200 focus:border-teal focus:ring-teal rounded-md shadow-sm"
                                required
                            >
                                <option v-for="type in contentTypes" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                            <p v-if="form.content_type" class="mt-1 text-xs text-teal">
                                {{ contentTypes.find(t => t.value === form.content_type)?.description }}
                            </p>
                            <InputError :message="form.errors.content_type" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="duration" value="Duration (optional)" />
                            <TextInput
                                id="duration"
                                :model-value="formattedDuration"
                                @update:model-value="parseDuration"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="MM:SS or HH:MM:SS"
                            />
                            <InputError :message="form.errors.duration_seconds" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="external_url" value="External URL" />
                        <TextInput
                            id="external_url"
                            v-model="form.external_url"
                            type="url"
                            class="mt-1 block w-full"
                            placeholder="https://youtube.com/watch?v=..."
                            required
                        />
                        <InputError :message="form.errors.external_url" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="thumbnail_url" value="Thumbnail URL (optional)" />
                        <TextInput
                            id="thumbnail_url"
                            v-model="form.thumbnail_url"
                            type="url"
                            class="mt-1 block w-full"
                            placeholder="https://example.com/thumbnail.jpg"
                        />
                        <InputError :message="form.errors.thumbnail_url" class="mt-2" />
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
                            <Checkbox v-model:checked="form.is_featured" />
                            <span class="text-sm text-navy">Feature this content</span>
                        </label>
                    </div>

                    <div class="flex items-center gap-4">
                        <PrimaryButton :disabled="form.processing">
                            Add Content
                        </PrimaryButton>
                        <Link :href="selectedPublisherId ? route('admin.cfm.publishers.show', selectedPublisherId) : route('admin.cfm.publisher-content.index')">
                            <SecondaryButton type="button">Cancel</SecondaryButton>
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
