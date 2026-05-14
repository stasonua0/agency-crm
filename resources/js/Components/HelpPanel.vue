<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    links: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <aside class="mb-5 rounded-lg border border-indigo-100 bg-indigo-50/70 p-4 text-sm text-slate-700">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="font-semibold text-slate-950">{{ title }}</div>
                <p class="mt-1 max-w-4xl leading-6">{{ description }}</p>
            </div>
            <div v-if="links.length" class="min-w-64 rounded-md bg-white/75 p-3">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Связано с</div>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span
                        v-for="link in links"
                        :key="link"
                        class="rounded-md bg-indigo-100 px-2 py-1 text-xs font-semibold text-indigo-700"
                    >
                        {{ link }}
                    </span>
                </div>
            </div>
        </div>
    </aside>
</template>
