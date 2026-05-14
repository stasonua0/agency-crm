<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ClientForm from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    type: 'legal_entity',
    legal_name: '',
    short_name: '',
    inn: '',
    kpp: '',
    ogrn: '',
    legal_address: '',
    invoice_email: '',
    contact_person: '',
    phone: '',
    comment: '',
    status: 'active',
});

const submit = () => form.post(route('clients.store'));
</script>

<template>
    <Head title="Новый клиент" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Новый клиент</h1>
                <p class="text-sm text-slate-500">Создание карточки клиента</p>
            </div>
        </template>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <ClientForm :form="form" submit-label="Создать" @submit="submit" />
        </section>
    </AuthenticatedLayout>
</template>
