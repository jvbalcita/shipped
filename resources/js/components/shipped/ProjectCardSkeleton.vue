<script setup lang="ts">
defineProps<{ count?: number }>();
</script>

<template>
    <div
        v-for="n in count ?? 6"
        :key="n"
        class="grid animate-pulse border border-foreground bg-background"
        aria-hidden="true"
    >
        <div class="aspect-[16/10] bg-secondary" />
        <div class="grid gap-px bg-foreground">
            <div class="flex items-center justify-between bg-background px-4 py-3">
                <div class="h-4 w-20 bg-secondary" />
                <div class="h-4 w-8 bg-secondary" />
            </div>
            <div class="bg-background px-4 py-4">
                <div class="h-6 w-3/4 bg-secondary" />
                <div class="mt-3 h-4 w-full bg-secondary" />
            </div>
            <div class="bg-background px-4 py-3">
                <div class="h-3 w-24 bg-secondary" />
            </div>
        </div>
    </div>
</template>
