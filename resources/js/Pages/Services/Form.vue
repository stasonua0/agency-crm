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

const statuses = [
    { value: 'active', label: 'Активна' },
    { value: 'archived', label: 'Архив' },
];
</script>

<template>
    <form class="space-y-6" @submit.prevent="$emit('submit')">
        <div class="grid gap-5 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <InputLabel for="name" value="Название" />
                <TextInput id="name" v-model="form.name" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>
            <div>
                <InputLabel for="status" value="Статус" />
                <select id="status" v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.status" />
            </div>
        </div>

        <div>
            <InputLabel for="document_name" value="Наименование для документов" />
            <TextInput id="document_name" v-model="form.document_name" class="mt-1 block w-full" />
            <InputError class="mt-2" :message="form.errors.document_name" />
        </div>

        <div>
            <InputLabel for="comment" value="Комментарий" />
            <textarea id="comment" v-model="form.comment" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            <InputError class="mt-2" :message="form.errors.comment" />
        </div>

        <div class="flex justify-end gap-3">
            <Link :href="route('services.index')">
                <SecondaryButton type="button">Отмена</SecondaryButton>
            </Link>
            <PrimaryButton :disabled="form.processing">{{ submitLabel }}</PrimaryButton>
        </div>
    </form>
</template>
