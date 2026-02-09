<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const form = useForm({
    name: '',
    slug: '',
    description: '',
});

const generateSlug = () => {
    form.slug = form.name
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim();
};

const submit = () => {
    form.post(route('admin.cfm.special-topics.store'));
};
</script>

<template>
    <AdminLayout title="Create Special Topic">
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
                            placeholder="e.g., Easter"
                            @input="generateSlug"
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
                            placeholder="e.g., easter"
                            required
                        />
                        <p class="mt-1 text-xs text-teal">URL-friendly identifier. Auto-generated from name.</p>
                        <InputError :message="form.errors.slug" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Description (optional)" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            class="mt-1 block w-full border-navy-200 focus:border-teal focus:ring-teal rounded-md shadow-sm"
                            rows="3"
                            placeholder="Brief description of this special topic..."
                        ></textarea>
                        <InputError :message="form.errors.description" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <PrimaryButton :disabled="form.processing">
                            Create Special Topic
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
