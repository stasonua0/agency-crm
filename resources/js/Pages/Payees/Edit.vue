<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PayeeForm from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    payee: { type: Object, required: true },
});

const form = useForm({
    type: props.payee.type,
    name: props.payee.name,
    requisites: props.payee.requisites ?? '',
    phone: props.payee.phone ?? '',
    comment: props.payee.comment ?? '',
    status: props.payee.status,
});

const submit = () => form.put(route('payees.update', props.payee.id));
</script>

<template>
    <Head title="Редактирование получателя выплат" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Редактирование получателя выплат</h1>
                <p class="text-sm text-slate-500">Изменения не переписывают уже созданные snapshot-выплаты</p>
            </div>
        </template>
        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <PayeeForm :form="form" submit-label="Сохранить" @submit="submit" />
        </section>
    </AuthenticatedLayout>
</template>
