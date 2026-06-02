<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
});

const form = useForm({
    first_name: props.user.first_name,
    last_name: props.user.last_name,
    email: props.user.email,
});

const submit = () => {
    form.put(route('admin.users.update', props.user.id));
};
</script>

<template>
    <AdminLayout :title="`Edit ${user.name}`">
        <div class="max-w-2xl">
            <div class="bg-white rounded-lg shadow border border-navy-50 p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel for="first_name" value="First name" />
                        <TextInput
                            id="first_name"
                            v-model="form.first_name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="form.errors.first_name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="last_name" value="Last name" />
                        <TextInput
                            id="last_name"
                            v-model="form.last_name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="form.errors.last_name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <PrimaryButton :disabled="form.processing">
                            Save Changes
                        </PrimaryButton>
                        <Link :href="route('admin.users.show', user.id)">
                            <SecondaryButton type="button">Cancel</SecondaryButton>
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
