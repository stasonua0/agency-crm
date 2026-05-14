<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    settings: { type: Object, required: true },
});

const modelOptions = computed(() => props.settings.ai_models_cache ?? []);

const form = useForm({
    name: props.settings.name ?? '',
    inn: props.settings.inn ?? '',
    kpp: props.settings.kpp ?? '',
    ogrn: props.settings.ogrn ?? '',
    address: props.settings.address ?? '',
    bank: props.settings.bank ?? '',
    checking_account: props.settings.checking_account ?? '',
    correspondent_account: props.settings.correspondent_account ?? '',
    bik: props.settings.bik ?? '',
    email: props.settings.email ?? '',
    phone: props.settings.phone ?? '',
    vat_enabled: Boolean(props.settings.vat_enabled),
    invoice_email_subject: props.settings.invoice_email_subject ?? 'Счёт {номер_счёта} на сумму {сумма}',
    invoice_email_body: props.settings.invoice_email_body ?? 'Здравствуйте.\n\nНаправляем счёт {номер_счёта} на сумму {сумма} по услуге {услуга}.\n\nКлиент: {клиент}',
    ai_provider: props.settings.ai_provider ?? 'stub',
    ai_api_key: '',
    ai_model: props.settings.ai_model ?? '',
});

const refreshForm = useForm({
    ai_api_key: '',
});

const submit = () => form.put(route('settings.update'), { preserveScroll: true });

const refreshModels = () => {
    refreshForm.ai_api_key = form.ai_api_key;
    refreshForm.post(route('settings.ai-models.refresh'), {
        preserveScroll: true,
        onSuccess: () => {
            form.ai_api_key = '';
        },
    });
};
</script>

<template>
    <Head title="Настройки" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Настройки</h1>
                <p class="text-sm text-slate-500">Реквизиты студии, шаблоны писем и ИИ-заполнение</p>
            </div>
        </template>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <form class="space-y-8" @submit.prevent="submit">
                <div class="space-y-6">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Реквизиты студии</h2>
                    </div>
                    <div class="grid gap-5 lg:grid-cols-3">
                        <div>
                            <InputLabel for="name" value="Название" />
                            <TextInput id="name" v-model="form.name" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>
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
                    </div>

                    <div class="grid gap-5 lg:grid-cols-3">
                        <div>
                            <InputLabel for="ogrn" value="ОГРН / ОГРНИП" />
                            <TextInput id="ogrn" v-model="form.ogrn" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.ogrn" />
                        </div>
                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>
                        <div>
                            <InputLabel for="phone" value="Телефон" />
                            <TextInput id="phone" v-model="form.phone" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.phone" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="address" value="Адрес" />
                        <textarea id="address" v-model="form.address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <InputError class="mt-2" :message="form.errors.address" />
                    </div>

                    <div class="grid gap-5 lg:grid-cols-4">
                        <div>
                            <InputLabel for="bank" value="Банк" />
                            <TextInput id="bank" v-model="form.bank" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.bank" />
                        </div>
                        <div>
                            <InputLabel for="checking_account" value="Расчётный счёт" />
                            <TextInput id="checking_account" v-model="form.checking_account" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.checking_account" />
                        </div>
                        <div>
                            <InputLabel for="correspondent_account" value="Корр. счёт" />
                            <TextInput id="correspondent_account" v-model="form.correspondent_account" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.correspondent_account" />
                        </div>
                        <div>
                            <InputLabel for="bik" value="БИК" />
                            <TextInput id="bik" v-model="form.bik" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.bik" />
                        </div>
                    </div>

                    <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                        <input v-model="form.vat_enabled" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                        Работает с НДС
                    </label>
                </div>

                <div class="space-y-5 border-t border-slate-200 pt-6">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">ИИ-заполнение</h2>
                        <p class="mt-1 text-sm text-slate-500">OpenAI-ключ хранится в базе зашифрованно. В GitHub и на экран ключ не выводится.</p>
                    </div>

                    <div class="grid gap-5 lg:grid-cols-3">
                        <div>
                            <InputLabel for="ai_provider" value="Провайдер" />
                            <select id="ai_provider" v-model="form.ai_provider" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="stub">Локальный stub</option>
                                <option value="openai">OpenAI</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.ai_provider" />
                        </div>

                        <div>
                            <InputLabel for="ai_api_key" value="OpenAI API-ключ" />
                            <TextInput
                                id="ai_api_key"
                                v-model="form.ai_api_key"
                                type="password"
                                class="mt-1 block w-full"
                                :placeholder="settings.has_ai_api_key ? 'Ключ сохранён. Введите новый для замены' : 'sk-...'"
                            />
                            <InputError class="mt-2" :message="form.errors.ai_api_key || refreshForm.errors.ai_api_key" />
                        </div>

                        <div class="flex items-end">
                            <SecondaryButton type="button" :disabled="refreshForm.processing" @click="refreshModels">
                                {{ refreshForm.processing ? 'Обновляем...' : 'Обновить модели' }}
                            </SecondaryButton>
                        </div>
                    </div>

                    <div class="grid gap-5 lg:grid-cols-2">
                        <div>
                            <InputLabel for="ai_model" value="Модель" />
                            <select id="ai_model" v-model="form.ai_model" :disabled="form.ai_provider !== 'openai'" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-100">
                                <option value="">Не выбрана</option>
                                <option v-for="model in modelOptions" :key="model" :value="model">{{ model }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.ai_model" />
                        </div>
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                            <div>Ключ: {{ settings.has_ai_api_key ? 'сохранён' : 'не задан' }}</div>
                            <div>Моделей в списке: {{ modelOptions.length }}</div>
                            <div>Последнее обновление: {{ settings.ai_models_synced_at || 'ещё не выполнялось' }}</div>
                        </div>
                    </div>
                </div>

                <div class="space-y-5 border-t border-slate-200 pt-6">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Шаблон письма со счётом</h2>
                        <p class="mt-1 text-sm text-slate-500">Доступные переменные: {номер_счёта}, {сумма}, {клиент}, {услуга}</p>
                    </div>
                    <div>
                        <InputLabel for="invoice_email_subject" value="Тема письма" />
                        <TextInput id="invoice_email_subject" v-model="form.invoice_email_subject" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.invoice_email_subject" />
                    </div>
                    <div>
                        <InputLabel for="invoice_email_body" value="Текст письма" />
                        <textarea id="invoice_email_body" v-model="form.invoice_email_body" rows="7" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <InputError class="mt-2" :message="form.errors.invoice_email_body" />
                    </div>
                </div>

                <div class="flex justify-end">
                    <PrimaryButton :disabled="form.processing">Сохранить</PrimaryButton>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>
