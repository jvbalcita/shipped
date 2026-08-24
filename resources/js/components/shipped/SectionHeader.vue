<script setup lang="ts">
/**
 * Recurring page section header: a technical kicker label beside a
 * display-type headline column. Replaces the duplicated "technical-label +
 * display-type" grid header across public and studio pages.
 */
defineProps<{
    /** Technical kicker label, e.g. "Creator studio / Workspace". */
    label: string;
}>();
</script>

<template>
    <div
        class="grid border-b border-foreground p-5 sm:grid-cols-[.45fr_1.55fr] sm:p-8"
    >
        <p class="technical-label text-primary">{{ label }}</p>
        <div class="min-w-0">
            <slot />
        </div>
    </div>
</template>
