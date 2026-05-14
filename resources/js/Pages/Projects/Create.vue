<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ProjectForm from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    clients: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    client_id: '',
    name: '',
    domain: '',
    status: 'active',
    budget: 0,
    comment: '',
});

const submit = () => form.post(route('projects.store'));
</script>

<template>
    <Head title="Новый проект" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Новый проект</h1>
                <p class="text-sm text-slate-500">Создание проекта клиента</p>
            </div>
        </template>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <ProjectForm :form="form" :clients="clients" submit-label="Создать" @submit="submit" />
        </section>
    </AuthenticatedLayout>
</template>
