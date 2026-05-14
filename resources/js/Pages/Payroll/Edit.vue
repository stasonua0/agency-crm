<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PayrollForm from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    payout: { type: Object, required: true },
    employees: { type: Array, required: true },
});

const form = useForm({
    employee_id: props.payout.employee_id,
    amount: props.payout.amount,
    payout_date: props.payout.payout_date,
    type: props.payout.type,
    status: props.payout.status,
    comment: props.payout.comment ?? '',
});

const submit = () => form.put(route('payroll.update', props.payout.id));
</script>

<template>
    <Head title="Редактирование зарплатной выплаты" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Редактирование зарплатной выплаты</h1>
                <p class="text-sm text-slate-500">Оплаченные выплаты не редактируются</p>
            </div>
        </template>
        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <PayrollForm :form="form" :employees="employees" submit-label="Сохранить" @submit="submit" />
        </section>
    </AuthenticatedLayout>
</template>
