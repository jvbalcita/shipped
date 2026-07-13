<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { CheckCircle2, CloudOff, RefreshCw } from '@lucide/vue';
import { computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard } from '@/routes';
import { store } from '@/routes/projects/verification';
import type { ConnectedEnvironmentSummary } from '@/types';

const props = defineProps<{
    project: {
        slug: string;
        connected_environment_id: number | null;
        verification_status: string;
        verification_failure_reason: string | null;
        verified_at: string | null;
        connected_environment: { environment_name: string } | null;
    };
    environments: ConnectedEnvironmentSummary[];
}>();

const form = useForm({
    connected_environment_id: props.project.connected_environment_id
        ? String(props.project.connected_environment_id)
        : '',
});

watch(
    () => props.project.connected_environment_id,
    (environmentId) => {
        form.connected_environment_id = environmentId
            ? String(environmentId)
            : '';
    },
);

const hasConnection = computed(() => props.environments.length > 0);
const isVerified = computed(
    () => props.project.verification_status === 'verified',
);
const isStale = computed(() => props.project.verification_status === 'stale');
const isFailed = computed(() => props.project.verification_status === 'failed');
const requiresReconnect = computed(
    () =>
        isStale.value ||
        props.project.verification_failure_reason ===
            'Laravel Cloud credentials are invalid. Reconnect Cloud and verify again.',
);
const verifiedAt = computed(() => {
    if (!props.project.verified_at) {
        return '';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(props.project.verified_at));
});

function verify(): void {
    form.post(store(props.project).url, {
        preserveScroll: true,
        onSuccess: (page) => {
            const project = page.props.project as
                { verification_status?: string } | undefined;

            if (project?.verification_status === 'verified') {
                toast.success('Verified against Laravel Cloud.');
            }
        },
    });
}
</script>

<template>
    <section class="border-t border-foreground p-5 sm:p-8">
        <div class="grid gap-8 lg:grid-cols-[.45fr_1.55fr]">
            <div>
                <p class="technical-label text-primary">Verification</p>
                <h2 class="display-type mt-4 text-4xl">Prove it.</h2>
            </div>

            <div class="max-w-2xl">
                <Alert
                    v-if="!hasConnection"
                    class="rounded-none border-foreground"
                >
                    <CloudOff class="size-4" />
                    <AlertTitle>Laravel Cloud is disconnected</AlertTitle>
                    <AlertDescription>
                        Connect Laravel Cloud to verify this project.
                    </AlertDescription>
                </Alert>

                <Alert
                    v-else-if="isVerified"
                    class="rounded-none border-foreground bg-secondary"
                >
                    <CheckCircle2 class="size-4" />
                    <AlertTitle>Verified</AlertTitle>
                    <AlertDescription>
                        Verified against
                        {{ project.connected_environment?.environment_name }} on
                        {{ verifiedAt }}.
                    </AlertDescription>
                </Alert>

                <Alert v-else variant="destructive" class="rounded-none">
                    <AlertTitle>{{
                        isFailed
                            ? 'Verification failed'
                            : isStale
                              ? 'Verification is stale'
                              : 'Verification required'
                    }}</AlertTitle>
                    <AlertDescription>
                        {{
                            isFailed || isStale
                                ? project.verification_failure_reason
                                : 'Select an environment, then verify the live URL.'
                        }}
                    </AlertDescription>
                </Alert>

                <form
                    v-if="hasConnection && !isVerified"
                    class="mt-6 grid gap-4"
                    @submit.prevent="verify"
                >
                    <Field>
                        <FieldLabel for="connected-environment"
                            >Laravel Cloud environment</FieldLabel
                        >
                        <Select v-model="form.connected_environment_id">
                            <SelectTrigger
                                id="connected-environment"
                                class="h-10 w-full rounded-none border-foreground"
                            >
                                <SelectValue
                                    placeholder="Select an environment"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="environment in environments"
                                    :key="environment.id"
                                    :value="String(environment.id)"
                                >
                                    {{ environment.application_name }} /
                                    {{ environment.environment_name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <FieldError v-if="form.errors.connected_environment_id">
                            {{ form.errors.connected_environment_id }}
                        </FieldError>
                    </Field>
                    <div class="flex flex-wrap gap-3">
                        <Button
                            type="submit"
                            :disabled="
                                form.processing ||
                                !form.connected_environment_id
                            "
                        >
                            <RefreshCw class="size-4" />
                            {{
                                isFailed || isStale
                                    ? 'Verify again'
                                    : 'Verify live URL'
                            }}
                        </Button>
                        <Button
                            v-if="requiresReconnect"
                            as-child
                            type="button"
                            variant="outline"
                        >
                            <Link :href="dashboard()">Reconnect Cloud</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</template>
