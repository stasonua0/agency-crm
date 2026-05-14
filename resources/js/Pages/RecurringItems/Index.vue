<script setup>
import HelpPanel from '@/Components/HelpPanel.vue';
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    items: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const form = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    operation_type: props.filters.operation_type ?? '',
    payment_method: props.filters.payment_method ?? '',
});

const typeLabels = { income: 'Доход', expense: 'Расход' };
const statusLabels = { active: 'Активна', stopped: 'Остановлена' };
const methodLabels = { cash: 'Наличные', bank_transfer: 'Безналичный перевод' };
const periodicityLabels = { monthly: 'Ежемесячно', semiannual: 'Раз в полгода', yearly: 'Ежегодно' };
const money = (value) => Number(value || 0).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const applyFilters = () => router.get(route('recurring-items.index'), form, { preserveState: true, replace: true });

const stopItem = (item) => {
    if (confirm('Остановить регулярную операцию?')) {
        router.delete(route('recurring-items.destroy', item.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Регулярные операции" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Регулярные операции</h1>
                <p class="text-sm text-slate-500">Шаблоны доходов и расходов</p>
            </div>
        </template>

        <HelpPanel
            title="Для чего этот раздел"
            description="Регулярные операции — шаблоны будущих начислений: абонплаты, расходы, подрядчики и периодичность. Ежедневная команда генерации создаёт из них начисления со snapshot-данными."
            :links="['Клиенты', 'Проекты', 'Услуги', 'Начисления', 'Подрядчики']"
        />

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-200 p-5 xl:flex-row xl:items-end xl:justify-between">
                <form class="grid gap-3 md:grid-cols-4 xl:flex-1" @submit.prevent="applyFilters">
                    <input v-model="form.search" type="search" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 md:col-span-2" placeholder="Клиент, проект, услуга, подрядчик" />
                    <select v-model="form.operation_type" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Все типы</option>
                        <option value="income">Доход</option>
                        <option value="expense">Расход</option>
                    </select>
                    <select v-model="form.status" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Все статусы</option>
                        <option value="active">Активные</option>
                        <option value="stopped">Остановленные</option>
                    </select>
                    <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Применить</button>
                </form>
                <Link :href="route('recurring-items.create')" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Новая операция</Link>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Связка</th>
                            <th class="px-5 py-3">Тип</th>
                            <th class="px-5 py-3">Сумма</th>
                            <th class="px-5 py-3">Периодичность</th>
                            <th class="px-5 py-3">Следующий платёж</th>
                            <th class="px-5 py-3">Подрядчик</th>
                            <th class="px-5 py-3">Статус</th>
                            <th class="px-5 py-3 text-right">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="item in items.data" :key="item.id">
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-950">{{ item.client?.short_name }}</div>
                                <div class="text-slate-500">{{ item.project?.name || 'Без проекта' }} · {{ item.service?.name }}</div>
                            </td>
                            <td class="px-5 py-4">{{ typeLabels[item.operation_type] }}</td>
                            <td class="px-5 py-4 font-semibold">{{ money(item.amount) }} ₽</td>
                            <td class="px-5 py-4">{{ periodicityLabels[item.periodicity] }}</td>
                            <td class="px-5 py-4">{{ item.next_payment_date }}</td>
                            <td class="px-5 py-4">
                                <div>{{ item.contractor?.name || item.contractor_name || '—' }}</div>
                                <div class="text-slate-500">{{ item.contractor_amount ? `${money(item.contractor_amount)} ₽` : '' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="item.status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'">{{ statusLabels[item.status] }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex justify-end gap-3">
                                    <Link :href="route('recurring-items.edit', item.id)" class="font-semibold text-indigo-700 hover:text-indigo-900">Изменить</Link>
                                    <button v-if="item.status !== 'stopped'" type="button" class="font-semibold text-slate-500 hover:text-red-700" @click="stopItem(item)">Остановить</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="items.data.length === 0">
                            <td class="px-5 py-8 text-center text-slate-500" colspan="8">Регулярные операции не найдены</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 p-5"><Pagination :links="items.links" /></div>
        </section>
    </AuthenticatedLayout>
</template>
