<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PlanForm from './Partials/PlanForm.vue'

const props = defineProps({
    plan: Object,
    volumes: Array,
    authors: Array,
    churchCallings: Array,
    conferences: Array
})

const dateOnly = (value) => value ? value.slice(0, 10) : ''

const form = useForm({
    name: props.plan.name,
    type: props.plan.type,
    // Scripture criteria
    volume_id: props.plan.config.volume_id ?? null,
    book_ids: props.plan.config.book_ids ?? [],
    // Talk criteria
    mode: props.plan.config.mode ?? 'author',
    author_id: props.plan.config.author_id ?? null,
    author_calling_id: props.plan.config.author_calling_id ?? null,
    church_calling_id: props.plan.config.church_calling_id ?? null,
    years_back: props.plan.config.years_back ?? null,
    general_conference_id: props.plan.config.general_conference_id ?? null,
    // Schedule
    start_date: dateOnly(props.plan.start_date),
    end_date: dateOnly(props.plan.end_date),
    frequency: props.plan.frequency ?? 'daily'
})

const submit = () => {
    form.transform(data => ({
        ...data,
        start_date: data.start_date || null,
        end_date: data.end_date || null,
        frequency: data.start_date ? data.frequency : null,
        book_ids: data.book_ids.length ? data.book_ids : null
    })).put(route('study-plans.update', props.plan.id))
}
</script>

<template>
    <AppLayout :title="`Edit ${plan.name}`">
        <template #header>
            <h2 class="font-semibold text-xl text-navy leading-tight">
                Edit Study Plan
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
                    submit-label="Save Changes"
                    :cancel-href="route('study-plans.show', plan.id)"
                    @submit="submit"
                >
                    <template #notice>
                        <div class="bg-ivory border border-amber/30 rounded-md px-4 py-3 text-sm text-navy">
                            Saving rebuilds the schedule with your new settings. Readings you've already
                            checked off stay checked as long as they're still part of the plan.
                        </div>
                    </template>
                </PlanForm>
            </div>
        </div>
    </AppLayout>
</template>
