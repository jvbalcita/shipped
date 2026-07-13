<script setup lang="ts">
import {
    CalendarDate,
    getLocalTimeZone,
    parseDate,
    today,
} from '@internationalized/date';
import { CalendarClock } from '@lucide/vue';
import type { DateValue } from 'reka-ui';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const props = withDefaults(
    defineProps<{
        id: string;
        modelValue: string;
    }>(),
    {
        modelValue: '',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
    'validity-change': [isValid: boolean];
}>();

const isOpen = ref(false);
const selectedDate = ref<DateValue>();
const selectedHour = ref('09');
const selectedMinute = ref('00');
const currentTime = ref(new Date());
let isSynchronizingModelValue = false;
let currentTimeInterval: ReturnType<typeof setInterval> | undefined;

const hours = Array.from({ length: 24 }, (_, hour) =>
    String(hour).padStart(2, '0'),
);
const minutes = ['00', '15', '30', '45'];

const selectedMoment = computed(() => {
    if (!selectedDate.value) {
        return null;
    }

    const date = selectedDate.value.toDate(getLocalTimeZone());

    return new Date(
        date.getFullYear(),
        date.getMonth(),
        date.getDate(),
        Number(selectedHour.value),
        Number(selectedMinute.value),
    );
});

const isFuture = computed(
    () =>
        selectedMoment.value !== null &&
        selectedMoment.value > currentTime.value,
);

const displayValue = computed(() => {
    if (!selectedMoment.value) {
        return 'Choose a future date and time';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(selectedMoment.value);
});

function emitDateTime(): void {
    if (!selectedMoment.value) {
        emit('update:modelValue', '');

        return;
    }

    emit('update:modelValue', selectedMoment.value.toISOString());
}

function selectDate(value: DateValue | undefined): void {
    selectedDate.value = value;
    emitDateTime();
}

function chooseNextSlot(): void {
    const nextSlot = new Date();

    nextSlot.setSeconds(0, 0);
    nextSlot.setMinutes(Math.ceil((nextSlot.getMinutes() + 1) / 15) * 15);

    selectedDate.value = new CalendarDate(
        nextSlot.getFullYear(),
        nextSlot.getMonth() + 1,
        nextSlot.getDate(),
    );
    selectedHour.value = String(nextSlot.getHours()).padStart(2, '0');
    selectedMinute.value = String(nextSlot.getMinutes()).padStart(2, '0');

    emitDateTime();
}

watch(
    () => [selectedHour.value, selectedMinute.value],
    () => {
        if (!isSynchronizingModelValue) {
            emitDateTime();
        }
    },
    { flush: 'sync' },
);

watch(
    () => props.modelValue,
    (value) => {
        isSynchronizingModelValue = true;

        if (!value) {
            selectedDate.value = undefined;
            isSynchronizingModelValue = false;

            return;
        }

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            selectedDate.value = undefined;
            isSynchronizingModelValue = false;

            return;
        }

        selectedDate.value = parseDate(
            `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`,
        );
        selectedHour.value = String(date.getHours()).padStart(2, '0');
        selectedMinute.value = String(date.getMinutes()).padStart(2, '0');
        isSynchronizingModelValue = false;
    },
    { immediate: true, flush: 'sync' },
);

watch(isFuture, (value) => emit('validity-change', value), { immediate: true });

onMounted(() => {
    currentTimeInterval = setInterval(() => {
        currentTime.value = new Date();
    }, 30_000);
});

onUnmounted(() => {
    if (currentTimeInterval) {
        clearInterval(currentTimeInterval);
    }
});
</script>

<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button
                :id="id"
                type="button"
                variant="outline"
                class="h-10 w-full justify-between text-left font-normal tracking-normal normal-case"
                :class="[
                    !selectedDate && 'text-muted-foreground',
                    selectedDate &&
                        !isFuture &&
                        'border-destructive text-destructive',
                ]"
                :aria-invalid="selectedDate && !isFuture ? 'true' : undefined"
            >
                <span>{{ displayValue }}</span>
                <CalendarClock class="size-4" />
            </Button>
        </PopoverTrigger>
        <PopoverContent
            class="w-auto rounded-none border-2 border-foreground bg-background p-0 shadow-none"
            align="start"
        >
            <Calendar
                :model-value="selectedDate"
                :min-value="today(getLocalTimeZone())"
                layout="month-and-year"
                @update:model-value="selectDate"
            />
            <div
                class="grid gap-px border-t border-foreground bg-foreground sm:grid-cols-[1fr_1fr_auto]"
            >
                <Select v-model="selectedHour">
                    <SelectTrigger
                        class="w-full rounded-none border-0 bg-background"
                    >
                        <SelectValue placeholder="Hour" />
                    </SelectTrigger>
                    <SelectContent class="rounded-none border-foreground">
                        <SelectItem
                            v-for="hour in hours"
                            :key="hour"
                            :value="hour"
                        >
                            {{ hour }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Select v-model="selectedMinute">
                    <SelectTrigger
                        class="w-full rounded-none border-0 bg-background"
                    >
                        <SelectValue placeholder="Minute" />
                    </SelectTrigger>
                    <SelectContent class="rounded-none border-foreground">
                        <SelectItem
                            v-for="minute in minutes"
                            :key="minute"
                            :value="minute"
                        >
                            :{{ minute }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Button
                    type="button"
                    variant="secondary"
                    class="rounded-none border-0"
                    @click="chooseNextSlot"
                >
                    Next slot
                </Button>
            </div>
            <div
                class="flex items-center justify-between border-t border-foreground p-3"
            >
                <p class="text-xs text-muted-foreground">
                    Future dates only. Times use your local timezone.
                </p>
                <Button
                    type="button"
                    size="sm"
                    :disabled="!isFuture"
                    @click="isOpen = false"
                >
                    Set time
                </Button>
            </div>
            <p
                v-if="selectedDate && !isFuture"
                role="alert"
                class="border-t border-destructive px-3 py-2 text-xs text-destructive"
            >
                Choose a time after the current moment.
            </p>
        </PopoverContent>
    </Popover>
</template>
