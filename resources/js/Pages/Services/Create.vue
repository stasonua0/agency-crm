<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ServiceForm from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    document_name: '',
    status: 'active',
    comment: '',
});

const submit = () => form.post(route('services.store'));
</script>

<template>
    <Head title="Новая услуга" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Новая услуга</h1>
                <p class="text-sm text-slate-500">Создание услуги</p>
            </div>
        </template>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <ServiceForm :form="form" submit-label="Создать" @submit="submit" />
        </section>
    </AuthenticatedLayout>
</template>
