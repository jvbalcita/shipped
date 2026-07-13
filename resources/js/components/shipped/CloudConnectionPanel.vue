<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    CheckCircle2,
    CircleHelp,
    Cloud,
    LockKeyhole,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { destroy, store } from '@/routes/cloud-connection';
import type {
    CloudConnectionSummary,
    ConnectedEnvironmentSummary,
} from '@/types';

const props = defineProps<{
    connection: CloudConnectionSummary | null;
    environments: ConnectedEnvironmentSummary[];
}>();

const form = useForm({ api_token: '' });
const confirmDisconnect = ref(false);
const isConnected = computed(() => props.connection?.status === 'connected');
const connectionError = computed(() => {
    const errors = form.errors as Record<string, string | undefined>;

    return errors.cloud_connection;
});
const lastValidatedAt = computed(() => {
    if (
        props.connection?.last_validated_at === null ||
        props.connection === null
    ) {
        return 'Not yet validated';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(props.connection.last_validated_at));
});

function connect(): void {
    form.clearErrors();

    if (form.api_token.trim() === '') {
        form.setError('api_token', 'Enter a Laravel Cloud API token.');

        return;
    }

    form.post(store().url, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function disconnect(): void {
    form.delete(destroy().url, {
        preserveScroll: true,
        onFinish: () => (confirmDisconnect.value = false),
    });
}
</script>

<template>
    <section class="border-b border-foreground p-5 sm:p-8">
        <div class="grid min-w-0 gap-8 lg:grid-cols-[.45fr_1.55fr]">
            <div>
                <p class="technical-label text-primary">Cloud connection</p>
                <h2 class="display-type mt-4 text-4xl">Laravel Cloud.</h2>
            </div>

            <div class="max-w-2xl min-w-0">
                <Alert
                    v-if="!isConnected"
                    class="rounded-none border-foreground"
                >
                    <Cloud class="size-4" />
                    <AlertTitle>Connect your deployment evidence</AlertTitle>
                    <AlertDescription>
                        Create an API token in
                        <a
                            href="https://cloud.laravel.com/settings/api-tokens"
                            target="_blank"
                            rel="noreferrer"
                            class="underline underline-offset-4"
                            >Laravel Cloud</a
                        >, then paste it here. Shipped encrypts the token at
                        rest and never displays it again.
                    </AlertDescription>
                </Alert>

                <div
                    v-if="!isConnected"
                    class="mt-4 flex items-start gap-3 border-l-2 border-primary bg-secondary p-4 text-sm"
                >
                    <LockKeyhole class="mt-0.5 size-4 shrink-0 text-primary" />
                    <p class="min-w-0 break-words text-muted-foreground">
                        Shipped uses this token only for GET requests to inspect
                        your Laravel Cloud applications, environments, and
                        domains. Its actual permissions remain controlled by
                        Laravel Cloud.
                    </p>
                    <TooltipProvider :delay-duration="120">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-xs"
                                    class="shrink-0"
                                    aria-label="How Shipped uses your Laravel Cloud token"
                                >
                                    <CircleHelp class="size-3.5" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent
                                class="max-w-72 rounded-none border border-foreground text-left leading-relaxed"
                            >
                                Connecting a token does not give Shipped a new
                                permission level. Use the least-privileged
                                Laravel Cloud token available to you.
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>

                <Alert
                    v-else
                    class="rounded-none border-foreground bg-secondary"
                >
                    <CheckCircle2 class="size-4" />
                    <AlertTitle>Laravel Cloud is connected</AlertTitle>
                    <AlertDescription>
                        Last validated {{ lastValidatedAt }}.
                        {{ connection?.environment_count }} environments are
                        available for verification.
                    </AlertDescription>
                </Alert>
                <Alert
                    v-if="connectionError"
                    variant="destructive"
                    class="mt-4 rounded-none"
                >
                    <AlertTitle>Connection unavailable</AlertTitle>
                    <AlertDescription>
                        {{ connectionError }}
                    </AlertDescription>
                </Alert>

                <form
                    class="mt-6 grid gap-4"
                    novalidate
                    @submit.prevent="connect"
                >
                    <Field>
                        <FieldLabel for="cloud-api-token">
                            {{
                                isConnected ? 'Replace API token' : 'API token'
                            }}
                        </FieldLabel>
                        <Input
                            id="cloud-api-token"
                            v-model="form.api_token"
                            type="password"
                            autocomplete="off"
                            placeholder="Paste a Laravel Cloud API token"
                            :disabled="form.processing"
                            :aria-invalid="Boolean(form.errors.api_token)"
                            @update:model-value="form.clearErrors('api_token')"
                        />
                        <FieldError v-if="form.errors.api_token">
                            {{ form.errors.api_token }}
                        </FieldError>
                        <FieldDescription>
                            Encrypted at rest and never displayed after you
                            submit it. Shipped only uses GET requests to read
                            deployment details.
                        </FieldDescription>
                    </Field>
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center"
                    >
                        <Button
                            type="submit"
                            class="w-full sm:w-auto"
                            :disabled="form.processing"
                        >
                            {{
                                form.processing
                                    ? 'Connecting…'
                                    : isConnected
                                      ? 'Replace token'
                                      : 'Connect Laravel Cloud'
                            }}
                        </Button>

                        <AlertDialog
                            v-if="isConnected"
                            v-model:open="confirmDisconnect"
                        >
                            <AlertDialogTrigger as-child>
                                <Button
                                    variant="outline"
                                    type="button"
                                    class="w-full sm:w-auto"
                                >
                                    <Trash2 class="size-4" />Disconnect
                                </Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle>
                                        Disconnect Laravel Cloud?
                                    </AlertDialogTitle>
                                    <AlertDialogDescription>
                                        Connected projects will be made private
                                        and marked unverified. Projects and
                                        releases are kept.
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter>
                                    <AlertDialogCancel
                                        >Cancel</AlertDialogCancel
                                    >
                                    <AlertDialogAction
                                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                        :disabled="form.processing"
                                        @click="disconnect"
                                    >
                                        Disconnect
                                    </AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                    </div>
                </form>

                <div
                    v-if="isConnected"
                    class="mt-8 border-t border-foreground pt-5"
                >
                    <p class="technical-label text-primary">
                        Available environments
                    </p>
                    <ul
                        v-if="environments.length"
                        class="mt-4 grid gap-3 sm:grid-cols-2"
                    >
                        <li
                            v-for="environment in environments"
                            :key="environment.id"
                            class="border border-foreground p-4"
                        >
                            <p class="font-medium">
                                {{ environment.application_name }}
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ environment.environment_name }}
                                <template v-if="environment.domains.length">
                                    · {{ environment.domains.join(', ') }}
                                </template>
                            </p>
                        </li>
                    </ul>
                    <p v-else class="mt-4 text-sm text-muted-foreground">
                        No environments were returned for this token.
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
