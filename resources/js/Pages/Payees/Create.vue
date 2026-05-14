<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PayeeForm from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    type: 'contractor',
    name: '',
    requisites: '',
    phone: '',
    comment: '',
    status: 'active',
});

const submit = () => form.post(route('payees.store'));
</script>

<template>
    <Head title="Новый получатель выплат" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Новый получатель выплат</h1>
                <p class="text-sm text-slate-500">Сотрудник, подрядчик, ПФ или другой получатель</p>
            </div>
        </template>
        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <PayeeForm :form="form" submit-label="Создать" @submit="submit" />
        </section>
    </AuthenticatedLayout>
</template>
