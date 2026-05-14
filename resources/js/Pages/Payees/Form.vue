<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    form: { type: Object, required: true },
    submitLabel: { type: String, required: true },
});

defineEmits(['submit']);

const typeOptions = [
    { value: 'employee', label: 'Сотрудник' },
    { value: 'contractor', label: 'Подрядчик' },
    { value: 'pf', label: 'ПФ' },
    { value: 'other', label: 'Другое' },
];

const statusOptions = [
    { value: 'active', label: 'Активен' },
    { value: 'archived', label: 'Архив' },
];
</script>

<template>
    <form class="space-y-6" @submit.prevent="$emit('submit')">
        <div class="grid gap-5 lg:grid-cols-3">
            <div>
                <InputLabel for="type" value="Тип" />
                <select id="type" v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option v-for="option in typeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.type" />
            </div>
            <div>
                <InputLabel for="name" value="Имя / название" />
                <TextInput id="name" v-model="form.name" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>
            <div>
                <InputLabel for="status" value="Статус" />
                <select id="status" v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.status" />
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <InputLabel for="phone" value="Телефон" />
                <TextInput id="phone" v-model="form.phone" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.phone" />
            </div>
            <div>
                <InputLabel for="requisites" value="Реквизиты" />
                <textarea id="requisites" v-model="form.requisites" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <InputError class="mt-2" :message="form.errors.requisites" />
            </div>
        </div>

        <div>
            <InputLabel for="comment" value="Комментарий" />
            <textarea id="comment" v-model="form.comment" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            <InputError class="mt-2" :message="form.errors.comment" />
        </div>

        <div class="flex justify-end gap-3">
            <Link :href="route('payees.index')">
                <SecondaryButton type="button">Отмена</SecondaryButton>
            </Link>
            <PrimaryButton :disabled="form.processing">{{ submitLabel }}</PrimaryButton>
        </div>
    </form>
</template>
