<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    form: {
        type: Object,
        required: true,
    },
    submitLabel: {
        type: String,
        required: true,
    },
});

defineEmits(['submit']);

const clientTypes = [
    { value: 'legal_entity', label: 'Юридическое лицо' },
    { value: 'individual_entrepreneur', label: 'ИП' },
    { value: 'individual', label: 'Физическое лицо' },
];

const statuses = [
    { value: 'active', label: 'Активен' },
    { value: 'archived', label: 'Архив' },
];
</script>

<template>
    <form class="space-y-6" @submit.prevent="$emit('submit')">
        <div class="grid gap-5 lg:grid-cols-3">
            <div>
                <InputLabel for="type" value="Тип клиента" />
                <select id="type" v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option v-for="type in clientTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.type" />
            </div>
            <div>
                <InputLabel for="status" value="Статус" />
                <select id="status" v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.status" />
            </div>
            <div>
                <InputLabel for="invoice_email" value="Email для счетов" />
                <TextInput id="invoice_email" v-model="form.invoice_email" type="email" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.invoice_email" />
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <InputLabel for="legal_name" value="Юридическое название" />
                <TextInput id="legal_name" v-model="form.legal_name" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.legal_name" />
            </div>
            <div>
                <InputLabel for="short_name" value="Краткое название" />
                <TextInput id="short_name" v-model="form.short_name" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.short_name" />
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-3">
            <div>
                <InputLabel for="inn" value="ИНН" />
                <TextInput id="inn" v-model="form.inn" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.inn" />
            </div>
            <div>
                <InputLabel for="kpp" value="КПП" />
                <TextInput id="kpp" v-model="form.kpp" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.kpp" />
            </div>
            <div>
                <InputLabel for="ogrn" value="ОГРН / ОГРНИП" />
                <TextInput id="ogrn" v-model="form.ogrn" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.ogrn" />
            </div>
        </div>

        <div>
            <InputLabel for="legal_address" value="Юридический адрес" />
            <textarea id="legal_address" v-model="form.legal_address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            <InputError class="mt-2" :message="form.errors.legal_address" />
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <InputLabel for="contact_person" value="Контактное лицо" />
                <TextInput id="contact_person" v-model="form.contact_person" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.contact_person" />
            </div>
            <div>
                <InputLabel for="phone" value="Телефон" />
                <TextInput id="phone" v-model="form.phone" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.phone" />
            </div>
        </div>

        <div>
            <InputLabel for="comment" value="Комментарий" />
            <textarea id="comment" v-model="form.comment" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            <InputError class="mt-2" :message="form.errors.comment" />
        </div>

        <div class="flex justify-end gap-3">
            <Link :href="route('clients.index')">
                <SecondaryButton type="button">Отмена</SecondaryButton>
            </Link>
            <PrimaryButton :disabled="form.processing">{{ submitLabel }}</PrimaryButton>
        </div>
    </form>
</template>
