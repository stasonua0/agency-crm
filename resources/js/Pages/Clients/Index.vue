<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    clients: {
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
    type: props.filters.type ?? '',
});

const clientTypeLabels = {
    legal_entity: 'Юрлицо',
    individual_entrepreneur: 'ИП',
    individual: 'Физлицо',
};

const statusLabels = {
    active: 'Активен',
    archived: 'Архив',
};

const applyFilters = () => {
    router.get(route('clients.index'), form, {
        preserveState: true,
        replace: true,
    });
};

const archiveClient = (client) => {
    if (confirm(`Архивировать клиента "${client.short_name}"?`)) {
        router.delete(route('clients.destroy', client.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Клиенты" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Клиенты</h1>
                <p class="text-sm text-slate-500">Справочник клиентов</p>
            </div>
        </template>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-200 p-5 xl:flex-row xl:items-end xl:justify-between">
                <form class="grid gap-3 md:grid-cols-4 xl:flex-1" @submit.prevent="applyFilters">
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Поиск</label>
                        <input v-model="form.search" type="search" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Название, ИНН, контакт, телефон" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Тип</label>
                        <select v-model="form.type" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Все</option>
                            <option value="legal_entity">Юрлицо</option>
                            <option value="individual_entrepreneur">ИП</option>
                            <option value="individual">Физлицо</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Статус</label>
                        <select v-model="form.status" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Все</option>
                            <option value="active">Активные</option>
                            <option value="archived">Архив</option>
                        </select>
                    </div>
                    <div class="md:col-span-4">
                        <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Применить</button>
                    </div>
                </form>

                <Link :href="route('clients.create')" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Новый клиент
                </Link>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Клиент</th>
                            <th class="px-5 py-3">Реквизиты</th>
                            <th class="px-5 py-3">Контакты</th>
                            <th class="px-5 py-3">Статус</th>
                            <th class="px-5 py-3 text-right">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="client in clients.data" :key="client.id">
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-950">{{ client.short_name }}</div>
                                <div class="mt-1 text-slate-500">{{ client.legal_name }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ clientTypeLabels[client.type] }}</div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                <div>ИНН: {{ client.inn || '—' }}</div>
                                <div>КПП: {{ client.kpp || '—' }}</div>
                                <div>ОГРН: {{ client.ogrn || '—' }}</div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                <div>{{ client.contact_person || '—' }}</div>
                                <div>{{ client.phone || '—' }}</div>
                                <div>{{ client.invoice_email || '—' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="client.status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'">
                                    {{ statusLabels[client.status] }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex justify-end gap-3">
                                    <Link :href="route('clients.edit', client.id)" class="font-semibold text-indigo-700 hover:text-indigo-900">Изменить</Link>
                                    <button v-if="client.status !== 'archived'" type="button" class="font-semibold text-slate-500 hover:text-red-700" @click="archiveClient(client)">В архив</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="clients.data.length === 0">
                            <td class="px-5 py-8 text-center text-slate-500" colspan="5">Клиенты не найдены</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 p-5">
                <Pagination :links="clients.links" />
            </div>
        </section>
    </AuthenticatedLayout>
</template>
