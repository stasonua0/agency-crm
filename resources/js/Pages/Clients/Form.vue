<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
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

const lookup = ref({
    loading: false,
    error: '',
    company: null,
});

const clientTypes = [
    { value: 'legal_entity', label: 'Юридическое лицо' },
    { value: 'individual_entrepreneur', label: 'ИП' },
    { value: 'individual', label: 'Физическое лицо' },
];

const statuses = [
    { value: 'active', label: 'Активен' },
    { value: 'archived', label: 'Архив' },
];

const canLookup = computed(() => /^\d{10}(\d{2})?$/.test(String(props.form.inn || '').trim()));

const lookupCompany = async () => {
    lookup.value = { loading: true, error: '', company: null };

    try {
        const response = await fetch(route('clients.lookup-company'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ inn: props.form.inn }),
        });

        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload.message || 'Не удалось получить данные по ИНН.');
        }

        lookup.value = { loading: false, error: '', company: payload.data };
    } catch (error) {
        lookup.value = {
            loading: false,
            error: error.message || 'Не удалось получить данные по ИНН.',
            company: null,
        };
    }
};

const applyLookup = () => {
    if (!lookup.value.company) return;

    const company = lookup.value.company;
    props.form.type = company.type || props.form.type;
    props.form.legal_name = company.legal_name || props.form.legal_name;
    props.form.short_name = company.short_name || props.form.short_name;
    props.form.inn = company.inn || props.form.inn;
    props.form.kpp = company.kpp || '';
    props.form.ogrn = company.ogrn || '';
    props.form.legal_address = company.legal_address || '';
    lookup.value.company = null;
};
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

        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <div class="grid gap-3 lg:grid-cols-[1fr_auto]">
                <div>
                    <InputLabel for="inn" value="ИНН" />
                    <TextInput id="inn" v-model="form.inn" inputmode="numeric" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.inn" />
                    <div v-if="lookup.error" class="mt-2 text-sm text-red-600">{{ lookup.error }}</div>
                </div>
                <div class="flex items-end">
                    <SecondaryButton type="button" :disabled="lookup.loading || !canLookup" @click="lookupCompany">
                        {{ lookup.loading ? 'Ищем...' : 'Заполнить по ИНН' }}
                    </SecondaryButton>
                </div>
            </div>

            <div v-if="lookup.company" class="mt-4 rounded-md border border-indigo-200 bg-white p-4">
                <div class="text-sm font-semibold text-slate-950">Предпросмотр данных</div>
                <dl class="mt-3 grid gap-3 text-sm md:grid-cols-2">
                    <div><dt class="text-slate-500">Название</dt><dd class="font-medium text-slate-950">{{ lookup.company.legal_name }}</dd></div>
                    <div><dt class="text-slate-500">Кратко</dt><dd class="font-medium text-slate-950">{{ lookup.company.short_name }}</dd></div>
                    <div><dt class="text-slate-500">ИНН</dt><dd class="font-medium text-slate-950">{{ lookup.company.inn }}</dd></div>
                    <div><dt class="text-slate-500">КПП</dt><dd class="font-medium text-slate-950">{{ lookup.company.kpp || '-' }}</dd></div>
                    <div><dt class="text-slate-500">ОГРН / ОГРНИП</dt><dd class="font-medium text-slate-950">{{ lookup.company.ogrn || '-' }}</dd></div>
                    <div><dt class="text-slate-500">Источник</dt><dd class="font-medium text-slate-950">{{ lookup.company.source }}</dd></div>
                    <div class="md:col-span-2"><dt class="text-slate-500">Адрес</dt><dd class="font-medium text-slate-950">{{ lookup.company.legal_address || '-' }}</dd></div>
                </dl>
                <div class="mt-4 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="lookup.company = null">Отменить</SecondaryButton>
                    <PrimaryButton type="button" @click="applyLookup">Применить</PrimaryButton>
                </div>
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

        <div class="grid gap-5 lg:grid-cols-2">
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
