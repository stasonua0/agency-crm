<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    operations: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const form = reactive({
    search: props.filters.search ?? '',
    type: props.filters.type ?? '',
    source: props.filters.source ?? '',
});

const typeLabels = { income: 'Доход', expense: 'Расход' };
const sourceLabels = { manual: 'Ручная', occurrence: 'Начисление', payout_batch: 'Пакет выплат' };
const money = (value) => Number(value || 0).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const applyFilters = () => router.get(route('financial.operations.index'), form, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Финансовые операции" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Финансовые операции</h1>
                <p class="text-sm text-slate-500">Единый реестр фактических денег</p>
            </div>
        </template>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <form class="grid gap-3 border-b border-slate-200 p-5 md:grid-cols-4" @submit.prevent="applyFilters">
                <input v-model="form.search" type="search" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 md:col-span-2" placeholder="Клиент, проект, услуга, комментарий" />
                <select v-model="form.type" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Все типы</option>
                    <option value="income">Доход</option>
                    <option value="expense">Расход</option>
                </select>
                <select v-model="form.source" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Все источники</option>
                    <option value="manual">Ручная</option>
                    <option value="occurrence">Начисление</option>
                    <option value="payout_batch">Пакет выплат</option>
                </select>
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Применить</button>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Дата</th>
                            <th class="px-5 py-3">Тип</th>
                            <th class="px-5 py-3">Связка</th>
                            <th class="px-5 py-3">Сумма</th>
                            <th class="px-5 py-3">Категория</th>
                            <th class="px-5 py-3">Источник</th>
                            <th class="px-5 py-3">Комментарий</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="operation in operations.data" :key="operation.id">
                            <td class="px-5 py-4">{{ operation.paid_at }}</td>
                            <td class="px-5 py-4">{{ typeLabels[operation.type] }}</td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-950">{{ operation.client?.short_name || '—' }}</div>
                                <div class="text-slate-500">{{ operation.project?.name || 'Без проекта' }} · {{ operation.service?.name || 'Без услуги' }}</div>
                            </td>
                            <td class="px-5 py-4 font-semibold">{{ money(operation.amount) }} ₽</td>
                            <td class="px-5 py-4">{{ operation.category || '—' }}</td>
                            <td class="px-5 py-4">{{ sourceLabels[operation.source] }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ operation.comment || '—' }}</td>
                        </tr>
                        <tr v-if="operations.data.length === 0">
                            <td class="px-5 py-8 text-center text-slate-500" colspan="7">Финансовые операции не найдены</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 p-5"><Pagination :links="operations.links" /></div>
        </section>
    </AuthenticatedLayout>
</template>
