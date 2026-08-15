<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import FileUpload from '@/components/shipped/FileUpload.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { update as updateUsername } from '@/routes/username';
import { send } from '@/routes/verification';

type ProfileLink = { type: string; url: string };

const linkTypes = ['website', 'github', 'twitter', 'linkedin'] as const;

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const initialLinks =
    (user.value.links as ProfileLink[] | null | undefined) ?? [];

const form = useForm({
    name: user.value.name,
    title: (user.value.title as string) || 'Creator',
    location: (user.value.location as string) || '',
    bio: (user.value.bio as string) || '',
    links:
        initialLinks.length > 0
            ? initialLinks.map((link) => ({ type: link.type, url: link.url }))
            : ([] as ProfileLink[]),
    avatar: null as File | null,
    avatar_removal: false as boolean,
});

const addLink = (): void => {
    if (form.links.length >= 8) {
        return;
    }

    form.links.push({ type: 'website', url: '' });
};

const removeLink = (index: number): void => {
    form.links.splice(index, 1);
};

const onAvatarChange = (file: File | null): void => {
    form.avatar = file;
    if (file) {
        form.avatar_removal = false;
    }
};

const onAvatarRemove = (): void => {
    form.avatar_removal = true;
    form.avatar = null;
};

const submit = (): void => {
    form
        .transform((data) => ({
            ...data,
            _method: 'patch',
        }))
        .post(ProfileController.update.url(), {
            forceFormData: true,
            preserveScroll: true,
        });
};

const usernameForm = useForm({
    username: user.value.username as string,
});

const submitUsername = (): void => {
    usernameForm.patch(updateUsername.url(), {
        preserveScroll: true,
        onSuccess: () => {
            usernameForm.username = user.value.username as string;
        },
    });
};
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Profile"
            description="Update your public identity"
        />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label>Avatar</Label>
                <FileUpload
                    :model-value="form.avatar"
                    :existing-url="(user.avatar as string) || null"
                    :error="form.errors.avatar"
                    data-test="profile-avatar"
                    @update:model-value="onAvatarChange"
                    @remove-existing="onAvatarRemove"
                />
                <InputError class="mt-2" :message="form.errors.avatar" />
            </div>

            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    class="mt-1 block w-full"
                    required
                    autocomplete="name"
                    placeholder="Full name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="title">Title</Label>
                <Input
                    id="title"
                    v-model="form.title"
                    class="mt-1 block w-full"
                    required
                    maxlength="50"
                    placeholder="Creator"
                    data-test="profile-title"
                />
                <InputError class="mt-2" :message="form.errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="location">Location</Label>
                <Input
                    id="location"
                    v-model="form.location"
                    class="mt-1 block w-full"
                    maxlength="80"
                    placeholder="Berlin, DE"
                    data-test="profile-location"
                />
                <InputError class="mt-2" :message="form.errors.location" />
            </div>

            <div class="grid gap-2">
                <Label for="bio">Bio</Label>
                <textarea
                    id="bio"
                    v-model="form.bio"
                    class="mt-1 block w-full border border-foreground bg-background p-3 text-sm"
                    maxlength="280"
                    rows="4"
                    placeholder="A short bio shown on your creator page"
                    data-test="profile-bio"
                />
                <InputError class="mt-2" :message="form.errors.bio" />
            </div>

            <div class="grid gap-3">
                <div class="flex items-center justify-between gap-3">
                    <Label>Links</Label>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        data-test="profile-add-link"
                        :disabled="form.links.length >= 8"
                        @click="addLink"
                    >
                        Add link
                    </Button>
                </div>

                <div
                    v-for="(link, index) in form.links"
                    :key="index"
                    class="grid gap-2 border border-foreground p-3 sm:grid-cols-[10rem_1fr_auto]"
                >
                    <div class="grid gap-2">
                        <Label :for="`link-type-${index}`" class="sr-only"
                            >Type</Label
                        >
                        <select
                            :id="`link-type-${index}`"
                            v-model="link.type"
                            class="h-9 border border-foreground bg-background px-3 font-mono text-xs uppercase"
                            data-test="profile-link-type"
                        >
                            <option
                                v-for="type in linkTypes"
                                :key="type"
                                :value="type"
                            >
                                {{ type }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label :for="`link-url-${index}`" class="sr-only"
                            >URL</Label
                        >
                        <Input
                            :id="`link-url-${index}`"
                            v-model="link.url"
                            type="url"
                            placeholder="https://"
                            data-test="profile-link-url"
                        />
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        data-test="profile-remove-link"
                        @click="removeLink(index)"
                    >
                        Remove
                    </Button>
                </div>
                <InputError class="mt-2" :message="form.errors.links" />
            </div>

            <div v-if="page.props.mustVerifyEmail && !user.email_verified_at">
                <p class="-mt-4 text-sm text-muted-foreground">
                    Your email address is unverified.
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-if="page.props.status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button
                    type="submit"
                    :disabled="form.processing"
                    data-test="update-profile-button"
                    >Save</Button
                >
            </div>
        </form>

        <form class="mt-8 space-y-4 border-t border-foreground pt-6" @submit.prevent="submitUsername">
            <Heading
                variant="small"
                title="Username"
                description="Changing your username holds the old one for 30 days."
            />

            <div class="grid gap-2">
                <Label for="username">Username</Label>
                <Input
                    id="username"
                    v-model="usernameForm.username"
                    class="mt-1 block w-full font-mono"
                    required
                    autocomplete="off"
                    maxlength="30"
                    placeholder="username"
                    data-test="profile-username"
                />
                <InputError class="mt-2" :message="usernameForm.errors.username" />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    type="submit"
                    :disabled="usernameForm.processing"
                    data-test="update-username-button"
                    >Change username</Button
                >
            </div>
        </form>
    </div>

    <div class="mt-8 border-t border-foreground pt-6">
        <DeleteUser />
    </div>
</template>
