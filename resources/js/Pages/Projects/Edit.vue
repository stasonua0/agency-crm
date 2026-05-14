<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ProjectForm from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    clients: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    client_id: props.project.client_id,
    name: props.project.name,
    domain: props.project.domain ?? '',
    status: props.project.status,
    budget: props.project.budget,
    comment: props.project.comment ?? '',
});

const submit = () => form.put(route('projects.update', props.project.id));
</script>

<template>
    <Head title="Редактирование проекта" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Редактирование проекта</h1>
                <p class="text-sm text-slate-500">{{ project.name }}</p>
            </div>
        </template>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <ProjectForm :form="form" :clients="clients" :paid-amount="project.paid_amount" submit-label="Сохранить" @submit="submit" />
        </section>
    </AuthenticatedLayout>
</template>
