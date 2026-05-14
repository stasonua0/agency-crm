<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <nav v-if="links.length > 3" class="flex flex-wrap gap-2">
        <component
            :is="link.url ? Link : 'span'"
            v-for="link in links"
            :key="link.label"
            :href="link.url"
            class="rounded-md border px-3 py-2 text-sm"
            :class="[
                link.active
                    ? 'border-indigo-600 bg-indigo-600 text-white'
                    : 'border-slate-200 bg-white text-slate-700',
                !link.url ? 'cursor-not-allowed opacity-50' : 'hover:border-indigo-300 hover:text-indigo-700',
            ]"
            v-html="link.label"
        />
    </nav>
</template>
