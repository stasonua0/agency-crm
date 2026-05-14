<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    occurrences: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const form = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    operation_type: props.filters.operation_type ?? '',
    payment_method: props.filters.payment_method ?? '',
});

const typeLabels = { income: 'Доход', expense: 'Расход' };
const statusLabels = { planned: 'Запланировано', paid: 'Оплачено', cancelled: 'Отменено' };
const methodLabels = { cash: 'Наличные', bank_transfer: 'Безналичный перевод' };
const money = (value) => Number(value || 0).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const applyFilters = () => router.get(route('payment.occurrences.index'), form, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Начисления" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Начисления</h1>
                <p class="text-sm text-slate-500">Snapshot будущих оплат</p>
            </div>
        </template>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <form class="grid gap-3 border-b border-slate-200 p-5 md:grid-cols-4" @submit.prevent="applyFilters">
                <input v-model="form.search" type="search" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 md:col-span-2" placeholder="Клиент, проект, услуга, период" />
                <select v-model="form.operation_type" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Все типы</option>
                    <option value="income">Доход</option>
                    <option value="expense">Расход</option>
                </select>
                <select v-model="form.status" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Все статусы</option>
                    <option value="planned">Запланировано</option>
                    <option value="paid">Оплачено</option>
                    <option value="cancelled">Отменено</option>
                </select>
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Применить</button>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Связка</th>
                            <th class="px-5 py-3">Период</th>
                            <th class="px-5 py-3">Срок</th>
                            <th class="px-5 py-3">Тип</th>
                            <th class="px-5 py-3">Сумма</th>
                            <th class="px-5 py-3">Подрядчик</th>
                            <th class="px-5 py-3">Способ</th>
                            <th class="px-5 py-3">Статус</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="occurrence in occurrences.data" :key="occurrence.id">
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-950">{{ occurrence.client?.short_name }}</div>
                                <div class="text-slate-500">{{ occurrence.project?.name || 'Без проекта' }} · {{ occurrence.service?.name }}</div>
                            </td>
                            <td class="px-5 py-4">{{ occurrence.period }}</td>
                            <td class="px-5 py-4">{{ occurrence.due_date }}</td>
                            <td class="px-5 py-4">{{ typeLabels[occurrence.operation_type] }}</td>
                            <td class="px-5 py-4 font-semibold">{{ money(occurrence.amount_snapshot) }} ₽</td>
                            <td class="px-5 py-4">
                                <div>{{ occurrence.contractor_name_snapshot || '—' }}</div>
                                <div class="text-slate-500">{{ occurrence.contractor_amount_snapshot ? `${money(occurrence.contractor_amount_snapshot)} ₽` : '' }}</div>
                            </td>
                            <td class="px-5 py-4">{{ methodLabels[occurrence.payment_method] }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ statusLabels[occurrence.status] }}</span>
                            </td>
                        </tr>
                        <tr v-if="occurrences.data.length === 0">
                            <td class="px-5 py-8 text-center text-slate-500" colspan="8">Начисления не найдены</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 p-5"><Pagination :links="occurrences.links" /></div>
        </section>
    </AuthenticatedLayout>
</template>
