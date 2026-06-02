<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
    show_lds_content: true,
    terms: false,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Register" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="first_name" value="First name" />
                <TextInput
                    id="first_name"
                    v-model="form.first_name"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="given-name"
                />
                <InputError class="mt-2" :message="form.errors.first_name" />
            </div>

            <div class="mt-4">
                <InputLabel for="last_name" value="Last name" />
                <TextInput
                    id="last_name"
                    v-model="form.last_name"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autocomplete="family-name"
                />
                <InputError class="mt-2" :message="form.errors.last_name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirm Password" />
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="mt-4">
                <label class="flex items-start">
                    <div class="flex items-center h-5">
                        <Checkbox id="show_lds_content" v-model:checked="form.show_lds_content" name="show_lds_content" />
                    </div>
                    <div class="ms-2 text-sm text-stone-600">
                        Show content for The Church of Jesus Christ of Latter-day Saints, including Come Follow Me study weeks and scripture suggestions from the Book of Mormon, Doctrine and Covenants, and Pearl of Great Price. You can change this anytime in your profile.
                    </div>
                </label>
                <InputError class="mt-2" :message="form.errors.show_lds_content" />
            </div>

            <div v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature" class="mt-4">
                <InputLabel for="terms">
                    <div class="flex items-center">
                        <Checkbox id="terms" v-model:checked="form.terms" name="terms" required />

                        <div class="ms-2 text-sm text-stone-600">
                            I agree to the <a target="_blank" :href="route('terms.show')" class="text-amber-600 hover:text-amber-700">Terms of Service</a> and <a target="_blank" :href="route('policy.show')" class="text-amber-600 hover:text-amber-700">Privacy Policy</a>
                        </div>
                    </div>
                    <InputError class="mt-2" :message="form.errors.terms" />
                </InputLabel>
            </div>

            <div class="flex items-center justify-end mt-6">
                <PrimaryButton class="w-full justify-center" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Register
                </PrimaryButton>
            </div>

            <div class="mt-6 text-center">
                <span class="text-sm text-stone-600">Already have an account?</span>
                <Link :href="route('login')" class="ms-1 text-sm text-amber-600 hover:text-amber-700 font-medium">
                    Log in
                </Link>
            </div>
        </form>
    </AuthenticationCard>
</template>
