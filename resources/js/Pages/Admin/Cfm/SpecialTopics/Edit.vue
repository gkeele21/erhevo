<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    specialTopic: Object,
});

const form = useForm({
    name: props.specialTopic.name,
    slug: props.specialTopic.slug,
    description: props.specialTopic.description || '',
});

const submit = () => {
    form.put(route('admin.cfm.special-topics.update', props.specialTopic.id));
};
</script>

<template>
    <AdminLayout :title="`Edit ${specialTopic.name}`">
        <div class="max-w-2xl">
            <div class="bg-white rounded-lg shadow border border-navy-50 p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="slug" value="Slug" />
                        <TextInput
                            id="slug"
                            v-model="form.slug"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <p class="mt-1 text-xs text-teal">URL-friendly identifier. Be careful changing this as it may break existing links.</p>
                        <InputError :message="form.errors.slug" class="mt-2" />
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

                    <div class="flex items-center gap-4">
                        <PrimaryButton :disabled="form.processing">
                            Save Changes
                        </PrimaryButton>
                        <Link :href="route('admin.cfm.special-topics.index')">
                            <SecondaryButton type="button">Cancel</SecondaryButton>
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
