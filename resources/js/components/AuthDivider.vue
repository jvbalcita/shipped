<script setup lang="ts">
import { Separator } from '@/components/ui/separator';

defineProps<{
    label: string;
}>();
</script>

<template>
    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
            <Separator class="w-full" />
        </div>
        <div class="relative flex justify-center text-xs uppercase">
            <span class="bg-background px-2 text-muted-foreground">
                {{ label }}
            </span>
        </div>
    </div>
</template>
