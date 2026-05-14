<script setup>
import HelpPanel from '@/Components/HelpPanel.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    metrics: { type: Object, required: true },
});

const money = (value) => Number(value || 0).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const summary = [
    { label: 'Доход за месяц', value: `${money(props.metrics.month_income)} ₽`, detail: 'Фактические поступления' },
    { label: 'Расход за месяц', value: `${money(props.metrics.month_expense)} ₽`, detail: 'Фактические списания' },
    { label: 'Прибыль', value: `${money(props.metrics.profit)} ₽`, detail: 'Cash basis' },
    { label: 'Ожидаемые оплаты', value: `${money(props.metrics.expected_payments)} ₽`, detail: 'Плановые доходные начисления' },
    { label: 'Прогноз на следующий месяц', value: `${money(props.metrics.next_month_forecast)} ₽`, detail: 'Будущие начисления' },
];
</script>

<template>
    <Head title="Дашборд" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Дашборд</h1>
                <p class="text-sm text-slate-500">Финансовое состояние по фактическим операциям</p>
            </div>
        </template>

        <HelpPanel
            title="Для чего этот раздел"
            description="Дашборд даёт быстрый срез финансового состояния: доходы, расходы, прибыль, ожидаемые оплаты и прогноз. Детализация находится в отчётах и финансовых операциях."
            :links="['Отчёты', 'Финансовые операции', 'Начисления']"
        />

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <article
                v-for="item in summary"
                :key="item.label"
                class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div class="text-sm font-medium text-slate-500">{{ item.label }}</div>
                <div class="mt-3 text-2xl font-semibold text-slate-950">{{ item.value }}</div>
                <div class="mt-1 text-sm text-slate-500">{{ item.detail }}</div>
            </article>
        </section>

        <section class="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Отчёты</h2>
                    <p class="mt-1 text-sm text-slate-500">Доходы, расходы и прибыль считаются только по фактическим денежным операциям.</p>
                </div>
                <Link :href="route('reports.index')" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Открыть отчёты
                </Link>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
