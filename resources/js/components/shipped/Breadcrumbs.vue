<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';

export type BreadcrumbItem = {
    label: string;
    href?: string;
};

defineProps<{
    items: BreadcrumbItem[];
}>();
</script>

<template>
    <nav
        aria-label="Breadcrumb"
        class="border-b border-foreground px-5 py-3 sm:px-8"
    >
        <ol class="flex flex-wrap items-center gap-2">
            <li
                v-for="(item, index) in items"
                :key="item.href ?? item.label + '-' + index"
                class="flex items-center gap-2"
            >
                <ChevronRight
                    v-if="index > 0"
                    class="size-3 text-muted-foreground"
                    aria-hidden="true"
                />
                <Link
                    v-if="item.href && index < items.length - 1"
                    :href="item.href"
                    class="technical-label text-primary underline underline-offset-4"
                >
                    {{ item.label }}
                </Link>
                <span
                    v-else
                    class="technical-label text-muted-foreground"
                    aria-current="page"
                >
                    {{ item.label }}
                </span>
            </li>
        </ol>
    </nav>
</template>
