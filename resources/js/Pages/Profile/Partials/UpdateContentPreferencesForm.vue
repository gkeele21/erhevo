<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputLabel from '@/Components/InputLabel.vue';

const page = usePage();

const form = useForm({
    show_lds_content: page.props.userSettings?.show_lds_content ?? true,
});

const updateSettings = () => {
    form.put(route('user-settings.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <FormSection @submitted="updateSettings">
        <template #title>
            Content Preferences
        </template>

        <template #description>
            Customize what types of content and features you see across the app.
        </template>

        <template #form>
            <div class="col-span-6">
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <Checkbox
                            id="show_lds_content"
                            v-model:checked="form.show_lds_content"
                        />
                    </div>
                    <div class="ml-3">
                        <InputLabel for="show_lds_content" class="font-medium text-stone-900 dark:text-stone-100">
                            Enable LDS Content Features
                        </InputLabel>
                        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                            When enabled, you'll see features for linking posts to Come Follow Me study weeks and receive
                            scripture suggestions from the Book of Mormon, Doctrine and Covenants, and Pearl of Great Price
                            in addition to the Bible.
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Saved.
            </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save
            </PrimaryButton>
        </template>
    </FormSection>
</template>
