<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const summary = [
    { label: 'Доход за месяц', value: '0 ₽', detail: 'Операций пока нет' },
    { label: 'Расход за месяц', value: '0 ₽', detail: 'Операций пока нет' },
    { label: 'Прибыль', value: '0 ₽', detail: 'Кассовый метод' },
    { label: 'Ожидаемые оплаты', value: '0', detail: 'Появятся на этапе 4' },
];

const nextModules = [
    'Клиенты, услуги и проекты',
    'Регулярные операции',
    'Начисления и счета',
    'Отчёты и журнал аудита',
];
</script>

<template>
    <Head title="Дашборд" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-950">Дашборд</h1>
                <p class="text-sm text-slate-500">Рабочее место CRM MVP</p>
            </div>
        </template>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="item in summary"
                :key="item.label"
                class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div class="text-sm font-medium text-slate-500">{{ item.label }}</div>
                <div class="mt-3 text-2xl font-semibold text-slate-950">{{ item.value }}</div>
                <div class="mt-1 text-sm text-slate-500">{{ item.detail }}</div>
            </article>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-[1.4fr_0.8fr]">
            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Статус этапа 1</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Здесь подготовлены каркас приложения, авторизация, роли, навигация, PostgreSQL и Redis.
                        </p>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                        Готово
                    </span>
                </div>

                <div class="mt-6 overflow-hidden rounded-lg border border-slate-200">
                    <div class="grid grid-cols-3 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <span>Раздел</span>
                        <span>Статус</span>
                        <span>Примечание</span>
                    </div>
                    <div class="divide-y divide-slate-200 text-sm">
                        <div class="grid grid-cols-3 px-4 py-3">
                            <span class="font-medium text-slate-900">Авторизация</span>
                            <span class="text-emerald-700">Готово</span>
                            <span class="text-slate-500">Breeze + Inertia</span>
                        </div>
                        <div class="grid grid-cols-3 px-4 py-3">
                            <span class="font-medium text-slate-900">Роли</span>
                            <span class="text-emerald-700">Готово</span>
                            <span class="text-slate-500">Владелец, финансовый менеджер, наблюдатель</span>
                        </div>
                        <div class="grid grid-cols-3 px-4 py-3">
                            <span class="font-medium text-slate-900">Бизнес-модули</span>
                            <span class="text-amber-700">Запланировано</span>
                            <span class="text-slate-500">Начинаются на этапе 2</span>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-950">Следующие модули</h2>
                <div class="mt-5 space-y-4">
                    <div
                        v-for="(module, index) in nextModules"
                        :key="module"
                        class="flex gap-3"
                    >
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-indigo-50 text-sm font-semibold text-indigo-700">
                            {{ index + 1 }}
                        </div>
                        <div class="pt-1 text-sm font-medium text-slate-700">{{ module }}</div>
                    </div>
                </div>
            </aside>
        </section>
    </AuthenticatedLayout>
</template>
