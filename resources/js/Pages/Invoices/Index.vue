<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    invoices: { type: Object, required: true },
    occurrences: { type: Array, required: true },
    filters: { type: Object, default: () => ({}) },
});

const today = new Date().toISOString().slice(0, 10);
const filters = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
});
const form = useForm({
    occurrence_id: '',
    invoice_number: '',
    invoice_date: today,
    status: 'draft',
    invoice_url: '',
    invoice_pdf_path: '',
    external_id: '',
});

const statusLabels = { draft: 'Черновик', sent: 'Отправлен', paid: 'Оплачен', cancelled: 'Отменён' };
const money = (value) => Number(value || 0).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const applyFilters = () => router.get(route('invoices.index'), filters, { preserveState: true, replace: true });
const submit = () => form.post(route('invoices.store'), { preserveScroll: true, onSuccess: () => form.reset() });
const sendTochka = (invoice) => router.post(route('invoices.tochka.store', invoice.id), {}, { preserveScroll: true });
const sendEmail = (invoice) => router.post(route('invoices.email.store', invoice.id), {}, { preserveScroll: true });
</script>

<template>
    <Head title="Счета" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Счета</h1>
                <p class="text-sm text-slate-500">Один счёт на одно безналичное начисление</p>
            </div>
        </template>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="grid gap-5 border-b border-slate-200 p-5 xl:grid-cols-[1fr_420px]">
                <form class="grid gap-3 md:grid-cols-3" @submit.prevent="applyFilters">
                    <input v-model="filters.search" type="search" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 md:col-span-2" placeholder="Номер или клиент" />
                    <select v-model="filters.status" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Все статусы</option>
                        <option value="draft">Черновик</option>
                        <option value="sent">Отправлен</option>
                        <option value="paid">Оплачен</option>
                        <option value="cancelled">Отменён</option>
                    </select>
                    <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Применить</button>
                </form>

                <form class="space-y-3 rounded-lg border border-slate-200 p-4" @submit.prevent="submit">
                    <div class="text-sm font-semibold text-slate-950">Создать счёт</div>
                    <select v-model="form.occurrence_id" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Выберите начисление</option>
                        <option v-for="occurrence in occurrences" :key="occurrence.id" :value="occurrence.id">
                            {{ occurrence.client?.short_name }} · {{ occurrence.period }} · {{ money(occurrence.amount_snapshot) }} ₽
                        </option>
                    </select>
                    <div v-if="form.errors.occurrence_id" class="text-sm text-red-600">{{ form.errors.occurrence_id }}</div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <input v-model="form.invoice_number" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Номер счёта" />
                        <input v-model="form.invoice_date" type="date" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div v-if="form.errors.invoice_number" class="text-sm text-red-600">{{ form.errors.invoice_number }}</div>
                    <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60" :disabled="form.processing">Создать</button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Счёт</th>
                            <th class="px-5 py-3">Клиент</th>
                            <th class="px-5 py-3">Услуга</th>
                            <th class="px-5 py-3">Дата</th>
                            <th class="px-5 py-3">Сумма</th>
                            <th class="px-5 py-3">Статус</th>
                            <th class="px-5 py-3">Email</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="invoice in invoices.data" :key="invoice.id">
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-950">{{ invoice.invoice_number }}</div>
                                <div v-if="invoice.external_id" class="text-xs text-slate-500">{{ invoice.external_id }}</div>
                            </td>
                            <td class="px-5 py-4">{{ invoice.client?.short_name }}</td>
                            <td class="px-5 py-4">{{ invoice.occurrence?.service?.name }}</td>
                            <td class="px-5 py-4">{{ invoice.invoice_date }}</td>
                            <td class="px-5 py-4 font-semibold">{{ money(invoice.amount) }} ₽</td>
                            <td class="px-5 py-4">{{ statusLabels[invoice.status] }}</td>
                            <td class="px-5 py-4">
                                <div v-if="invoice.email_sent_at" class="text-xs font-semibold text-emerald-700">Отправлен</div>
                                <div v-if="invoice.email_to" class="text-xs text-slate-500">{{ invoice.email_to }}</div>
                                <div v-if="!invoice.email_sent_at" class="text-xs text-slate-400">Не отправлен</div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button
                                        v-if="!invoice.external_id && !['paid', 'cancelled'].includes(invoice.status)"
                                        type="button"
                                        class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
                                        @click="sendTochka(invoice)"
                                    >
                                        В Точку
                                    </button>
                                    <button
                                        v-if="invoice.client?.invoice_email && !['paid', 'cancelled'].includes(invoice.status)"
                                        type="button"
                                        class="rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        @click="sendEmail(invoice)"
                                    >
                                        Email
                                    </button>
                                    <a v-if="invoice.invoice_url" :href="invoice.invoice_url" target="_blank" class="rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold text-indigo-600 hover:bg-slate-50">PDF</a>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="invoices.data.length === 0">
                            <td class="px-5 py-8 text-center text-slate-500" colspan="8">Счета не найдены</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 p-5"><Pagination :links="invoices.links" /></div>
        </section>
    </AuthenticatedLayout>
</template>
