<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    settlements: { type: Array, required: true },
    batches: { type: Object, required: true },
});

const selectedIds = ref([]);
const today = new Date().toISOString().slice(0, 10);
const createForm = useForm({
    settlement_ids: [],
    comment: '',
});
const paidForm = useForm({ paid_at: today });
const selectedBatch = ref(null);

const statusLabels = { planned: 'Запланирован', paid: 'Оплачен' };
const money = (value) => Number(value || 0).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const selectedSettlements = computed(() => props.settlements.filter((settlement) => selectedIds.value.includes(settlement.id)));
const selectedPayeeIds = computed(() => [...new Set(selectedSettlements.value.map((settlement) => settlement.payee_id))]);
const selectedTotal = computed(() => selectedSettlements.value.reduce((sum, settlement) => sum + Number(settlement.amount || 0), 0));
const canCreateBatch = computed(() => selectedSettlements.value.length > 0 && selectedPayeeIds.value.length === 1 && !createForm.processing);
const canConfirmPaid = computed(() => Boolean(selectedBatch.value && paidForm.paid_at && !paidForm.processing));

const toggleSettlement = (settlement) => {
    if (selectedIds.value.includes(settlement.id)) {
        selectedIds.value = selectedIds.value.filter((id) => id !== settlement.id);
        return;
    }

    selectedIds.value = [...selectedIds.value, settlement.id];
};

const createBatch = () => {
    createForm.clearErrors();
    createForm.settlement_ids = selectedIds.value;
    createForm.post(route('payouts.store'), {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
            createForm.reset();
        },
    });
};

const openPaidDialog = (batch) => {
    selectedBatch.value = batch;
    paidForm.clearErrors();
    paidForm.paid_at = today;
};

const closePaidDialog = () => {
    selectedBatch.value = null;
    paidForm.clearErrors();
    paidForm.reset();
    paidForm.paid_at = today;
};

const markPaid = () => {
    if (!selectedBatch.value) {
        return;
    }

    paidForm.patch(route('payouts.mark-paid', selectedBatch.value.id), {
        preserveScroll: true,
        onSuccess: closePaidDialog,
    });
};
</script>

<template>
    <Head title="Выплаты" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Выплаты</h1>
                <p class="text-sm text-slate-500">Пакетные выплаты по pending-взаиморасчётам</p>
            </div>
        </template>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Ожидают выплаты</h2>
                        <p class="text-sm text-slate-500">Выберите несколько строк одного получателя</p>
                    </div>
                    <form class="flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="createBatch">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Комментарий</label>
                            <input v-model="createForm.comment" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-80" placeholder="Необязательно" />
                        </div>
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60" :disabled="!canCreateBatch">
                            Создать пакет · {{ money(selectedTotal) }} ₽
                        </button>
                    </form>
                </div>
                <div v-if="createForm.errors.settlement_ids" class="mt-3 text-sm text-red-600">{{ createForm.errors.settlement_ids }}</div>
                <div v-if="selectedSettlements.length > 0 && selectedPayeeIds.length > 1" class="mt-3 text-sm text-amber-700">В одном пакете может быть только один получатель.</div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3"></th>
                            <th class="px-5 py-3">Получатель</th>
                            <th class="px-5 py-3">Источник</th>
                            <th class="px-5 py-3">Сумма</th>
                            <th class="px-5 py-3">Реквизиты snapshot</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="settlement in settlements" :key="settlement.id">
                            <td class="px-5 py-4">
                                <input
                                    type="checkbox"
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    :checked="selectedIds.includes(settlement.id)"
                                    @change="toggleSettlement(settlement)"
                                />
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-950">{{ settlement.payee_name_snapshot }}</div>
                                <div class="text-slate-500">{{ settlement.payee?.name || 'Получатель удалён' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div>{{ settlement.occurrence?.client?.short_name }}</div>
                                <div class="text-slate-500">{{ settlement.occurrence?.project?.name || 'Без проекта' }} · {{ settlement.occurrence?.service?.name }}</div>
                            </td>
                            <td class="px-5 py-4 font-semibold">{{ money(settlement.amount) }} ₽</td>
                            <td class="max-w-md whitespace-pre-line px-5 py-4 text-slate-600">{{ settlement.payee_requisites_snapshot || '—' }}</td>
                        </tr>
                        <tr v-if="settlements.length === 0">
                            <td class="px-5 py-8 text-center text-slate-500" colspan="5">Pending-выплат нет</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5">
                <h2 class="text-base font-semibold text-slate-950">Пакеты выплат</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Пакет</th>
                            <th class="px-5 py-3">Получатель</th>
                            <th class="px-5 py-3">Сумма</th>
                            <th class="px-5 py-3">Статус</th>
                            <th class="px-5 py-3">Комментарий</th>
                            <th class="px-5 py-3 text-right">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="batch in batches.data" :key="batch.id">
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-950">#{{ batch.id }}</div>
                                <div class="text-slate-500">Строк: {{ batch.items_count }}</div>
                            </td>
                            <td class="px-5 py-4">{{ batch.payee_name_snapshot }}</td>
                            <td class="px-5 py-4 font-semibold">{{ money(batch.total_amount) }} ₽</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="batch.status === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'">{{ statusLabels[batch.status] }}</span>
                                <div v-if="batch.paid_at" class="mt-1 text-xs text-slate-500">{{ batch.paid_at }}</div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ batch.comment || '—' }}</td>
                            <td class="px-5 py-4 text-right">
                                <button
                                    v-if="batch.status === 'planned'"
                                    type="button"
                                    class="rounded-md border border-indigo-200 px-3 py-2 text-xs font-semibold text-indigo-700 hover:border-indigo-300 hover:bg-indigo-50"
                                    @click="openPaidDialog(batch)"
                                >
                                    Подтвердить выплату
                                </button>
                            </td>
                        </tr>
                        <tr v-if="batches.data.length === 0">
                            <td class="px-5 py-8 text-center text-slate-500" colspan="6">Пакеты выплат не найдены</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 p-5"><Pagination :links="batches.links" /></div>
        </section>

        <div v-if="selectedBatch" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 px-4">
            <form class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl" @submit.prevent="markPaid">
                <h2 class="text-lg font-semibold text-slate-950">Подтвердить выплату</h2>
                <p class="mt-1 text-sm text-slate-500">
                    #{{ selectedBatch.id }} · {{ selectedBatch.payee_name_snapshot }} · {{ money(selectedBatch.total_amount) }} ₽
                </p>

                <label class="mt-5 block text-sm font-medium text-slate-700" for="paid_at">Дата выплаты</label>
                <input id="paid_at" v-model="paidForm.paid_at" type="date" class="mt-2 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <div v-if="paidForm.errors.paid_at" class="mt-2 text-sm text-red-600">{{ paidForm.errors.paid_at }}</div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="rounded-md border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="closePaidDialog">Отмена</button>
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60" :disabled="!canConfirmPaid">
                        Подтвердить
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
