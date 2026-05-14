<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PayrollForm from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    employees: { type: Array, required: true },
});

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    employee_id: '',
    amount: '',
    payout_date: today,
    type: 'salary',
    status: 'planned',
    comment: '',
});

const submit = () => form.post(route('payroll.store'));
</script>

<template>
    <Head title="Новая зарплатная выплата" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Новая зарплатная выплата</h1>
                <p class="text-sm text-slate-500">Зарплата, бонус или аванс</p>
            </div>
        </template>
        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <PayrollForm :form="form" :employees="employees" submit-label="Создать" @submit="submit" />
        </section>
    </AuthenticatedLayout>
</template>
