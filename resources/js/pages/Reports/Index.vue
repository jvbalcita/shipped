<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Flag, ShieldAlert } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import PublicShell from '@/components/shipped/PublicShell.vue';
import SectionHeader from '@/components/shipped/SectionHeader.vue';
import TextLink from '@/components/TextLink.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Textarea } from '@/components/ui/textarea';
import { update } from '@/routes/reports';
import type { ContentReportRow } from '@/types';

defineProps<{
    reports: ContentReportRow[];
}>();

const resolving = ref<ContentReportRow | null>(null);

const form = useForm({
    resolution: 'no_action',
    resolution_note: '',
});

function openResolve(report: ContentReportRow): void {
    resolving.value = report;
    form.resolution = 'no_action';
    form.resolution_note = '';
}

function submitResolve(): void {
    const report = resolving.value;

    if (report === null) {
        return;
    }

    form.patch(update({ report: report.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Report resolved.');
            resolving.value = null;
        },
    });
}

function formatTimestamp(iso: string | null): string {
    if (!iso) {
        return '';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(iso));
}
</script>

<template>
    <PublicShell title="Studio: reports">
        <section
            class="page-enter mx-auto w-full min-w-0 border-x border-b border-foreground"
        >
            <SectionHeader label="Curator / Reports queue">
                <h1
                    class="display-type mt-12 max-w-4xl text-[clamp(3rem,6.5vw,6.75rem)] sm:mt-0"
                >
                    Reports.
                </h1>
                <p
                    class="mt-6 max-w-xl text-sm leading-6 text-muted-foreground"
                >
                    Every builder-flagged record, oldest first. Resolving
                    records the outcome; act on the underlying content with the
                    studio tools before choosing "action taken".
                </p>
            </SectionHeader>

            <Empty
                v-if="!reports.length"
                class="border-y border-foreground py-20"
                data-test="reports-empty"
            >
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <ShieldAlert />
                    </EmptyMedia>
                    <EmptyTitle>Queue clear</EmptyTitle>
                    <EmptyDescription>
                        No open reports. The registry's trust contract holds —
                        for now.
                    </EmptyDescription>
                </EmptyHeader>
            </Empty>

            <ul
                v-else
                class="divide-y divide-foreground border-t border-foreground"
            >
                <li
                    v-for="report in reports"
                    :key="report.id"
                    class="grid gap-4 p-5 sm:p-8"
                    :data-test="`report-row-${report.id}`"
                >
                    <div class="flex flex-wrap items-center gap-3">
                        <Badge variant="outline">{{
                            report.reason_label
                        }}</Badge>
                        <span
                            class="technical-label text-muted-foreground"
                            data-test="report-subject-context"
                            >{{
                                report.subject.context ?? report.subject.title
                            }}</span
                        >
                        <span
                            v-if="!report.subject.live"
                            class="technical-label text-destructive"
                            >no longer live</span
                        >
                        <span class="technical-label text-muted-foreground">{{
                            formatTimestamp(report.created_at)
                        }}</span>
                    </div>

                    <blockquote
                        v-if="report.subject.excerpt"
                        class="border-l-2 border-primary pl-4 font-prose text-sm leading-7"
                        data-test="report-subject-excerpt"
                    >
                        {{ report.subject.excerpt }}
                    </blockquote>

                    <p
                        v-if="report.note"
                        class="text-sm leading-6"
                        data-test="report-note"
                    >
                        <span class="technical-label text-muted-foreground"
                            >Reporter note:
                        </span>
                        {{ report.note }}
                    </p>

                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                        <span class="technical-label text-muted-foreground">
                            Reported by
                            <template v-if="report.reporter_username"
                                >@{{ report.reporter_username }}</template
                            >
                            <template v-else>an unknown builder</template>
                        </span>
                        <TextLink
                            v-if="report.subject.url"
                            :href="report.subject.url"
                            class="technical-label inline-flex items-center gap-1"
                            data-test="report-subject-link"
                        >
                            <Flag class="size-3" aria-hidden="true" />
                            View reported {{ report.subject.type }}
                        </TextLink>
                        <Button
                            size="sm"
                            variant="outline"
                            data-test="resolve-open"
                            @click="openResolve(report)"
                            >Resolve</Button
                        >
                    </div>
                </li>
            </ul>
        </section>

        <Dialog
            :open="resolving !== null"
            @update:open="
                (value: boolean) => {
                    if (!value) resolving = null;
                }
            "
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Resolve report</DialogTitle>
                    <DialogDescription>
                        "No action" dismisses the report. "Action taken" records
                        that you removed or corrected the underlying content —
                        use the studio tools to do that first.
                    </DialogDescription>
                </DialogHeader>
                <form
                    novalidate
                    class="grid gap-5"
                    @submit.prevent="submitResolve"
                >
                    <Field>
                        <FieldLabel>Outcome</FieldLabel>
                        <RadioGroup
                            v-model="form.resolution"
                            class="grid gap-2"
                            data-test="resolve-outcome"
                        >
                            <Label
                                for="resolve-no-action"
                                class="flex cursor-pointer items-center gap-3 border border-foreground p-3 font-normal transition-colors hover:bg-secondary has-[[data-state=checked]]:bg-secondary"
                            >
                                <RadioGroupItem
                                    id="resolve-no-action"
                                    value="no_action"
                                />
                                No action — dismiss the report
                            </Label>
                            <Label
                                for="resolve-action-taken"
                                class="flex cursor-pointer items-center gap-3 border border-foreground p-3 font-normal transition-colors hover:bg-secondary has-[[data-state=checked]]:bg-secondary"
                            >
                                <RadioGroupItem
                                    id="resolve-action-taken"
                                    value="action_taken"
                                />
                                Action taken — content was removed or corrected
                            </Label>
                        </RadioGroup>
                        <FieldError v-if="form.errors.resolution">{{
                            form.errors.resolution
                        }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel for="resolution-note"
                            >Resolution note
                            <span class="text-muted-foreground"
                                >(optional)</span
                            ></FieldLabel
                        >
                        <Textarea
                            id="resolution-note"
                            v-model="form.resolution_note"
                            class="min-h-20"
                            maxlength="1000"
                            data-test="resolve-note"
                        />
                        <FieldError v-if="form.errors.resolution_note">{{
                            form.errors.resolution_note
                        }}</FieldError>
                    </Field>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="resolving = null"
                            >Cancel</Button
                        >
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            data-test="resolve-submit"
                            >Resolve report</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </PublicShell>
</template>
