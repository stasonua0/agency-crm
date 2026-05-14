<script setup>
import HelpPanel from '@/Components/HelpPanel.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    filters: { type: Object, required: true },
    report: { type: Object, required: true },
    services: { type: Array, required: true },
    clients: { type: Array, required: true },
});

const form = reactive({
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    service_id: props.filters.service_id ?? '',
    client_id: props.filters.client_id ?? '',
});

const money = (value) => Number(value || 0).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const applyFilters = () => router.get(route('reports.index'), form, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Отчёты" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Отчёты</h1>
                <p class="text-sm text-slate-500">Cash basis: фактические доходы минус фактические расходы</p>
            </div>
        </template>

        <HelpPanel
            title="Для чего этот раздел"
            description="Отчёты показывают доходы, расходы и прибыль по cash basis: считаются только фактические финансовые операции, а не плановые начисления."
            :links="['Финансовые операции', 'Клиенты', 'Услуги', 'Проекты']"
        />

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <form class="grid gap-3 md:grid-cols-5" @submit.prevent="applyFilters">
                <input v-model="form.date_from" type="date" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <input v-model="form.date_to" type="date" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <select v-model="form.service_id" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Все услуги</option>
                    <option v-for="service in services" :key="service.id" :value="service.id">{{ service.name }}</option>
                </select>
                <select v-model="form.client_id" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Все клиенты</option>
                    <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.short_name }}</option>
                </select>
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Применить</button>
            </form>
        </section>

        <section class="mt-6 grid gap-4 md:grid-cols-3">
            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">Доходы</div>
                <div class="mt-3 text-2xl font-semibold text-slate-950">{{ money(report.summary.income) }} ₽</div>
            </article>
            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">Расходы</div>
                <div class="mt-3 text-2xl font-semibold text-slate-950">{{ money(report.summary.expense) }} ₽</div>
            </article>
            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">Прибыль студии</div>
                <div class="mt-3 text-2xl font-semibold text-slate-950">{{ money(report.summary.profit) }} ₽</div>
            </article>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 p-5">
                    <h2 class="text-base font-semibold text-slate-950">Доходы по услугам</h2>
                </div>
                <div class="divide-y divide-slate-200">
                    <div v-for="row in report.income_by_services" :key="row.label" class="flex items-center justify-between gap-4 p-5 text-sm">
                        <span class="font-medium text-slate-700">{{ row.label }}</span>
                        <span class="font-semibold text-slate-950">{{ money(row.amount) }} ₽</span>
                    </div>
                    <div v-if="report.income_by_services.length === 0" class="p-5 text-sm text-slate-500">Доходов за период нет</div>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 p-5">
                    <h2 class="text-base font-semibold text-slate-950">Доходы по месяцам</h2>
                </div>
                <div class="divide-y divide-slate-200">
                    <div v-for="row in report.income_by_months" :key="row.label" class="flex items-center justify-between gap-4 p-5 text-sm">
                        <span class="font-medium text-slate-700">{{ row.label }}</span>
                        <span class="font-semibold text-slate-950">{{ money(row.amount) }} ₽</span>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white shadow-sm xl:col-span-2">
                <div class="border-b border-slate-200 p-5">
                    <h2 class="text-base font-semibold text-slate-950">Расходы</h2>
                </div>
                <div class="grid divide-y divide-slate-200 md:grid-cols-4 md:divide-x md:divide-y-0">
                    <div v-for="row in report.expenses" :key="row.label" class="p-5">
                        <div class="text-sm font-medium text-slate-500">{{ row.label }}</div>
                        <div class="mt-3 text-xl font-semibold text-slate-950">{{ money(row.amount) }} ₽</div>
                    </div>
                    <div v-if="report.expenses.length === 0" class="p-5 text-sm text-slate-500 md:col-span-4">Расходов за период нет</div>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
