<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PlanForm from './Partials/PlanForm.vue'

defineProps({
    volumes: Array,
    authors: Array,
    churchCallings: Array,
    conferences: Array
})

const form = useForm({
    name: '',
    type: 'scripture',
    // Scripture criteria
    volume_id: null,
    book_ids: [],
    // Talk criteria
    mode: 'author',
    author_id: null,
    author_calling_ids: [],
    church_calling_id: null,
    years_back: null,
    general_conference_id: null,
    // Schedule
    start_date: '',
    end_date: '',
    frequency: 'daily'
})

const submit = () => {
    form.transform(data => ({
        ...data,
        start_date: data.start_date || null,
        end_date: data.end_date || null,
        frequency: data.start_date ? data.frequency : null,
        book_ids: data.book_ids.length ? data.book_ids : null
    })).post(route('study-plans.store'))
}
</script>

<template>
    <AppLayout title="New Study Plan">
        <template #header>
            <h2 class="font-semibold text-xl text-navy leading-tight">
                New Study Plan
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <PlanForm
                    :form="form"
                    :volumes="volumes"
                    :authors="authors"
                    :church-callings="churchCallings"
                    :conferences="conferences"
                    submit-label="Create Study Plan"
                    :cancel-href="route('study-plans.index')"
                    @submit="submit"
                />
            </div>
        </div>
    </AppLayout>
</template>
