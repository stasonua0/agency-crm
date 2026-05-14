<script setup>
import HelpPanel from '@/Components/HelpPanel.vue';
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    payouts: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const form = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    type: props.filters.type ?? '',
});

const typeLabels = { salary: 'Зарплата', bonus: 'Бонус', advance: 'Аванс' };
const statusLabels = { planned: 'Запланирована', paid: 'Оплачена' };
const money = (value) => Number(value || 0).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const applyFilters = () => router.get(route('payroll.index'), form, { preserveState: true, replace: true });

const deletePayout = (payout) => {
    if (confirm(`Удалить выплату "${payout.employee_name_snapshot}"?`)) {
        router.delete(route('payroll.destroy', payout.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Зарплаты" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Зарплаты</h1>
                <p class="text-sm text-slate-500">Зарплатные выплаты, бонусы и авансы</p>
            </div>
        </template>

        <HelpPanel
            title="Для чего этот раздел"
            description="Зарплаты фиксируют выплаты сотрудникам: оклад, бонусы и авансы. При статусе “оплачено” CRM создаёт расходную финансовую операцию без распределения по проектам."
            :links="['Получатели выплат', 'Финансовые операции', 'Отчёты']"
        />

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-200 p-5 xl:flex-row xl:items-end xl:justify-between">
                <form class="grid gap-3 md:grid-cols-4 xl:flex-1" @submit.prevent="applyFilters">
                    <input v-model="form.search" type="search" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 md:col-span-2" placeholder="Сотрудник или комментарий" />
                    <select v-model="form.type" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Все типы</option>
                        <option value="salary">Зарплата</option>
                        <option value="bonus">Бонус</option>
                        <option value="advance">Аванс</option>
                    </select>
                    <select v-model="form.status" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Все статусы</option>
                        <option value="planned">Запланированные</option>
                        <option value="paid">Оплаченные</option>
                    </select>
                    <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Применить</button>
                </form>
                <Link :href="route('payroll.create')" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Новая выплата</Link>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Сотрудник</th>
                            <th class="px-5 py-3">Дата</th>
                            <th class="px-5 py-3">Тип</th>
                            <th class="px-5 py-3">Сумма</th>
                            <th class="px-5 py-3">Статус</th>
                            <th class="px-5 py-3">Комментарий</th>
                            <th class="px-5 py-3 text-right">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="payout in payouts.data" :key="payout.id">
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-950">{{ payout.employee_name_snapshot }}</div>
                                <div class="max-w-sm whitespace-pre-line text-slate-500">{{ payout.requisites_snapshot || 'Реквизиты не указаны' }}</div>
                            </td>
                            <td class="px-5 py-4">{{ payout.payout_date }}</td>
                            <td class="px-5 py-4">{{ typeLabels[payout.type] }}</td>
                            <td class="px-5 py-4 font-semibold">{{ money(payout.amount) }} ₽</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="payout.status === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'">{{ statusLabels[payout.status] }}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ payout.comment || '—' }}</td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex justify-end gap-3">
                                    <Link v-if="payout.status !== 'paid'" :href="route('payroll.edit', payout.id)" class="font-semibold text-indigo-700 hover:text-indigo-900">Изменить</Link>
                                    <button v-if="payout.status !== 'paid'" type="button" class="font-semibold text-slate-500 hover:text-red-700" @click="deletePayout(payout)">Удалить</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="payouts.data.length === 0">
                            <td class="px-5 py-8 text-center text-slate-500" colspan="7">Зарплатные выплаты не найдены</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 p-5"><Pagination :links="payouts.links" /></div>
        </section>
    </AuthenticatedLayout>
</template>
