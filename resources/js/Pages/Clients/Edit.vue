<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ClientForm from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    client: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    type: props.client.type,
    legal_name: props.client.legal_name,
    short_name: props.client.short_name,
    inn: props.client.inn ?? '',
    kpp: props.client.kpp ?? '',
    ogrn: props.client.ogrn ?? '',
    legal_address: props.client.legal_address ?? '',
    invoice_email: props.client.invoice_email ?? '',
    contact_person: props.client.contact_person ?? '',
    phone: props.client.phone ?? '',
    comment: props.client.comment ?? '',
    status: props.client.status,
});

const submit = () => form.put(route('clients.update', props.client.id));
</script>

<template>
    <Head title="Редактирование клиента" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Редактирование клиента</h1>
                <p class="text-sm text-slate-500">{{ client.short_name }}</p>
            </div>
        </template>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <ClientForm :form="form" submit-label="Сохранить" @submit="submit" />
        </section>
    </AuthenticatedLayout>
</template>
