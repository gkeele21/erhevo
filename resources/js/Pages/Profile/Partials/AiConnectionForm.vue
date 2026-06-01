<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const page = usePage();

const providers = computed(() => page.props.ai?.providers ?? []);
const connected = computed(() => page.props.ai?.connected ?? false);
const currentProviderKey = computed(() => page.props.ai?.provider ?? null);

const currentProviderLabel = computed(() => {
    const match = providers.value.find(p => p.key === currentProviderKey.value);
    return match?.label ?? currentProviderKey.value;
});

const form = useForm({
    ai_provider: currentProviderKey.value ?? (providers.value[0]?.key ?? 'openai'),
    ai_api_key: '',
});

const selectedHint = computed(() => {
    const match = providers.value.find(p => p.key === form.ai_provider);
    return match?.key_hint ?? '';
});

const connect = () => {
    form.put(route('ai-connection.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset('ai_api_key'),
    });
};

const disconnectForm = useForm({});

const disconnect = () => {
    disconnectForm.delete(route('ai-connection.destroy'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <FormSection @submitted="connect">
        <template #title>
            AI Account
        </template>

        <template #description>
            AI features (writing prompts, tag &amp; scripture suggestions, privacy checks, insights, and more) use
            your own AI account. Connect a provider with your own API key to enable them. Your key is stored
            encrypted and is only ever used for your requests.
        </template>

        <template #form>
            <!-- Connected state -->
            <div v-if="connected" class="col-span-6">
                <div class="flex items-center gap-3 p-4 rounded-lg bg-green-50 border border-green-200">
                    <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-green-800">
                            Connected to {{ currentProviderLabel }}
                        </p>
                        <p class="text-xs text-green-700">
                            AI features are enabled. Reconnect below to change provider or replace your key.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Provider select -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="ai_provider" value="AI provider" />
                <select
                    id="ai_provider"
                    v-model="form.ai_provider"
                    class="mt-1 block w-full border-stone-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm dark:bg-stone-900 dark:border-stone-700 dark:text-stone-300"
                >
                    <option v-for="provider in providers" :key="provider.key" :value="provider.key">
                        {{ provider.label }}
                    </option>
                </select>
                <InputError :message="form.errors.ai_provider" class="mt-2" />
            </div>

            <!-- API key -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="ai_api_key" :value="connected ? 'New API key' : 'API key'" />
                <TextInput
                    id="ai_api_key"
                    v-model="form.ai_api_key"
                    type="password"
                    autocomplete="off"
                    class="mt-1 block w-full"
                    placeholder="Paste your API key"
                />
                <p v-if="selectedHint" class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                    {{ selectedHint }}
                </p>
                <InputError :message="form.errors.ai_api_key" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Connected.
            </ActionMessage>

            <DangerButton
                v-if="connected"
                type="button"
                class="me-3"
                :class="{ 'opacity-25': disconnectForm.processing }"
                :disabled="disconnectForm.processing"
                @click="disconnect"
            >
                Disconnect
            </DangerButton>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                {{ connected ? 'Reconnect' : 'Connect' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
