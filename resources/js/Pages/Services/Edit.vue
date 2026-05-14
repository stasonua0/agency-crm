<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ServiceForm from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    service: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    name: props.service.name,
    document_name: props.service.document_name,
    status: props.service.status,
    comment: props.service.comment ?? '',
});

const submit = () => form.put(route('services.update', props.service.id));
</script>

<template>
    <Head title="Редактирование услуги" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Редактирование услуги</h1>
                <p class="text-sm text-slate-500">{{ service.name }}</p>
            </div>
        </template>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <ServiceForm :form="form" submit-label="Сохранить" @submit="submit" />
        </section>
    </AuthenticatedLayout>
</template>
