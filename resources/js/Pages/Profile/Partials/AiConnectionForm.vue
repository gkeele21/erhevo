<script setup>
import { computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const page = usePage();

const providers = computed(() => page.props.ai?.providers ?? []);
const connectedKeys = computed(() => page.props.ai?.connections ?? []);
const defaultProviderKey = computed(() => page.props.ai?.provider ?? null);

const connections = computed(() =>
    connectedKeys.value.map(key => ({
        key,
        label: providers.value.find(p => p.key === key)?.label ?? key,
        isDefault: key === defaultProviderKey.value,
    }))
);

const form = useForm({
    ai_provider: providers.value.find(p => !connectedKeys.value.includes(p.key))?.key
        ?? (providers.value[0]?.key ?? 'openai'),
    ai_api_key: '',
});

const selectedProvider = computed(() =>
    providers.value.find(p => p.key === form.ai_provider) ?? null
);

const selectedHint = computed(() => selectedProvider.value?.key_hint ?? '');
const replacingKey = computed(() => connectedKeys.value.includes(form.ai_provider));

const connect = () => {
    form.put(route('ai-connection.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset('ai_api_key'),
    });
};

const makeDefault = (provider) => {
    router.put(route('ai-connection.default'), { ai_provider: provider }, {
        preserveScroll: true,
    });
};

const disconnect = (provider) => {
    router.delete(route('ai-connection.destroy', provider), {
        preserveScroll: true,
    });
};
</script>

<template>
    <FormSection @submitted="connect">
        <template #title>
            AI Accounts
        </template>

        <template #description>
            AI features (writing prompts, tag &amp; scripture suggestions, privacy checks, insights, video
            transcription, and more) use your own AI accounts. Connect one or more providers with your own API
            keys; your <strong>default</strong> handles general features, and capabilities a provider lacks
            (like audio transcription) automatically use another of your connections. Keys are verified with a
            tiny test request when you connect, stored encrypted, and only ever used for your requests.
        </template>

        <template #form>
            <!-- Connected accounts -->
            <div v-if="connections.length" class="col-span-6 space-y-2">
                <div
                    v-for="conn in connections"
                    :key="conn.key"
                    class="flex items-center justify-between gap-3 p-4 rounded-lg bg-green-50 border border-green-200"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm font-medium text-green-800 truncate">
                            {{ conn.label }}
                            <span
                                v-if="conn.isDefault"
                                class="ml-1 rounded bg-green-600 px-1.5 py-0.5 text-xs font-semibold text-white"
                            >Default</span>
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-3 text-sm">
                        <button
                            v-if="!conn.isDefault"
                            type="button"
                            class="font-medium text-green-700 hover:text-green-900 underline"
                            @click="makeDefault(conn.key)"
                        >
                            Make default
                        </button>
                        <button
                            type="button"
                            class="font-medium text-red-600 hover:text-red-800 underline"
                            @click="disconnect(conn.key)"
                        >
                            Disconnect
                        </button>
                    </div>
                </div>
            </div>

            <!-- Provider select -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="ai_provider" :value="connections.length ? 'Add another provider' : 'AI provider'" />
                <select
                    id="ai_provider"
                    v-model="form.ai_provider"
                    class="mt-1 block w-full border-stone-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm dark:bg-stone-900 dark:border-stone-700 dark:text-stone-300"
                >
                    <option v-for="provider in providers" :key="provider.key" :value="provider.key">
                        {{ provider.label }}{{ connectedKeys.includes(provider.key) ? ' (connected)' : '' }}
                    </option>
                </select>
                <InputError :message="form.errors.ai_provider" class="mt-2" />
            </div>

            <!-- Where to get a key for the selected provider -->
            <div
                v-if="selectedProvider?.console_url"
                class="col-span-6 sm:col-span-4 rounded-lg border border-stone-200 bg-stone-50 p-3 text-sm text-stone-600 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-400"
            >
                <p>
                    Create your API key at
                    <a
                        :href="selectedProvider.console_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium text-amber-700 underline hover:text-amber-900 dark:text-amber-500"
                    >{{ selectedProvider.console_host }} ↗</a><template v-if="selectedProvider.console_path">,
                    then go to <span class="font-medium">{{ selectedProvider.console_path }}</span></template>.
                </p>
                <p v-if="selectedProvider.note" class="mt-1 text-xs text-stone-500 dark:text-stone-500">
                    {{ selectedProvider.note }}
                </p>
            </div>

            <!-- API key -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="ai_api_key" :value="replacingKey ? 'New API key' : 'API key'" />
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

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                {{ replacingKey ? 'Replace key' : 'Connect' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
