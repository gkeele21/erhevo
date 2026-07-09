<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    callings: Array,
    organizations: Array,
});

const rows = ref(props.callings.map((c) => ({
    id: c.id,
    church_organization_id: c.church_organization_id || '',
    name: c.name || '',
    prefix: c.prefix || '',
    is_active: !!c.is_active,
    authors_count: c.authors_count,
})));

const saveRow = (r) => {
    router.put(route('admin.church-callings.update', r.id), {
        church_organization_id: r.church_organization_id || null,
        name: r.name || null,
        prefix: r.prefix || null,
        is_active: r.is_active,
    }, { preserveScroll: true });
};

const deleteRow = (r) => {
    if (confirm(`Delete this calling? Any author-calling history rows referencing it are removed (${r.authors_count} author(s) currently hold it as their primary calling).`)) {
        router.delete(route('admin.church-callings.destroy', r.id), { preserveScroll: true });
    }
};

const form = useForm({ church_organization_id: '', name: '', prefix: '', is_active: true });
const add = () => form.post(route('admin.church-callings.store'), { preserveScroll: true, onSuccess: () => form.reset() });

const inputClass = 'rounded-lg border-navy-100 text-sm w-full';
</script>

<template>
    <AdminLayout title="Church Callings">
        <!-- Add -->
        <div class="bg-white rounded-lg shadow border border-navy-50 p-4 mb-6">
            <h3 class="text-sm font-semibold text-navy mb-3">Add a calling</h3>
            <div class="grid gap-3 sm:grid-cols-4 items-end">
                <div>
                    <label class="block text-xs text-navy-300 mb-1">Organization</label>
                    <select v-model="form.church_organization_id" :class="inputClass">
                        <option value="">— None —</option>
                        <option v-for="o in organizations" :key="o.id" :value="o.id">{{ o.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-navy-300 mb-1">Name</label>
                    <TextInput v-model="form.name" class="w-full" placeholder="e.g. Apostle" />
                </div>
                <div>
                    <label class="block text-xs text-navy-300 mb-1">Prefix</label>
                    <TextInput v-model="form.prefix" class="w-full" placeholder="e.g. Elder" />
                </div>
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-1 text-sm text-navy"><input type="checkbox" v-model="form.is_active"> Active</label>
                    <PrimaryButton @click="add" :disabled="form.processing">Add</PrimaryButton>
                </div>
            </div>
        </div>

        <!-- List -->
        <div class="bg-white rounded-lg shadow border border-navy-50 overflow-x-auto">
            <table class="w-full">
                <thead class="bg-navy-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-sm font-semibold text-navy">Organization</th>
                        <th class="px-3 py-2 text-left text-sm font-semibold text-navy">Prefix</th>
                        <th class="px-3 py-2 text-left text-sm font-semibold text-navy">Name</th>
                        <th class="px-3 py-2 text-left text-sm font-semibold text-navy">Active</th>
                        <th class="px-3 py-2 text-left text-sm font-semibold text-navy">Authors</th>
                        <th class="px-3 py-2 text-left text-sm font-semibold text-navy">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-navy-50">
                    <tr v-for="r in rows" :key="r.id">
                        <td class="px-3 py-2 w-1/3">
                            <select v-model="r.church_organization_id" :class="inputClass">
                                <option value="">— None —</option>
                                <option v-for="o in organizations" :key="o.id" :value="o.id">{{ o.name }}</option>
                            </select>
                        </td>
                        <td class="px-3 py-2"><TextInput v-model="r.prefix" class="w-full" /></td>
                        <td class="px-3 py-2"><TextInput v-model="r.name" class="w-full" /></td>
                        <td class="px-3 py-2"><input type="checkbox" v-model="r.is_active"></td>
                        <td class="px-3 py-2 text-sm">{{ r.authors_count }}</td>
                        <td class="px-3 py-2">
                            <div class="flex gap-3">
                                <button @click="saveRow(r)" class="text-teal hover:text-navy text-sm">Save</button>
                                <button @click="deleteRow(r)" class="text-red-500 hover:text-red-700 text-sm">Delete</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
