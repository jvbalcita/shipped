<script setup lang="ts">
import { parseDate } from '@internationalized/date';
import { CalendarDays, X } from '@lucide/vue';
import type { DateValue } from 'reka-ui';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';

const props = withDefaults(
    defineProps<{
        id?: string;
        modelValue: string;
        placeholder?: string;
    }>(),
    { id: undefined, modelValue: '', placeholder: 'Pick a date' },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const isOpen = ref(false);
const selectedDate = ref<DateValue | undefined>();

watch(
    () => props.modelValue,
    (value) => {
        selectedDate.value = value ? parseDate(value) : undefined;
    },
    { immediate: true },
);

const displayValue = computed(() => {
    if (!props.modelValue) {
        return props.placeholder;
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
    }).format(new Date(`${props.modelValue}T00:00:00`));
});

function selectDate(value: DateValue | undefined): void {
    selectedDate.value = value;
    emit('update:modelValue', value ? value.toString() : '');
}

function clearDate(): void {
    selectDate(undefined);
}
</script>

<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button
                :id="id"
                type="button"
                variant="outline"
                class="h-10 w-full justify-between gap-2 font-normal tracking-normal normal-case"
                :class="!modelValue && 'text-muted-foreground'"
                data-test="date-picker-trigger"
            >
                <span class="truncate">{{ displayValue }}</span>
                <span class="flex shrink-0 items-center gap-1">
                    <span
                        v-if="modelValue"
                        role="button"
                        tabindex="0"
                        aria-label="Clear date"
                        class="inline-flex size-6 items-center justify-center rounded-none hover:bg-secondary"
                        @click.stop="clearDate"
                        @keydown.enter.stop.prevent="clearDate"
                    >
                        <X class="size-4" />
                    </span>
                    <CalendarDays class="size-4" />
                </span>
            </Button>
        </PopoverTrigger>
        <PopoverContent
            class="w-auto rounded-none border-2 border-foreground bg-background p-0 shadow-none"
            align="start"
        >
            <Calendar
                :model-value="selectedDate"
                layout="month-and-year"
                @update:model-value="selectDate"
            />
        </PopoverContent>
    </Popover>
</template>
