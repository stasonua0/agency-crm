<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    actions: { type: Array, required: true },
});

const form = reactive({
    search: props.filters.search ?? '',
    action: props.filters.action ?? '',
});

const labels = {
    created: 'Создание',
    updated: 'Изменение',
    archived: 'Архивирование',
    paid: 'Оплата',
    cancelled: 'Отмена',
    correction: 'Корректировка',
    invoice_sent: 'Отправка счёта',
    webhook: 'Webhook',
    batch_payout: 'Пакетная выплата',
};

const applyFilters = () => router.get(route('audit.log.index'), form, { preserveState: true, replace: true });
const modelName = (value) => value ? value.split('\\\\').pop() : '-';
</script>

<template>
    <Head title="Журнал аудита" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Журнал аудита</h1>
                <p class="text-sm text-slate-500">Неизменяемая история ключевых действий</p>
            </div>
        </template>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <form class="grid gap-3 border-b border-slate-200 p-5 md:grid-cols-4" @submit.prevent="applyFilters">
                <input v-model="form.search" type="search" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 md:col-span-2" placeholder="Действие, модель, пользователь" />
                <select v-model="form.action" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Все действия</option>
                    <option v-for="action in actions" :key="action" :value="action">{{ labels[action] ?? action }}</option>
                </select>
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Применить</button>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Дата</th>
                            <th class="px-5 py-3">Действие</th>
                            <th class="px-5 py-3">Объект</th>
                            <th class="px-5 py-3">Пользователь</th>
                            <th class="px-5 py-3">Данные</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="log in logs.data" :key="log.id">
                            <td class="whitespace-nowrap px-5 py-4">{{ log.created_at }}</td>
                            <td class="px-5 py-4 font-semibold text-slate-950">{{ labels[log.action] ?? log.action }}</td>
                            <td class="px-5 py-4">{{ modelName(log.auditable_type) }} #{{ log.auditable_id ?? '-' }}</td>
                            <td class="px-5 py-4">{{ log.user?.email ?? 'Система' }}</td>
                            <td class="px-5 py-4 text-xs text-slate-600">
                                <pre class="max-w-xl whitespace-pre-wrap">{{ JSON.stringify(log.metadata ?? {}, null, 2) }}</pre>
                            </td>
                        </tr>
                        <tr v-if="logs.data.length === 0">
                            <td class="px-5 py-8 text-center text-slate-500" colspan="5">Записей аудита нет</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 p-5"><Pagination :links="logs.links" /></div>
        </section>
    </AuthenticatedLayout>
</template>
