<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    clients: {
        type: Array,
        required: true,
    },
    submitLabel: {
        type: String,
        required: true,
    },
    paidAmount: {
        type: [Number, String],
        default: 0,
    },
});

defineEmits(['submit']);

const statuses = [
    { value: 'active', label: 'Активен' },
    { value: 'paused', label: 'Пауза' },
    { value: 'archived', label: 'Архив' },
];

const remainingAmount = computed(() => {
    const budget = Number(props.form.budget || 0);
    const paid = Number(props.paidAmount || 0);

    return Math.max(0, budget - paid).toLocaleString('ru-RU', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
});
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
                <InputLabel for="status" value="Статус" />
                <select id="status" v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.status" />
            </div>
            <div>
                <InputLabel for="budget" value="Бюджет" />
                <TextInput id="budget" v-model="form.budget" type="number" min="0" step="0.01" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.budget" />
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <InputLabel for="name" value="Название" />
                <TextInput id="name" v-model="form.name" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>
            <div>
                <InputLabel for="domain" value="Домен" />
                <TextInput id="domain" v-model="form.domain" class="mt-1 block w-full" placeholder="example.com" />
                <InputError class="mt-2" :message="form.errors.domain" />
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Оплачено</div>
                <div class="mt-2 text-xl font-semibold text-slate-950">{{ Number(paidAmount || 0).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} ₽</div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Остаток</div>
                <div class="mt-2 text-xl font-semibold text-slate-950">{{ remainingAmount }} ₽</div>
            </div>
        </div>

        <div>
            <InputLabel for="comment" value="Комментарий" />
            <textarea id="comment" v-model="form.comment" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            <InputError class="mt-2" :message="form.errors.comment" />
        </div>

        <div class="flex justify-end gap-3">
            <Link :href="route('projects.index')">
                <SecondaryButton type="button">Отмена</SecondaryButton>
            </Link>
            <PrimaryButton :disabled="form.processing">{{ submitLabel }}</PrimaryButton>
        </div>
    </form>
</template>
