<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    form: { type: Object, required: true },
    employees: { type: Array, required: true },
    submitLabel: { type: String, required: true },
});

defineEmits(['submit']);

const typeOptions = [
    { value: 'salary', label: 'Зарплата' },
    { value: 'bonus', label: 'Бонус' },
    { value: 'advance', label: 'Аванс' },
];

const statusOptions = [
    { value: 'planned', label: 'Запланирована' },
    { value: 'paid', label: 'Оплачена' },
];
</script>

<template>
    <form class="space-y-6" @submit.prevent="$emit('submit')">
        <div class="grid gap-5 lg:grid-cols-3">
            <div>
                <InputLabel for="employee_id" value="Сотрудник" />
                <select id="employee_id" v-model="form.employee_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Выберите сотрудника</option>
                    <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.name }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.employee_id" />
            </div>
            <div>
                <InputLabel for="amount" value="Сумма" />
                <TextInput id="amount" v-model="form.amount" type="number" min="0.01" step="0.01" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.amount" />
            </div>
            <div>
                <InputLabel for="payout_date" value="Дата" />
                <TextInput id="payout_date" v-model="form.payout_date" type="date" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.payout_date" />
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <InputLabel for="type" value="Тип" />
                <select id="type" v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option v-for="option in typeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.type" />
            </div>
            <div>
                <InputLabel for="status" value="Статус" />
                <select id="status" v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.status" />
            </div>
        </div>

        <div>
            <InputLabel for="comment" value="Комментарий" />
            <textarea id="comment" v-model="form.comment" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            <InputError class="mt-2" :message="form.errors.comment" />
            <InputError class="mt-2" :message="form.errors.payroll" />
        </div>

        <div class="flex justify-end gap-3">
            <Link :href="route('payroll.index')">
                <SecondaryButton type="button">Отмена</SecondaryButton>
            </Link>
            <PrimaryButton :disabled="form.processing">{{ submitLabel }}</PrimaryButton>
        </div>
    </form>
</template>
