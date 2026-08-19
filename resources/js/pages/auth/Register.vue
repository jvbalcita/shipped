<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import AuthDivider from '@/components/AuthDivider.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { redirect as oauthRedirect } from '@/routes/oauth';
import { store } from '@/routes/register';

const props = defineProps<{
    passwordRules: string;
    oauthProviders: string[];
}>();

const providerLabels: Record<string, string> = {
    github: 'GitHub',
    google: 'Google',
};

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
});
</script>

<template>
    <Head title="Register" />

    <div v-if="props.oauthProviders.length > 0" class="grid gap-3">
        <a
            v-for="provider in props.oauthProviders"
            :key="provider"
            :href="oauthRedirect.url({ provider })"
            class="inline-flex h-9 w-full items-center justify-center gap-2 border border-foreground bg-background text-sm font-medium transition-colors hover:bg-muted"
            :data-test="`oauth-${provider}`"
        >
            Continue with {{ providerLabels[provider] ?? provider }}
        </a>
    </div>

    <AuthDivider
        v-if="props.oauthProviders.length > 0"
        label="Or register with email"
    />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Full name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="username">Username</Label>
                <Input
                    id="username"
                    type="text"
                    required
                    :tabindex="3"
                    name="username"
                    placeholder="jack"
                    pattern="[a-z0-9_]{3,30}"
                    autocomplete="username"
                />
                <p class="text-xs text-muted-foreground">
                    Used in your public Shipped URL (lowercase letters, numbers,
                    underscores).
                </p>
                <InputError :message="errors.username" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="5"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="6"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Create account
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink
                :href="login()"
                class="underline underline-offset-4"
                :tabindex="7"
                >Log in</TextLink
            >
        </div>
    </Form>
</template>
