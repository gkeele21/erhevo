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
    volumes: Array,
});

const form = useForm({
    year: new Date().getFullYear() + 1,
    title: '',
    description: '',
    volume_ids: [],
});

const submit = () => {
    form.post(route('admin.cfm.study-years.store'));
};

const toggleVolume = (volumeId) => {
    const index = form.volume_ids.indexOf(volumeId);
    if (index > -1) {
        form.volume_ids.splice(index, 1);
    } else {
        form.volume_ids.push(volumeId);
    }
};
</script>

<template>
    <AdminLayout title="Create Study Year">
        <div class="max-w-2xl">
            <div class="bg-white rounded-lg shadow border border-navy-50 p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel for="year" value="Year" />
                        <TextInput
                            id="year"
                            v-model="form.year"
                            type="number"
                            min="2020"
                            max="2100"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="form.errors.year" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="title" value="Title" />
                        <TextInput
                            id="title"
                            v-model="form.title"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="e.g., Book of Mormon 2028"
                            required
                        />
                        <InputError :message="form.errors.title" class="mt-2" />
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
                        <InputLabel value="Scripture Volumes" />
                        <div class="mt-2 space-y-2">
                            <label
                                v-for="volume in volumes"
                                :key="volume.id"
                                class="flex items-center gap-2 cursor-pointer"
                            >
                                <Checkbox
                                    :checked="form.volume_ids.includes(volume.id)"
                                    @change="toggleVolume(volume.id)"
                                />
                                <span class="text-sm text-navy">{{ volume.name }}</span>
                            </label>
                        </div>
                        <InputError :message="form.errors.volume_ids" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <PrimaryButton :disabled="form.processing">
                            Create Study Year
                        </PrimaryButton>
                        <Link :href="route('admin.cfm.study-years.index')">
                            <SecondaryButton type="button">Cancel</SecondaryButton>
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
