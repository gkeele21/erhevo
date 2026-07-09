<script setup>
import { ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    author: Object,
    callings: Array,
});

const toDate = (v) => (v ? String(v).slice(0, 10) : '');

// --- Author profile form ---
const form = useForm({
    first_name: props.author.first_name || '',
    middle_name: props.author.middle_name || '',
    last_name: props.author.last_name || '',
    suffix: props.author.suffix || '',
    display_name: props.author.display_name || '',
    church_calling_id: props.author.church_calling_id || '',
    calling_started_at: toDate(props.author.calling_started_at),
    user_id: props.author.user_id || '',
    notes: props.author.notes || '',
});

const save = () => form.put(route('admin.authors.update', props.author.id), { preserveScroll: true });

// --- Calling history ---
// Editable local copy of each stint.
const stints = ref(props.author.callings.map((c) => ({
    id: c.id,
    church_calling_id: c.church_calling_id,
    start_date: toDate(c.start_date),
    end_date: toDate(c.end_date),
    label: c.calling?.full_title || c.calling?.name || '—',
})));

const saveStint = (s) => {
    router.put(route('admin.authors.callings.update', [props.author.id, s.id]), {
        church_calling_id: s.church_calling_id,
        start_date: s.start_date || null,
        end_date: s.end_date || null,
    }, { preserveScroll: true });
};

const deleteStint = (s) => {
    if (confirm('Remove this calling from the author\'s history?')) {
        router.delete(route('admin.authors.callings.destroy', [props.author.id, s.id]), { preserveScroll: true });
    }
};

const newStint = useForm({ church_calling_id: '', start_date: '', end_date: '' });
const addStint = () => {
    newStint.post(route('admin.authors.callings.store', props.author.id), {
        preserveScroll: true,
        onSuccess: () => newStint.reset(),
    });
};

// Calling picker options, filterable to active-only. Always keeps a currently
// selected calling visible even if it's inactive/historical.
const activeOnly = ref(true);
const optionsFor = (currentId) => {
    const base = activeOnly.value ? props.callings.filter((c) => c.active) : props.callings;
    const id = Number(currentId);
    if (id && !base.some((c) => c.id === id)) {
        const cur = props.callings.find((c) => c.id === id);
        if (cur) return [cur, ...base];
    }
    return base;
};

const inputClass = 'rounded-lg border-navy-100 text-sm w-full';
</script>

<template>
    <AdminLayout :title="`Author: ${author.full_name}`">
        <div class="mb-4">
            <Link :href="route('admin.authors.index')" class="text-teal hover:text-navy text-sm">← All authors</Link>
        </div>

        <!-- Profile -->
        <div class="bg-white rounded-lg shadow border border-navy-50 p-6 mb-6">
            <h3 class="text-lg font-semibold text-navy mb-4">Profile</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-navy mb-1">First name</label>
                    <TextInput v-model="form.first_name" class="w-full" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-navy mb-1">Middle name</label>
                    <TextInput v-model="form.middle_name" class="w-full" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-navy mb-1">Last name</label>
                    <TextInput v-model="form.last_name" class="w-full" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-navy mb-1">Suffix</label>
                    <TextInput v-model="form.suffix" class="w-full" placeholder="Jr., III, ..." />
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-navy mb-1">Display name override (for mononyms / irregular names)</label>
                    <TextInput v-model="form.display_name" class="w-full" placeholder="e.g. Rumi — leave blank to use the parts above" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-navy mb-1">Current (primary) calling</label>
                    <select v-model="form.church_calling_id" :class="inputClass">
                        <option value="">— None —</option>
                        <option v-for="c in optionsFor(form.church_calling_id)" :key="c.id" :value="c.id">{{ c.label }}{{ c.active ? '' : ' · inactive' }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-navy mb-1">Current calling started</label>
                    <input type="date" v-model="form.calling_started_at" :class="inputClass">
                </div>
                <div>
                    <label class="block text-sm font-medium text-navy mb-1">Linked user ID (optional)</label>
                    <TextInput v-model="form.user_id" class="w-full" placeholder="users.id, blank to unlink" />
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-navy mb-1">Notes</label>
                    <textarea v-model="form.notes" rows="2" :class="inputClass"></textarea>
                </div>
            </div>
            <div class="mt-4">
                <PrimaryButton @click="save" :disabled="form.processing">Save profile</PrimaryButton>
            </div>
        </div>

        <!-- Calling history -->
        <div class="bg-white rounded-lg shadow border border-navy-50 p-6">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-1">
                <h3 class="text-lg font-semibold text-navy">Calling history</h3>
                <label class="flex items-center gap-2 text-sm text-navy">
                    <input type="checkbox" v-model="activeOnly">
                    Active callings only
                </label>
            </div>
            <p class="text-sm text-navy-300 mb-4">Leave the end date blank for a calling the author currently holds. Multiple blank-end callings = concurrent callings.</p>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-navy-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-sm font-semibold text-navy">Calling</th>
                            <th class="px-3 py-2 text-left text-sm font-semibold text-navy">Start</th>
                            <th class="px-3 py-2 text-left text-sm font-semibold text-navy">End</th>
                            <th class="px-3 py-2 text-left text-sm font-semibold text-navy">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-navy-50">
                        <tr v-for="s in stints" :key="s.id">
                            <td class="px-3 py-2">
                                <select v-model="s.church_calling_id" :class="inputClass">
                                    <option v-for="c in optionsFor(s.church_calling_id)" :key="c.id" :value="c.id">{{ c.label }}{{ c.active ? '' : ' · inactive' }}</option>
                                </select>
                            </td>
                            <td class="px-3 py-2"><input type="date" v-model="s.start_date" :class="inputClass"></td>
                            <td class="px-3 py-2">
                                <input type="date" v-model="s.end_date" :class="inputClass">
                                <span v-if="!s.end_date" class="text-xs text-teal">current</span>
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex gap-3">
                                    <button @click="saveStint(s)" class="text-teal hover:text-navy text-sm">Save</button>
                                    <button @click="deleteStint(s)" class="text-red-500 hover:text-red-700 text-sm">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!stints.length">
                            <td colspan="4" class="px-3 py-6 text-center text-navy-300">No calling history yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Add a calling -->
            <div class="mt-5 border-t border-navy-50 pt-5">
                <h4 class="text-sm font-semibold text-navy mb-3">Add a calling</h4>
                <div class="grid gap-3 sm:grid-cols-4 items-end">
                    <div class="sm:col-span-2">
                        <label class="block text-xs text-navy-300 mb-1">Calling</label>
                        <select v-model="newStint.church_calling_id" :class="inputClass">
                            <option value="">Select a calling…</option>
                            <option v-for="c in optionsFor(newStint.church_calling_id)" :key="c.id" :value="c.id">{{ c.label }}{{ c.active ? '' : ' · inactive' }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-navy-300 mb-1">Start</label>
                        <input type="date" v-model="newStint.start_date" :class="inputClass">
                    </div>
                    <div>
                        <label class="block text-xs text-navy-300 mb-1">End <span class="text-navy-200">(blank = current)</span></label>
                        <input type="date" v-model="newStint.end_date" :class="inputClass">
                    </div>
                </div>
                <div class="mt-3">
                    <PrimaryButton @click="addStint" :disabled="!newStint.church_calling_id || newStint.processing">Add calling</PrimaryButton>
                    <span v-if="newStint.errors.church_calling_id" class="ml-3 text-sm text-red-500">{{ newStint.errors.church_calling_id }}</span>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
