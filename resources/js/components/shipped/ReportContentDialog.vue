<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { Flag } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Textarea } from '@/components/ui/textarea';
import { store } from '@/routes/reports';
import { contentReportReasons } from '@/types';
import type { ContentReportableType } from '@/types';

const props = defineProps<{
    reportableType: ContentReportableType;
    reportableId: number;
    subjectLabel: string;
    ariaLabel?: string;
}>();

const open = ref(false);
const page = usePage();

const form = useForm({
    reportable_type: props.reportableType,
    reportable_id: props.reportableId,
    reason: '' as string,
    note: '',
});

function submit(): void {
    form.post(store().url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Report sent to the curators. Thank you.');
            form.reset();
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-if="page.props.auth.user" v-model:open="open">
        <DialogTrigger as-child>
            <Button
                variant="ghost"
                size="sm"
                class="text-muted-foreground hover:text-foreground"
                :aria-label="props.ariaLabel ?? `Report ${props.subjectLabel}`"
                :data-test="`report-open-${props.reportableType}`"
            >
                <Flag class="size-4" />
                <slot />
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Report {{ props.subjectLabel }}</DialogTitle>
                <DialogDescription>
                    Reports go to the curators only. Nothing happens
                    automatically — a person reviews every report against the
                    registry's trust contract.
                </DialogDescription>
            </DialogHeader>
            <form novalidate class="grid gap-5" @submit.prevent="submit">
                <Field>
                    <FieldLabel>What is wrong?</FieldLabel>
                    <RadioGroup
                        v-model="form.reason"
                        class="grid gap-2"
                        data-test="report-reason"
                    >
                        <Label
                            v-for="reason in contentReportReasons"
                            :key="reason.value"
                            :for="`report-reason-${reason.value}`"
                            class="flex cursor-pointer items-center gap-3 border border-foreground p-3 font-normal transition-colors hover:bg-secondary has-[[data-state=checked]]:bg-secondary"
                        >
                            <RadioGroupItem
                                :id="`report-reason-${reason.value}`"
                                :value="reason.value"
                            />
                            {{ reason.label }}
                        </Label>
                    </RadioGroup>
                    <FieldError v-if="form.errors.reason">{{
                        form.errors.reason
                    }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel for="report-note"
                        >Details
                        <span class="text-muted-foreground"
                            >(required for "Something else")</span
                        ></FieldLabel
                    >
                    <Textarea
                        id="report-note"
                        v-model="form.note"
                        class="min-h-24"
                        maxlength="1000"
                        data-test="report-note"
                    />
                    <FieldError v-if="form.errors.note">{{
                        form.errors.note
                    }}</FieldError>
                </Field>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        data-test="report-submit"
                        >Send report</Button
                    >
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
