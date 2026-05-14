<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    services: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const form = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
});

const statusLabels = {
    active: 'Активна',
    archived: 'Архив',
};

const applyFilters = () => {
    router.get(route('services.index'), form, {
        preserveState: true,
        replace: true,
    });
};

const archiveService = (service) => {
    if (confirm(`Архивировать услугу "${service.name}"?`)) {
        router.delete(route('services.destroy', service.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Услуги" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Услуги</h1>
                <p class="text-sm text-slate-500">Справочник услуг</p>
            </div>
        </template>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-200 p-5 xl:flex-row xl:items-end xl:justify-between">
                <form class="grid gap-3 md:grid-cols-3 xl:flex-1" @submit.prevent="applyFilters">
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Поиск</label>
                        <input v-model="form.search" type="search" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Название, документ, комментарий" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Статус</label>
                        <select v-model="form.status" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Все</option>
                            <option value="active">Активные</option>
                            <option value="archived">Архив</option>
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Применить</button>
                    </div>
                </form>

                <Link :href="route('services.create')" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Новая услуга
                </Link>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Название</th>
                            <th class="px-5 py-3">Для документов</th>
                            <th class="px-5 py-3">Статус</th>
                            <th class="px-5 py-3 text-right">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="service in services.data" :key="service.id">
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-950">{{ service.name }}</div>
                                <div class="mt-1 text-slate-500">{{ service.comment || '—' }}</div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ service.document_name }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="service.status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'">
                                    {{ statusLabels[service.status] }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex justify-end gap-3">
                                    <Link :href="route('services.edit', service.id)" class="font-semibold text-indigo-700 hover:text-indigo-900">Изменить</Link>
                                    <button v-if="service.status !== 'archived'" type="button" class="font-semibold text-slate-500 hover:text-red-700" @click="archiveService(service)">В архив</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="services.data.length === 0">
                            <td class="px-5 py-8 text-center text-slate-500" colspan="4">Услуги не найдены</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 p-5">
                <Pagination :links="services.links" />
            </div>
        </section>
    </AuthenticatedLayout>
</template>
