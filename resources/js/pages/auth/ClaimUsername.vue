<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { claim as claimUsername } from '@/routes/username';

const props = defineProps<{
    username: string;
}>();

defineOptions({
    layout: {
        title: 'Claim your username',
        description:
            'This is how creators will find you on Shipped — you can change it later from your profile settings',
    },
});

const form = useForm({
    username: props.username,
});

const submit = (): void => {
    form.patch(claimUsername.url(), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Claim your username" />

    <form class="flex flex-col gap-6" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="username">Username</Label>
            <Input
                id="username"
                v-model="form.username"
                type="text"
                required
                autofocus
                autocomplete="off"
                placeholder="username"
                pattern="[a-z0-9_]{3,30}"
                data-test="claim-username-input"
            />
            <p class="text-xs text-muted-foreground">
                Your public Shipped URL: /@{{ form.username || 'username' }}
                (lowercase letters, numbers, underscores).
            </p>
            <InputError :message="form.errors.username" />
        </div>

        <div class="grid gap-3">
            <Button
                type="submit"
                :disabled="form.processing"
                data-test="claim-username-button"
            >
                <Spinner v-if="form.processing" />
                Claim username
            </Button>
            <TextLink
                :href="dashboard()"
                class="text-center text-sm"
                data-test="skip-username-button"
            >
                Skip — keep {{ props.username }}
            </TextLink>
        </div>
    </form>
</template>
