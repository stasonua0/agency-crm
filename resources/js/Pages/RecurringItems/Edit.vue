<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RecurringItemForm from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    item: { type: Object, required: true },
    clients: { type: Array, required: true },
    projects: { type: Array, required: true },
    services: { type: Array, required: true },
    payees: { type: Array, required: true },
});

const form = useForm({
    client_id: props.item.client_id,
    project_id: props.item.project_id ?? '',
    service_id: props.item.service_id,
    operation_type: props.item.operation_type,
    amount: props.item.amount,
    periodicity: props.item.periodicity,
    start_date: props.item.start_date,
    next_payment_date: props.item.next_payment_date,
    payment_method: props.item.payment_method,
    contractor_id: props.item.contractor_id ?? '',
    contractor_name: props.item.contractor_name ?? '',
    contractor_amount: props.item.contractor_amount ?? '',
    status: props.item.status,
    comment: props.item.comment ?? '',
});

const submit = () => form.put(route('recurring-items.update', props.item.id));
</script>

<template>
    <Head title="Редактирование регулярной операции" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Редактирование регулярной операции</h1>
                <p class="text-sm text-slate-500">Изменения не переписывают старые начисления</p>
            </div>
        </template>
        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <RecurringItemForm :form="form" :clients="clients" :projects="projects" :services="services" :payees="payees" submit-label="Сохранить" @submit="submit" />
        </section>
    </AuthenticatedLayout>
</template>
