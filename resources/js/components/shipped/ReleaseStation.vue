<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { CalendarClock, Send } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import DateTimePicker from '@/components/shipped/DateTimePicker.vue';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { focusFirstError } from '@/lib/focusFirstError';
import { store as releaseStore } from '@/routes/projects/releases';
import type { StudioProject } from '@/types/creator';

const props = defineProps<{
    project: StudioProject;
}>();

const releaseForm = useForm({
    title: '',
    notes: '',
    timing: 'now',
    published_at: '',
});

const isScheduledTimeValid = ref(false);
const isScheduled = computed(() => releaseForm.timing === 'schedule');

const ERROR_FIELD_IDS: Record<string, string> = {
    title: 'release_title',
    notes: 'release_notes',
    published_at: 'published_at',
};

function submitRelease(): void {
    if (isScheduled.value && !releaseForm.published_at) {
        releaseForm.setError(
            'published_at',
            'Choose a future publication date and time.',
        );

        return;
    }

    if (
        isScheduled.value &&
        (!isScheduledTimeValid.value ||
            new Date(releaseForm.published_at) <= new Date())
    ) {
        releaseForm.setError(
            'published_at',
            'Choose a publication time after the current moment.',
        );

        return;
    }

    releaseForm.clearErrors('published_at');
    releaseForm.post(releaseStore(props.project).url, {
        preserveScroll: true,
        onSuccess: () => {
            releaseForm.reset();
            isScheduledTimeValid.value = false;
        },
        onError: (errors) => focusFirstError(errors, ERROR_FIELD_IDS),
    });
}

function updateScheduledTimeValidity(isValid: boolean): void {
    isScheduledTimeValid.value = isValid;

    if (isValid) {
        releaseForm.clearErrors('published_at');
    }
}

watch(isScheduled, (isScheduling) => {
    if (!isScheduling) {
        releaseForm.clearErrors('published_at');
    }
});
</script>

<template>
    <div class="grid gap-10">
        <form
            novalidate
            class="grid max-w-3xl gap-5"
            data-test="release-form"
            @submit.prevent="submitRelease"
        >
            <Field
                ><FieldLabel for="release_title"
                    >Release title
                    <span class="text-primary" aria-hidden="true"
                        >*</span
                    > </FieldLabel
                ><Input
                    id="release_title"
                    v-model="releaseForm.title"
                    required
                    placeholder="What changed?"
                /><FieldError v-if="releaseForm.errors.title">{{
                    releaseForm.errors.title
                }}</FieldError></Field
            >
            <Field
                ><FieldLabel for="release_notes"
                    >Release notes
                    <span class="text-primary" aria-hidden="true"
                        >*</span
                    > </FieldLabel
                ><Textarea
                    id="release_notes"
                    v-model="releaseForm.notes"
                    required
                    placeholder="Say what changed, why it matters, and where to try it."
                /><FieldError v-if="releaseForm.errors.notes">{{
                    releaseForm.errors.notes
                }}</FieldError></Field
            >
            <Field
                ><FieldLabel>Timing</FieldLabel
                ><RadioGroup
                    v-model="releaseForm.timing"
                    class="grid gap-px border border-foreground bg-foreground sm:grid-cols-2"
                    ><label
                        class="flex cursor-pointer items-center gap-3 bg-background p-4"
                        ><RadioGroupItem value="now" /><span
                            ><span class="technical-label">Now</span
                            ><span class="mt-1 block text-sm"
                                >Publish immediately</span
                            ></span
                        ></label
                    ><label
                        class="flex cursor-pointer items-center gap-3 bg-background p-4"
                        ><RadioGroupItem value="schedule" /><span
                            ><span class="technical-label">Schedule</span
                            ><span class="mt-1 block text-sm"
                                >Choose a future time</span
                            ></span
                        ></label
                    ></RadioGroup
                ></Field
            >
            <Field v-if="isScheduled"
                ><FieldLabel for="published_at"
                    >Publication date and time</FieldLabel
                ><DateTimePicker
                    id="published_at"
                    v-model="releaseForm.published_at"
                    @validity-change="updateScheduledTimeValidity"
                />
                <p class="mt-2 text-sm text-muted-foreground">
                    Pick a future moment. Your local time is sent to Shipped and
                    validated again by the server.
                </p>
                <FieldError v-if="releaseForm.errors.published_at">{{
                    releaseForm.errors.published_at
                }}</FieldError></Field
            >
            <Button
                type="submit"
                class="self-start"
                :disabled="
                    releaseForm.processing ||
                    (isScheduled && !isScheduledTimeValid)
                "
                ><Spinner v-if="releaseForm.processing" /><CalendarClock
                    v-else-if="isScheduled"
                    class="size-4"
                /><Send v-else class="size-4" />{{
                    isScheduled ? 'Schedule release' : 'Publish release now'
                }}</Button
            >
        </form>
        <div>
            <p class="technical-label text-muted-foreground">Release archive</p>
            <ScrollArea class="mt-4 max-h-96 border border-foreground"
                ><ol class="divide-y divide-foreground">
                    <li
                        v-for="release in project.releases"
                        :key="release.id"
                        class="p-4"
                    >
                        <p class="technical-label text-muted-foreground">
                            {{
                                release.published_at
                                    ? new Date(
                                          release.published_at,
                                      ).toLocaleString()
                                    : 'Private draft'
                            }}
                        </p>
                        <h3 class="mt-2 font-semibold">
                            {{ release.title }}
                        </h3>
                        <p class="mt-2 text-sm leading-6 whitespace-pre-line">
                            {{ release.notes }}
                        </p>
                    </li>
                    <li
                        v-if="!project.releases.length"
                        class="p-4 text-sm text-muted-foreground"
                    >
                        No release story yet.
                    </li>
                </ol></ScrollArea
            >
        </div>
    </div>
</template>
