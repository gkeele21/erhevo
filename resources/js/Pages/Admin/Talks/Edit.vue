<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthorSelect from '@/Components/Story/AuthorSelect.vue';

const props = defineProps({
    talk: Object,
    callings: Array,
    sources: Array,
    talkTypes: Array,
});

const toDate = (v) => (v ? String(v).slice(0, 10) : '');

const form = useForm({
    title: props.talk.title || '',
    speaker_name: props.talk.speaker_name || '',
    author_id: props.talk.author_id || null,
    author_name: props.talk.author?.full_name || '',
    speaker_title: props.talk.speaker_title || '',
    church_calling_id: props.talk.church_calling_id || '',
    talk_date: toDate(props.talk.talk_date),
    url: props.talk.url || '',
    summary: props.talk.summary || '',
    source_id: props.talk.source_id || '',
    talk_type_id: props.talk.talk_type_id || '',
});

const save = () => form.put(route('admin.talks.update', props.talk.id), { preserveScroll: true });

const inputClass = 'rounded-lg border-navy-100 text-sm w-full';
</script>

<template>
    <AdminLayout :title="`Talk: ${talk.title}`">
        <div class="mb-4">
            <Link :href="route('admin.talks.index')" class="text-teal hover:text-navy text-sm">← All talks</Link>
        </div>

        <div class="bg-white rounded-lg shadow border border-navy-50 p-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-navy mb-1">Title</label>
                    <TextInput v-model="form.title" class="w-full" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-navy mb-1">Speaker</label>
                    <TextInput v-model="form.speaker_name" class="w-full" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-navy mb-1">Speaker title / prefix</label>
                    <TextInput v-model="form.speaker_title" class="w-full" placeholder="e.g. Elder, President" />
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-navy mb-1">Linked author <span class="text-navy-200">(entity)</span></label>
                    <AuthorSelect v-model="form.author_id" v-model:name="form.author_name" placeholder="Search authors to link this talk's speaker…" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-navy mb-1">Calling</label>
                    <select v-model="form.church_calling_id" :class="inputClass">
                        <option value="">— None —</option>
                        <option v-for="c in callings" :key="c.id" :value="c.id">{{ c.label }}{{ c.active ? '' : ' · inactive' }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-navy mb-1">Date</label>
                    <input type="date" v-model="form.talk_date" :class="inputClass">
                </div>
                <div>
                    <label class="block text-sm font-medium text-navy mb-1">Source</label>
                    <select v-model="form.source_id" :class="inputClass">
                        <option value="">— None —</option>
                        <option v-for="s in sources" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-navy mb-1">Talk type</label>
                    <select v-model="form.talk_type_id" :class="inputClass">
                        <option value="">— None —</option>
                        <option v-for="t in talkTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-navy mb-1">Source URL</label>
                    <TextInput v-model="form.url" class="w-full" placeholder="https://www.churchofjesuschrist.org/study/general-conference/..." />
                    <a v-if="form.url" :href="form.url" target="_blank" rel="noopener" class="mt-1 inline-block text-xs text-teal hover:text-navy">Open source ↗</a>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-navy mb-1">Summary <span class="text-navy-200">(short blurb only — not the full talk)</span></label>
                    <textarea v-model="form.summary" rows="3" :class="inputClass"></textarea>
                </div>
            </div>
            <div class="mt-4">
                <PrimaryButton @click="save" :disabled="form.processing">Save talk</PrimaryButton>
            </div>
        </div>
    </AdminLayout>
</template>
