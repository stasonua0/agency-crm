<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head :title="title" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">{{ title }}</h1>
                <p class="text-sm text-slate-500">Module placeholder</p>
            </div>
        </template>

        <section class="rounded-lg border border-dashed border-slate-300 bg-white p-8 shadow-sm">
            <div class="max-w-2xl">
                <div class="text-sm font-semibold uppercase tracking-wide text-indigo-700">
                    Stage placeholder
                </div>
                <h2 class="mt-3 text-2xl font-semibold text-slate-950">{{ title }}</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    This section is wired into the CRM navigation. Its data model, validation, permissions,
                    and screens will be implemented in the planned module stage.
                </p>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
