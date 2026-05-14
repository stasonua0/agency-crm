<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    settings: { type: Object, required: true },
});

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
});

const submit = () => form.put(route('settings.update'), { preserveScroll: true });
</script>

<template>
    <Head title="Настройки" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Настройки</h1>
                <p class="text-sm text-slate-500">Реквизиты студии для счетов и актов</p>
            </div>
        </template>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <form class="space-y-6" @submit.prevent="submit">
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

                <div class="flex justify-end">
                    <PrimaryButton :disabled="form.processing">Сохранить</PrimaryButton>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>
