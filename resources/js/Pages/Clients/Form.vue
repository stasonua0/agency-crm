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

const ai = ref({
    text: '',
    loading: false,
    error: '',
    preview: null,
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

const fieldLabels = {
    type: 'Тип клиента',
    legal_name: 'Юридическое название',
    short_name: 'Краткое название',
    inn: 'ИНН',
    kpp: 'КПП',
    ogrn: 'ОГРН / ОГРНИП',
    legal_address: 'Юридический адрес',
    invoice_email: 'Email для счетов',
    contact_person: 'Контактное лицо',
    phone: 'Телефон',
    comment: 'Комментарий',
    status: 'Статус',
};

const valueLabels = {
    legal_entity: 'Юридическое лицо',
    individual_entrepreneur: 'ИП',
    individual: 'Физическое лицо',
    active: 'Активен',
    archived: 'Архив',
};

const canLookup = computed(() => /^\d{10}(\d{2})?$/.test(String(props.form.inn || '').trim()));
const canParseAi = computed(() => ai.value.text.trim().length >= 10 && !ai.value.loading);
const aiFields = computed(() => Object.entries(ai.value.preview?.fields ?? {}));

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const postJson = async (url, body) => {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(body),
    });

    const payload = await response.json();

    if (!response.ok) {
        throw new Error(payload.message || 'Запрос не выполнен.');
    }

    return payload;
};

const lookupCompany = async () => {
    lookup.value = { loading: true, error: '', company: null };

    try {
        const payload = await postJson(route('clients.lookup-company'), { inn: props.form.inn });
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

    applyFields(lookup.value.company);
    lookup.value.company = null;
};

const parseAiText = async () => {
    ai.value = { ...ai.value, loading: true, error: '', preview: null };

    try {
        const payload = await postJson(route('clients.ai-autofill'), { text: ai.value.text });
        ai.value = { ...ai.value, loading: false, error: '', preview: payload.data };
    } catch (error) {
        ai.value = {
            ...ai.value,
            loading: false,
            error: error.message || 'Не удалось разобрать текст.',
            preview: null,
        };
    }
};

const applyAiPreview = () => {
    if (!ai.value.preview?.fields) return;

    applyFields(ai.value.preview.fields);
    ai.value.preview = null;
};

const applyFields = (fields) => {
    Object.entries(fields).forEach(([key, value]) => {
        if (Object.prototype.hasOwnProperty.call(props.form, key) && value !== null && value !== undefined) {
            props.form[key] = value;
        }
    });
};

const displayValue = (value) => valueLabels[value] ?? value ?? '-';
</script>

<template>
    <form class="space-y-6" @submit.prevent="$emit('submit')">
        <div class="rounded-lg border border-indigo-100 bg-indigo-50/60 p-4">
            <div class="grid gap-3 lg:grid-cols-[1fr_auto]">
                <div>
                    <InputLabel for="ai_text" value="ИИ-заполнение" />
                    <textarea
                        id="ai_text"
                        v-model="ai.text"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-indigo-100 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="ООО Ромашка, ИНН 7700000000, контакт Иван, телефон +7..., email billing@example.ru, проект сайт, услуга SEO 80000 в месяц"
                    />
                    <div v-if="ai.error" class="mt-2 text-sm text-red-600">{{ ai.error }}</div>
                </div>
                <div class="flex items-end">
                    <SecondaryButton type="button" :disabled="!canParseAi" @click="parseAiText">
                        {{ ai.loading ? 'Разбираем...' : 'Разобрать ИИ' }}
                    </SecondaryButton>
                </div>
            </div>

            <div v-if="ai.preview" class="mt-4 rounded-md border border-indigo-200 bg-white p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-950">Предпросмотр</div>
                        <div class="text-xs text-slate-500">Источник: {{ ai.preview.source }}, уверенность: {{ Math.round(ai.preview.confidence * 100) }}%</div>
                    </div>
                    <div class="flex gap-3">
                        <SecondaryButton type="button" @click="ai.preview = null">Отменить</SecondaryButton>
                        <PrimaryButton type="button" @click="applyAiPreview">Применить</PrimaryButton>
                    </div>
                </div>
                <dl class="mt-3 grid gap-3 text-sm md:grid-cols-2">
                    <div v-for="[key, value] in aiFields" :key="key">
                        <dt class="text-slate-500">{{ fieldLabels[key] ?? key }}</dt>
                        <dd class="font-medium text-slate-950">{{ displayValue(value) }}</dd>
                    </div>
                </dl>
            </div>
        </div>

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
