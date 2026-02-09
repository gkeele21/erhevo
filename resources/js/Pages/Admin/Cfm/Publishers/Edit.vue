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
    publisher: Object,
});

const form = useForm({
    name: props.publisher.name,
    slug: props.publisher.slug,
    description: props.publisher.description || '',
    website_url: props.publisher.website_url || '',
    logo_url: props.publisher.logo_url || '',
    social_links: {
        youtube: props.publisher.social_links?.youtube || '',
        instagram: props.publisher.social_links?.instagram || '',
        facebook: props.publisher.social_links?.facebook || '',
        twitter: props.publisher.social_links?.twitter || '',
    },
    is_verified: props.publisher.is_verified,
    is_active: props.publisher.is_active,
});

const submit = () => {
    form.put(route('admin.cfm.publishers.update', props.publisher.id));
};
</script>

<template>
    <AdminLayout :title="`Edit ${publisher.name}`">
        <div class="max-w-2xl">
            <div class="bg-white rounded-lg shadow border border-navy-50 p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="name" value="Publisher Name" />
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
                            />
                            <InputError :message="form.errors.slug" class="mt-2" />
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

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="website_url" value="Website URL (optional)" />
                            <TextInput
                                id="website_url"
                                v-model="form.website_url"
                                type="url"
                                class="mt-1 block w-full"
                                placeholder="https://example.com"
                            />
                            <InputError :message="form.errors.website_url" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="logo_url" value="Logo URL (optional)" />
                            <TextInput
                                id="logo_url"
                                v-model="form.logo_url"
                                type="url"
                                class="mt-1 block w-full"
                                placeholder="https://example.com/logo.png"
                            />
                            <InputError :message="form.errors.logo_url" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Social Links (optional)" />
                        <div class="mt-2 grid grid-cols-2 gap-4">
                            <div>
                                <label for="youtube" class="block text-xs text-teal mb-1">YouTube</label>
                                <TextInput
                                    id="youtube"
                                    v-model="form.social_links.youtube"
                                    type="url"
                                    class="block w-full"
                                    placeholder="https://youtube.com/@channel"
                                />
                            </div>
                            <div>
                                <label for="instagram" class="block text-xs text-teal mb-1">Instagram</label>
                                <TextInput
                                    id="instagram"
                                    v-model="form.social_links.instagram"
                                    type="url"
                                    class="block w-full"
                                    placeholder="https://instagram.com/handle"
                                />
                            </div>
                            <div>
                                <label for="facebook" class="block text-xs text-teal mb-1">Facebook</label>
                                <TextInput
                                    id="facebook"
                                    v-model="form.social_links.facebook"
                                    type="url"
                                    class="block w-full"
                                    placeholder="https://facebook.com/page"
                                />
                            </div>
                            <div>
                                <label for="twitter" class="block text-xs text-teal mb-1">Twitter/X</label>
                                <TextInput
                                    id="twitter"
                                    v-model="form.social_links.twitter"
                                    type="url"
                                    class="block w-full"
                                    placeholder="https://x.com/handle"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <Checkbox v-model:checked="form.is_active" />
                            <span class="text-sm text-navy">Publisher is active</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <Checkbox v-model:checked="form.is_verified" />
                            <span class="text-sm text-navy">Publisher is verified</span>
                        </label>
                    </div>

                    <div class="flex items-center gap-4">
                        <PrimaryButton :disabled="form.processing">
                            Save Changes
                        </PrimaryButton>
                        <Link :href="route('admin.cfm.publishers.show', publisher.id)">
                            <SecondaryButton type="button">Cancel</SecondaryButton>
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
