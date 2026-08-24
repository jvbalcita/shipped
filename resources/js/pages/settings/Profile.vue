<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import FileUpload from '@/components/shipped/FileUpload.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    normalizeProfileLinkType,
    normalizeProfileLinkUrl,
    profileLinkOption,
    profileLinkOptions,
} from '@/lib/profileLinks';
import type {
    ProfileLinkType,
    StoredProfileLinkType,
} from '@/lib/profileLinks';
import { edit } from '@/routes/profile';
import { update as updateFeaturedProjects } from '@/routes/profile/featured-projects';
import { update as updateUsername } from '@/routes/username';
import { send } from '@/routes/verification';
import type { ProfileProject } from '@/types/creator';

type ProfileLink = { type: ProfileLinkType; url: string };
type StoredProfileLink = { type: StoredProfileLinkType; url: string };

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
const profileProjects = computed(
    () => (page.props.profileProjects as ProfileProject[] | undefined) ?? [],
);

const initialLinks = (
    (user.value.links as StoredProfileLink[] | null | undefined) ?? []
).map((link): ProfileLink => {
    const type = normalizeProfileLinkType(link.type);

    return {
        type,
        url: normalizeProfileLinkUrl(link.type, link.url),
    };
});

const linkTypeMemory = ref<ProfileLinkType[]>(
    initialLinks.map((link) => link.type),
);

const form = useForm({
    name: user.value.name,
    title: (user.value.title as string) || 'Creator',
    location: (user.value.location as string) || '',
    bio: (user.value.bio as string) || '',
    links:
        initialLinks.length > 0
            ? initialLinks.map((link) => ({ ...link }))
            : ([] as ProfileLink[]),
    avatar: null as File | null,
    avatar_removal: false as boolean,
});

const addLink = (): void => {
    if (form.links.length >= 8) {
        return;
    }

    const type: ProfileLinkType = 'website';

    form.links.push({ type, url: profileLinkOption(type).prefix });
    linkTypeMemory.value.push(type);
};

const removeLink = (index: number): void => {
    form.links.splice(index, 1);
    linkTypeMemory.value.splice(index, 1);
};

const updateLinkType = (index: number, type: unknown): void => {
    const link = form.links[index];

    if (!link || typeof type !== 'string' || !type) {
        return;
    }

    const nextType = normalizeProfileLinkType(type);
    const previousType = linkTypeMemory.value[index] ?? 'website';
    const previousPrefix = profileLinkOption(previousType).prefix;

    if (!link.url.trim() || link.url.trim() === previousPrefix) {
        link.url = profileLinkOption(nextType).prefix;
    }

    link.type = nextType;
    linkTypeMemory.value[index] = nextType;
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
    form.transform((data) => ({
        ...data,
        _method: 'patch',
    })).post(ProfileController.update.url(), {
        forceFormData: true,
        preserveScroll: true,
    });
};

const usernameForm = useForm({
    username: user.value.username as string,
});

const featuredProjectsForm = useForm({
    project_ids: profileProjects.value
        .filter(
            (project) =>
                project.is_discoverable &&
                project.profile_featured_order !== null,
        )
        .sort(
            (left, right) =>
                (left.profile_featured_order ?? 0) -
                (right.profile_featured_order ?? 0),
        )
        .map((project) => project.id),
});

const isFeaturedProject = (projectId: number): boolean =>
    featuredProjectsForm.project_ids.includes(projectId);

const preservedFeaturedOrders = computed(
    () =>
        new Set(
            profileProjects.value
                .filter(
                    (project) =>
                        !project.is_discoverable &&
                        project.profile_featured_order !== null,
                )
                .map((project) => project.profile_featured_order),
        ),
);

const featuredProjectPosition = (projectId: number): number => {
    let order = 1;

    for (const selectedProjectId of featuredProjectsForm.project_ids) {
        while (preservedFeaturedOrders.value.has(order)) {
            order++;
        }

        if (selectedProjectId === projectId) {
            return order;
        }

        order++;
    }

    return 0;
};

const toggleFeaturedProject = (project: ProfileProject): void => {
    const position = featuredProjectsForm.project_ids.indexOf(project.id);

    if (position !== -1) {
        featuredProjectsForm.project_ids.splice(position, 1);

        return;
    }

    if (
        !project.is_discoverable ||
        featuredProjectsForm.project_ids.length >= 3
    ) {
        return;
    }

    featuredProjectsForm.project_ids.push(project.id);
};

const saveFeaturedProjects = (): void => {
    featuredProjectsForm.put(updateFeaturedProjects.url(), {
        preserveScroll: true,
    });
};

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
    <div>
        <Head title="Profile settings" />

        <h1 class="sr-only">Profile settings</h1>

        <div class="flex flex-col space-y-6">
            <Heading
                variant="small"
                title="Profile"
                description="Update your public identity"
            />

            <form
                class="space-y-6"
                data-test="profile-details-form"
                @submit.prevent="submit"
            >
                <div class="grid gap-2">
                    <Label>Avatar</Label>
                    <FileUpload
                        :model-value="form.avatar"
                        kind="avatar"
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
                    <div
                        class="flex flex-wrap items-start justify-between gap-3"
                    >
                        <div>
                            <Label>Links</Label>
                            <p
                                class="mt-1 max-w-2xl text-sm leading-6 text-muted-foreground"
                            >
                                Add public profiles where people can find your
                                work. They will appear on your Shipping Profile.
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="min-h-11"
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
                        class="grid gap-4 border border-foreground p-4 sm:grid-cols-[12rem_minmax(0,1fr)_auto] sm:items-start"
                    >
                        <div class="grid gap-2">
                            <Label :for="`link-type-${index}`">Platform</Label>
                            <Select
                                :model-value="link.type"
                                @update:model-value="
                                    updateLinkType(index, $event)
                                "
                            >
                                <SelectTrigger
                                    :id="`link-type-${index}`"
                                    class="h-11 w-full rounded-none border-foreground"
                                    :aria-describedby="`link-type-help-${index}`"
                                    data-test="profile-link-type"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in profileLinkOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                :id="`link-type-help-${index}`"
                                class="text-xs leading-5 text-muted-foreground"
                            >
                                {{ profileLinkOption(link.type).description }}
                            </p>
                            <InputError
                                :message="form.errors[`links.${index}.type`]"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`link-url-${index}`"
                                >Profile URL</Label
                            >
                            <Input
                                :id="`link-url-${index}`"
                                v-model="link.url"
                                :aria-describedby="`link-url-help-${index}`"
                                type="url"
                                :placeholder="
                                    profileLinkOption(link.type).placeholder
                                "
                                data-test="profile-link-url"
                            />
                            <p
                                :id="`link-url-help-${index}`"
                                class="text-xs leading-5 text-muted-foreground"
                            >
                                Starts with
                                <span class="font-mono">
                                    {{ profileLinkOption(link.type).prefix }}
                                </span>
                                Add your username or profile slug after it.
                            </p>
                            <InputError
                                :message="form.errors[`links.${index}.url`]"
                            />
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="min-h-11"
                            data-test="profile-remove-link"
                            @click="removeLink(index)"
                        >
                            Remove
                        </Button>
                    </div>
                    <InputError class="mt-2" :message="form.errors.links" />
                </div>

                <div
                    v-if="page.props.mustVerifyEmail && !user.email_verified_at"
                    class="border-t border-foreground pt-6"
                >
                    <p class="text-sm text-muted-foreground">
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
                        A new verification link has been sent to your email
                        address.
                    </div>
                </div>

                <div
                    class="flex items-center gap-4 border-t border-foreground pt-6"
                >
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        data-test="update-profile-button"
                    >
                        {{
                            form.processing ? 'Saving profile…' : 'Save profile'
                        }}
                    </Button>
                </div>
            </form>

            <form
                class="space-y-4 border-t border-foreground pt-6"
                data-test="featured-projects-form"
                @submit.prevent="saveFeaturedProjects"
            >
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <Heading
                        variant="small"
                        title="Shipping profile"
                        description="Select up to 3 public projects in the order you want them shown."
                    />
                    <span class="technical-label text-muted-foreground">
                        {{ featuredProjectsForm.project_ids.length }} / 3
                        selected
                    </span>
                </div>

                <div
                    v-if="profileProjects.length"
                    class="grid gap-2"
                    data-test="featured-projects-settings"
                >
                    <button
                        v-for="project in profileProjects"
                        :key="project.id"
                        type="button"
                        class="flex min-h-11 items-center justify-between gap-4 border border-foreground p-3 text-left transition-colors hover:bg-secondary disabled:cursor-not-allowed disabled:opacity-60"
                        :class="{
                            'bg-secondary': isFeaturedProject(project.id),
                        }"
                        :disabled="
                            (!project.is_discoverable &&
                                !isFeaturedProject(project.id)) ||
                            (!isFeaturedProject(project.id) &&
                                featuredProjectsForm.project_ids.length >= 3)
                        "
                        :aria-checked="isFeaturedProject(project.id)"
                        role="checkbox"
                        @click="toggleFeaturedProject(project)"
                    >
                        <span class="min-w-0">
                            <span class="block truncate font-semibold">
                                {{ project.name }}
                            </span>
                            <span
                                class="technical-label mt-1 block text-muted-foreground"
                            >
                                {{
                                    project.is_discoverable
                                        ? project.category?.name || 'Project'
                                        : 'Currently hidden from public profile'
                                }}
                            </span>
                        </span>
                        <span
                            class="grid size-7 shrink-0 place-items-center border border-foreground font-mono text-sm"
                            :class="{
                                'bg-primary text-primary-foreground':
                                    isFeaturedProject(project.id),
                            }"
                        >
                            {{
                                isFeaturedProject(project.id)
                                    ? featuredProjectPosition(project.id)
                                    : (project.profile_featured_order ?? '—')
                            }}
                        </span>
                    </button>
                </div>
                <p
                    v-else
                    class="border border-dashed border-foreground p-4 text-sm text-muted-foreground"
                >
                    Ship and verify a public project with an approved Ship Story
                    to feature it here.
                </p>
                <InputError
                    class="mt-1"
                    :message="featuredProjectsForm.errors.project_ids"
                />
                <div>
                    <Button
                        type="submit"
                        variant="outline"
                        :disabled="featuredProjectsForm.processing"
                        data-test="save-featured-projects"
                    >
                        {{
                            featuredProjectsForm.processing
                                ? 'Saving featured projects…'
                                : 'Save featured projects'
                        }}
                    </Button>
                </div>
            </form>

            <form
                class="mt-8 space-y-4 border-t border-foreground pt-6"
                @submit.prevent="submitUsername"
            >
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
                    <InputError
                        class="mt-2"
                        :message="usernameForm.errors.username"
                    />
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
    </div>
</template>
