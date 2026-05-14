<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const showingSidebar = ref(false);

const roleLabels = {
    owner: 'Владелец',
    finance_manager: 'Финансовый менеджер',
    viewer: 'Наблюдатель',
};

const navigationGroups = [
    {
        title: 'Рабочий стол',
        items: [
            { label: 'Дашборд', routeName: 'dashboard', href: route('dashboard'), mark: 'ДБ' },
        ],
    },
    {
        title: 'Справочники',
        items: [
            { label: 'Клиенты', routeName: 'clients.index', href: route('clients.index'), mark: 'КЛ' },
            { label: 'Проекты', routeName: 'projects.index', href: route('projects.index'), mark: 'ПР' },
            { label: 'Услуги', routeName: 'services.index', href: route('services.index'), mark: 'УС' },
            { label: 'Получатели выплат', routeName: 'payees.index', href: route('payees.index'), mark: 'ПВ' },
        ],
    },
    {
        title: 'Финансы',
        items: [
            { label: 'Регулярные операции', routeName: 'recurring-items.index', href: route('recurring-items.index'), mark: 'РО' },
            { label: 'Начисления', routeName: 'payment.occurrences.index', href: route('payment.occurrences.index'), mark: 'НЧ' },
            { label: 'Финансовые операции', routeName: 'financial.operations.index', href: route('financial.operations.index'), mark: 'ФО' },
            { label: 'Выплаты', routeName: 'payouts.index', href: route('payouts.index'), mark: 'ВП' },
            { label: 'Зарплаты', routeName: 'payroll.index', href: route('payroll.index'), mark: 'ЗП' },
            { label: 'ПФ', routeName: 'pf.index', href: route('pf.index'), mark: 'ПФ' },
        ],
    },
    {
        title: 'Документы',
        items: [
            { label: 'Счета', routeName: 'invoices.index', href: route('invoices.index'), mark: 'СЧ' },
            { label: 'Акты', routeName: 'acts.index', href: route('acts.index'), mark: 'АК' },
        ],
    },
    {
        title: 'Аналитика',
        items: [
            { label: 'Отчёты', routeName: 'reports.index', href: route('reports.index'), mark: 'ОТ' },
        ],
    },
    {
        title: 'Администрирование',
        items: [
            { label: 'Настройки', routeName: 'settings.index', href: route('settings.index'), mark: 'НС' },
            { label: 'Журнал аудита', routeName: 'audit.log.index', href: route('audit.log.index'), mark: 'ЖА' },
        ],
    },
];

const isActive = (routeName) => route().current(routeName);
const groupIsActive = (group) => group.items.some((item) => isActive(item.routeName));
</script>

<template>
    <div class="min-h-screen bg-[#f6f7fb] text-slate-900">
        <aside
            class="fixed inset-y-0 left-0 z-40 w-72 border-r border-slate-200 bg-white shadow-sm transition-transform duration-200 lg:translate-x-0"
            :class="showingSidebar ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-16 items-center gap-3 border-b border-slate-100 px-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white">
                    CRM
                </div>
                <div>
                    <div class="text-sm font-semibold tracking-wide text-slate-950">Agency CRM</div>
                    <div class="text-xs text-slate-500">Финансовое рабочее место</div>
                </div>
            </div>

            <nav class="h-[calc(100vh-4rem)] overflow-y-auto px-3 py-4">
                <section
                    v-for="group in navigationGroups"
                    :key="group.title"
                    class="mb-4"
                >
                    <div
                        class="mb-1 px-3 text-[11px] font-bold uppercase tracking-wide"
                        :class="groupIsActive(group) ? 'text-indigo-700' : 'text-slate-400'"
                    >
                        {{ group.title }}
                    </div>

                    <Link
                        v-for="item in group.items"
                        :key="item.routeName"
                        :href="item.href"
                        class="mb-1 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition"
                        :class="isActive(item.routeName)
                            ? 'bg-indigo-50 text-indigo-700'
                            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'"
                        @click="showingSidebar = false"
                    >
                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-[11px] font-bold"
                            :class="isActive(item.routeName) ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500'"
                        >
                            {{ item.mark }}
                        </span>
                        <span class="truncate">{{ item.label }}</span>
                    </Link>
                </section>
            </nav>
        </aside>

        <div class="lg:pl-72">
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
                <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 lg:hidden"
                            @click="showingSidebar = !showingSidebar"
                            aria-label="Открыть меню"
                        >
                            <span class="flex flex-col gap-1">
                                <span class="block h-0.5 w-4 bg-current"></span>
                                <span class="block h-0.5 w-4 bg-current"></span>
                                <span class="block h-0.5 w-4 bg-current"></span>
                            </span>
                        </button>
                        <div>
                            <slot name="header" />
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="hidden text-right sm:block">
                            <div class="text-sm font-semibold text-slate-900">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-xs uppercase tracking-wide text-slate-500">
                                {{ roleLabels[$page.props.auth.user.role] ?? $page.props.auth.user.role }}
                            </div>
                        </div>

                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700"
                                >
                                    {{ $page.props.auth.user.name.charAt(0) }}
                                </button>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')">
                                    Профиль
                                </DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">
                                    Выйти
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </header>

            <main class="px-4 py-6 sm:px-6 lg:px-8">
                <div
                    v-if="$page.props.flash?.success"
                    class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
                >
                    {{ $page.props.flash.success }}
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
