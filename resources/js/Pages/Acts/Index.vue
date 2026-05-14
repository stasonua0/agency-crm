<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    acts: { type: Object, required: true },
    occurrences: { type: Array, required: true },
    invoices: { type: Array, required: true },
    filters: { type: Object, default: () => ({}) },
});

const today = new Date().toISOString().slice(0, 10);
const filters = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
});
const form = useForm({
    occurrence_id: '',
    invoice_id: '',
    act_number: '',
    act_date: today,
    status: 'awaiting_signature',
    file_path: '',
    external_id: '',
});

const statusLabels = { awaiting_signature: 'Ожидает подписи', sent_to_edo: 'Отправлен в ЭДО', signed: 'Подписан', cancelled: 'Отменён' };
const money = (value) => Number(value || 0).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const applyFilters = () => router.get(route('acts.index'), filters, { preserveState: true, replace: true });
const submit = () => form.post(route('acts.store'), { preserveScroll: true, onSuccess: () => form.reset() });
</script>

<template>
    <Head title="Акты" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Акты</h1>
                <p class="text-sm text-slate-500">Акты без электронной подписи</p>
            </div>
        </template>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="grid gap-5 border-b border-slate-200 p-5 xl:grid-cols-[1fr_420px]">
                <form class="grid gap-3 md:grid-cols-3" @submit.prevent="applyFilters">
                    <input v-model="filters.search" type="search" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 md:col-span-2" placeholder="Номер или клиент" />
                    <select v-model="filters.status" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Все статусы</option>
                        <option value="awaiting_signature">Ожидает подписи</option>
                        <option value="sent_to_edo">Отправлен в ЭДО</option>
                        <option value="signed">Подписан</option>
                        <option value="cancelled">Отменён</option>
                    </select>
                    <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Применить</button>
                </form>

                <form class="space-y-3 rounded-lg border border-slate-200 p-4" @submit.prevent="submit">
                    <div class="text-sm font-semibold text-slate-950">Создать акт</div>
                    <select v-model="form.occurrence_id" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Выберите начисление</option>
                        <option v-for="occurrence in occurrences" :key="occurrence.id" :value="occurrence.id">
                            {{ occurrence.client?.short_name }} · {{ occurrence.period }} · {{ money(occurrence.amount_snapshot) }} ₽
                        </option>
                    </select>
                    <select v-model="form.invoice_id" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Без счёта</option>
                        <option v-for="invoice in invoices" :key="invoice.id" :value="invoice.id">Счёт {{ invoice.invoice_number }}</option>
                    </select>
                    <div class="grid gap-3 md:grid-cols-2">
                        <input v-model="form.act_number" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Номер акта" />
                        <input v-model="form.act_date" type="date" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div v-if="form.errors.occurrence_id" class="text-sm text-red-600">{{ form.errors.occurrence_id }}</div>
                    <div v-if="form.errors.invoice_id" class="text-sm text-red-600">{{ form.errors.invoice_id }}</div>
                    <div v-if="form.errors.act_number" class="text-sm text-red-600">{{ form.errors.act_number }}</div>
                    <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60" :disabled="form.processing">Создать</button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Акт</th>
                            <th class="px-5 py-3">Клиент</th>
                            <th class="px-5 py-3">Счёт</th>
                            <th class="px-5 py-3">Дата</th>
                            <th class="px-5 py-3">Сумма</th>
                            <th class="px-5 py-3">Статус</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="act in acts.data" :key="act.id">
                            <td class="px-5 py-4 font-semibold text-slate-950">{{ act.act_number }}</td>
                            <td class="px-5 py-4">{{ act.client?.short_name }}</td>
                            <td class="px-5 py-4">{{ act.invoice?.invoice_number || '—' }}</td>
                            <td class="px-5 py-4">{{ act.act_date }}</td>
                            <td class="px-5 py-4 font-semibold">{{ money(act.amount) }} ₽</td>
                            <td class="px-5 py-4">{{ statusLabels[act.status] }}</td>
                        </tr>
                        <tr v-if="acts.data.length === 0">
                            <td class="px-5 py-8 text-center text-slate-500" colspan="6">Акты не найдены</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 p-5"><Pagination :links="acts.links" /></div>
        </section>
    </AuthenticatedLayout>
</template>
