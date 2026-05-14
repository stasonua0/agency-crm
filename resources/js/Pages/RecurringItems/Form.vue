<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    clients: { type: Array, required: true },
    projects: { type: Array, required: true },
    services: { type: Array, required: true },
    payees: { type: Array, required: true },
    submitLabel: { type: String, required: true },
});

defineEmits(['submit']);

const filteredProjects = computed(() => props.projects.filter((project) => String(project.client_id) === String(props.form.client_id)));

const operationTypes = [
    { value: 'income', label: 'Доход' },
    { value: 'expense', label: 'Расход' },
];

const periodicities = [
    { value: 'monthly', label: 'Ежемесячно' },
    { value: 'semiannual', label: 'Раз в полгода' },
    { value: 'yearly', label: 'Ежегодно' },
];

const paymentMethods = [
    { value: 'cash', label: 'Наличные' },
    { value: 'bank_transfer', label: 'Безналичный перевод' },
];

const statuses = [
    { value: 'active', label: 'Активна' },
    { value: 'stopped', label: 'Остановлена' },
];
</script>

<template>
    <form class="space-y-6" @submit.prevent="$emit('submit')">
        <div class="grid gap-5 lg:grid-cols-3">
            <div>
                <InputLabel for="client_id" value="Клиент" />
                <select id="client_id" v-model="form.client_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Выберите клиента</option>
                    <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.short_name }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.client_id" />
            </div>
            <div>
                <InputLabel for="project_id" value="Проект" />
                <select id="project_id" v-model="form.project_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Без проекта</option>
                    <option v-for="project in filteredProjects" :key="project.id" :value="project.id">{{ project.name }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.project_id" />
            </div>
            <div>
                <InputLabel for="service_id" value="Услуга" />
                <select id="service_id" v-model="form.service_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Выберите услугу</option>
                    <option v-for="service in services" :key="service.id" :value="service.id">{{ service.name }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.service_id" />
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-4">
            <div>
                <InputLabel for="operation_type" value="Тип операции" />
                <select id="operation_type" v-model="form.operation_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option v-for="type in operationTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.operation_type" />
            </div>
            <div>
                <InputLabel for="amount" value="Сумма" />
                <TextInput id="amount" v-model="form.amount" type="number" min="0.01" step="0.01" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.amount" />
            </div>
            <div>
                <InputLabel for="periodicity" value="Периодичность" />
                <select id="periodicity" v-model="form.periodicity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option v-for="periodicity in periodicities" :key="periodicity.value" :value="periodicity.value">{{ periodicity.label }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.periodicity" />
            </div>
            <div>
                <InputLabel for="payment_method" value="Способ оплаты" />
                <select id="payment_method" v-model="form.payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option v-for="method in paymentMethods" :key="method.value" :value="method.value">{{ method.label }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.payment_method" />
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-3">
            <div>
                <InputLabel for="start_date" value="Дата начала" />
                <TextInput id="start_date" v-model="form.start_date" type="date" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.start_date" />
            </div>
            <div>
                <InputLabel for="next_payment_date" value="Дата следующего платежа" />
                <TextInput id="next_payment_date" v-model="form.next_payment_date" type="date" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.next_payment_date" />
            </div>
            <div>
                <InputLabel for="status" value="Статус" />
                <select id="status" v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.status" />
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <InputLabel for="contractor_id" value="Подрядчик из справочника" />
                <select id="contractor_id" v-model="form.contractor_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Не выбран</option>
                    <option v-for="payee in payees" :key="payee.id" :value="payee.id">{{ payee.name }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.contractor_id" />
            </div>
            <div>
                <InputLabel for="contractor_name" value="Подрядчик вручную" />
                <TextInput id="contractor_name" v-model="form.contractor_name" class="mt-1 block w-full" placeholder="Если нет в справочнике" />
                <InputError class="mt-2" :message="form.errors.contractor_name" />
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <InputLabel for="contractor_amount" value="Сумма подрядчику" />
                <TextInput id="contractor_amount" v-model="form.contractor_amount" type="number" min="0.01" step="0.01" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.contractor_amount" />
            </div>
        </div>

        <div>
            <InputLabel for="comment" value="Комментарий" />
            <textarea id="comment" v-model="form.comment" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            <InputError class="mt-2" :message="form.errors.comment" />
        </div>

        <div class="flex justify-end gap-3">
            <Link :href="route('recurring-items.index')">
                <SecondaryButton type="button">Отмена</SecondaryButton>
            </Link>
            <PrimaryButton :disabled="form.processing">{{ submitLabel }}</PrimaryButton>
        </div>
    </form>
</template>
