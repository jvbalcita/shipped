<script setup lang="ts">
import { Form, Head, useForm } from '@inertiajs/vue3';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import type { Props as ManagePasskeysProps } from '@/components/ManagePasskeys.vue';
import ManagePasskeys from '@/components/ManagePasskeys.vue';
import type { Props as ManageTwoFactorProps } from '@/components/ManageTwoFactor.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Spinner } from '@/components/ui/spinner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { link as linkOauth, unlink as unlinkOauth } from '@/routes/oauth';
import { edit } from '@/routes/security';
import { update as updateEmail } from '@/routes/user-email';

type LinkedAccount = {
    provider: string;
    nickname: string | null;
};

type Props = {
    passwordRules: string;
    email: string;
    linkedAccounts: LinkedAccount[];
    oauthProviders: string[];
    hasPassword: boolean;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

const props = defineProps<Props>();

const emailForm = useForm({
    email: props.email,
});

const submitEmail = (): void => {
    emailForm.patch(updateEmail.url(), {
        preserveScroll: true,
        onSuccess: () => {
            emailForm.email = props.email;
        },
    });
};

const providerLabels: Record<string, string> = {
    github: 'GitHub',
    google: 'Google',
};

const providers = props.oauthProviders.map((key) => ({
    key,
    label: providerLabels[key] ?? key,
}));

const isLinked = (key: string): boolean =>
    props.linkedAccounts.some((account) => account.provider === key);

const nicknameFor = (key: string): string | null =>
    props.linkedAccounts.find((account) => account.provider === key)
        ?.nickname ?? null;

const linkForm = useForm({});

const linkProvider = (key: string): void => {
    linkForm.post(linkOauth.url({ provider: key }), {
        preserveScroll: true,
    });
};

const unlinkForm = useForm({});

const unlinkProvider = (key: string): void => {
    unlinkForm.delete(unlinkOauth.url({ provider: key }), {
        preserveScroll: true,
    });
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Security settings',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Security settings" />

    <h1 class="sr-only">Security settings</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Email address"
            description="The email address used for sign-in and notifications"
        />

        <form class="space-y-6" @submit.prevent="submitEmail">
            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    v-model="emailForm.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autocomplete="username"
                    placeholder="Email address"
                    data-test="security-email"
                />
                <InputError class="mt-2" :message="emailForm.errors.email" />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    type="submit"
                    :disabled="emailForm.processing"
                    data-test="update-email-button"
                >
                    Save
                </Button>
            </div>
        </form>
    </div>

    <div class="mt-8 space-y-6 border-t border-foreground pt-6">
        <Heading
            variant="small"
            :title="props.hasPassword ? 'Update password' : 'Set a password'"
            :description="
                props.hasPassword
                    ? 'Ensure your account is using a long, random password to stay secure'
                    : 'Set a password to also sign in with email'
            "
        />

        <Form
            v-bind="SecurityController.update.form()"
            :options="{
                preserveScroll: true,
            }"
            reset-on-success
            :reset-on-error="[
                'password',
                'password_confirmation',
                'current_password',
            ]"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div v-if="props.hasPassword" class="grid gap-2">
                <Label for="current_password">Current password</Label>
                <PasswordInput
                    id="current_password"
                    name="current_password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                    placeholder="Current password"
                />
                <InputError :message="errors.current_password" />
            </div>

            <div class="grid gap-2">
                <Label for="password">New password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="New password"
                    :passwordrules="props.passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="Confirm password"
                    :passwordrules="props.passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing"
                    data-test="update-password-button"
                >
                    Save
                </Button>
            </div>
        </Form>
    </div>

    <div class="mt-8 space-y-4 border-t border-foreground pt-6">
        <Heading
            variant="small"
            title="Connected providers"
            description="Link a Google or GitHub account to sign in without a password"
        />

        <div class="grid gap-3">
            <div
                v-for="provider in providers"
                :key="provider.key"
                class="flex items-center justify-between border border-foreground p-3"
            >
                <div class="flex min-w-0 items-center gap-2">
                    <span class="font-medium">{{ provider.label }}</span>
                    <span
                        v-if="nicknameFor(provider.key)"
                        class="truncate font-mono text-xs text-muted-foreground"
                        :data-test="`linked-${provider.key}-nickname`"
                    >
                        @{{ nicknameFor(provider.key) }}
                    </span>
                </div>
                <Button
                    v-if="isLinked(provider.key)"
                    variant="outline"
                    size="sm"
                    :disabled="unlinkForm.processing"
                    :data-test="`unlink-${provider.key}`"
                    @click="unlinkProvider(provider.key)"
                >
                    <Spinner v-if="unlinkForm.processing" />
                    Unlink
                </Button>
                <Button
                    v-else
                    variant="outline"
                    size="sm"
                    :disabled="linkForm.processing"
                    :data-test="`link-${provider.key}`"
                    @click="linkProvider(provider.key)"
                >
                    <Spinner v-if="linkForm.processing" />
                    Link
                </Button>
            </div>
        </div>
    </div>

    <ManageTwoFactor
        :canManageTwoFactor="canManageTwoFactor"
        :requiresConfirmation="requiresConfirmation"
        :twoFactorEnabled="twoFactorEnabled"
    />

    <ManagePasskeys
        :canManagePasskeys="canManagePasskeys"
        :passkeys="passkeys"
    />
</template>
