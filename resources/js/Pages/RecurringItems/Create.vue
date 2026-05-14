<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RecurringItemForm from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    clients: { type: Array, required: true },
    projects: { type: Array, required: true },
    services: { type: Array, required: true },
    payees: { type: Array, required: true },
});

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    client_id: '',
    project_id: '',
    service_id: '',
    operation_type: 'income',
    amount: '',
    periodicity: 'monthly',
    start_date: today,
    next_payment_date: today,
    payment_method: 'bank_transfer',
    contractor_id: '',
    contractor_name: '',
    contractor_amount: '',
    status: 'active',
    comment: '',
});

const submit = () => form.post(route('recurring-items.store'));
</script>

<template>
    <Head title="Новая регулярная операция" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Новая регулярная операция</h1>
                <p class="text-sm text-slate-500">Шаблон будущих начислений</p>
            </div>
        </template>
        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <RecurringItemForm :form="form" :clients="clients" :projects="projects" :services="services" :payees="payees" submit-label="Создать" @submit="submit" />
        </section>
    </AuthenticatedLayout>
</template>
