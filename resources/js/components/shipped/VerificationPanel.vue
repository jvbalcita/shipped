<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { CheckCircle2, RefreshCw } from '@lucide/vue';
import { computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { store } from '@/routes/projects/verification';
import type { ProjectVerification, VerificationStatus } from '@/types';

const props = defineProps<{
    project: { slug: string } & ProjectVerification;
}>();

const form = useForm({
    laravel_cloud_url: props.project.laravel_cloud_url ?? '',
});

watch(
    () => props.project.laravel_cloud_url,
    (url) => {
        form.laravel_cloud_url = url ?? '';
    },
);

const isVerified = computed(
    () => props.project.verification_status === 'verified',
);
const isStale = computed(() => props.project.verification_status === 'stale');
const isFailed = computed(() => props.project.verification_status === 'failed');

const statusTitle = computed<string>(() => {
    if (isFailed.value) {
        return 'Verification failed';
    }

    if (isStale.value) {
        return 'Verification is stale';
    }

    return 'Verification required';
});

const statusDescription = computed<string>(() => {
    if (isFailed.value || isStale.value) {
        return (
            props.project.verification_failure_reason ??
            'The Laravel Cloud URL could not be verified. Try again.'
        );
    }

    return 'Paste the environment URL from Laravel Cloud, then verify the deployment.';
});

const submitLabel = computed<string>(() => {
    if (isVerified.value) {
        return 'Recheck URL';
    }

    return isFailed.value || isStale.value ? 'Verify again' : 'Verify URL';
});

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
                { verification_status?: VerificationStatus } | undefined;

            if (project?.verification_status === 'verified') {
                toast.success('Deployed on Laravel Cloud — URL verified.');
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
                    v-if="isVerified"
                    class="rounded-none border-foreground bg-secondary"
                    data-test="verification-state"
                >
                    <CheckCircle2 class="size-4" />
                    <AlertTitle>Deployed on Laravel Cloud</AlertTitle>
                    <AlertDescription>
                        {{ project.laravel_cloud_url }} answered the latest
                        check{{ verifiedAt ? ` on ${verifiedAt}` : '' }}.
                        The probe checks the URL is up on Laravel Cloud; it is
                        not Cloud account ownership. Name match is a
                        consistency check, not proof of control.
                    </AlertDescription>
                </Alert>

                <Alert
                    v-else
                    variant="destructive"
                    class="rounded-none"
                    data-test="verification-state"
                >
                    <AlertTitle>{{ statusTitle }}</AlertTitle>
                    <AlertDescription>{{ statusDescription }}</AlertDescription>
                </Alert>

                <form class="mt-6 grid gap-4" @submit.prevent="verify">
                    <Field>
                        <FieldLabel for="laravel_cloud_url"
                            >Laravel Cloud environment URL</FieldLabel
                        >
                        <Input
                            id="laravel_cloud_url"
                            v-model="form.laravel_cloud_url"
                            type="url"
                            inputmode="url"
                            autocomplete="off"
                            spellcheck="false"
                            placeholder="https://your-app-main.laravel.cloud"
                            :disabled="form.processing"
                            :aria-invalid="
                                Boolean(form.errors.laravel_cloud_url)
                            "
                            data-test="cloud-url-input"
                            @update:model-value="
                                form.clearErrors('laravel_cloud_url')
                            "
                        />
                        <FieldError v-if="form.errors.laravel_cloud_url">
                            {{ form.errors.laravel_cloud_url }}
                        </FieldError>
                        <FieldDescription>
                            Copy the HTTPS URL from Laravel Cloud under Network
                            → Domains. Its project name must match the project's
                            Live URL name, even when Laravel Cloud adds a suffix.
                            The probe checks the URL is up on Laravel Cloud; it
                            is not Cloud account ownership. Name match is a
                            consistency check, not proof of control. Shipped
                            never asks for an API token.
                        </FieldDescription>
                    </Field>
                    <div class="flex flex-wrap gap-3">
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            :data-test="
                                isFailed || isStale
                                    ? 'verification-retry'
                                    : 'verify-cloud-url'
                            "
                        >
                            <RefreshCw
                                class="size-4"
                                :class="{ 'animate-spin': form.processing }"
                            />
                            {{ submitLabel }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</template>
