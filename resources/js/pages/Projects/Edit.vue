<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { CalendarClock, Check, Eye, Send } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import DateTimePicker from '@/components/shipped/DateTimePicker.vue';
import FileUpload from '@/components/shipped/FileUpload.vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import VerificationPanel from '@/components/shipped/VerificationPanel.vue';
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
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { update } from '@/routes/projects';
import { store as releaseStore } from '@/routes/projects/releases';
import visibility from '@/routes/projects/visibility';
import type { ConnectedEnvironmentSummary } from '@/types';

const props = defineProps<{
    project: any;
    categories: { id: number; name: string }[];
    connectedEnvironments: ConnectedEnvironmentSummary[];
}>();
const projectForm = useForm({
    name: props.project.name,
    tagline: props.project.tagline,
    description: props.project.description,
    category_id: String(props.project.category_id),
    live_url: props.project.live_url ?? '',
    cover_image: null as File | null,
});
const releaseForm = useForm({
    title: '',
    notes: '',
    timing: 'now',
    published_at: '',
});
const confirmPublish = ref(false);
const isScheduledTimeValid = ref(false);
const isScheduled = computed(() => releaseForm.timing === 'schedule');
const hasPublishedRelease = computed(() =>
    props.project.releases.some(
        (release: { published_at: string | null }) =>
            release.published_at !== null &&
            new Date(release.published_at) <= new Date(),
    ),
);

function saveProject(): void {
    projectForm.patch(update(props.project).url, {
        forceFormData: true,
        preserveScroll: true,
    });
}
function submitRelease(): void {
    if (isScheduled.value && !releaseForm.published_at) {
        releaseForm.setError(
            'published_at',
            'Choose a future publication date and time.',
        );

        return;
    }

    if (
        isScheduled.value &&
        (!isScheduledTimeValid.value ||
            new Date(releaseForm.published_at) <= new Date())
    ) {
        releaseForm.setError(
            'published_at',
            'Choose a publication time after the current moment.',
        );

        return;
    }

    releaseForm.clearErrors('published_at');
    releaseForm.post(releaseStore(props.project).url, {
        preserveScroll: true,
        onSuccess: () => {
            releaseForm.reset();
            isScheduledTimeValid.value = false;
        },
    });
}

function updateScheduledTimeValidity(isValid: boolean): void {
    isScheduledTimeValid.value = isValid;

    if (isValid) {
        releaseForm.clearErrors('published_at');
    }
}

watch(isScheduled, (isScheduling) => {
    if (!isScheduling) {
        releaseForm.clearErrors('published_at');
    }
});
function publishProject(): void {
    router.patch(
        visibility.update(props.project).url,
        { is_public: true },
        {
            preserveScroll: true,
            onFinish: () => (confirmPublish.value = false),
        },
    );
}
</script>

<template>
    <PublicShell :title="`Studio: ${project.name}`">
        <section
            class="page-enter mx-auto w-full max-w-[90rem] min-w-0 border-x border-b border-foreground"
        >
            <div
                class="grid border-b border-foreground p-5 sm:grid-cols-[.45fr_1.55fr] sm:p-8"
            >
                <p class="technical-label text-primary">
                    Creator studio /
                    {{ project.is_public ? 'Public record' : 'Private draft' }}
                </p>
                <div>
                    <h1
                        class="display-type mt-12 text-[clamp(3rem,7vw,7rem)] sm:mt-0"
                    >
                        {{ project.name }}
                    </h1>
                    <p class="mt-6 text-muted-foreground">
                        Shape the identity, then write a release people can
                        discover.
                    </p>
                </div>
            </div>
            <div class="grid gap-px bg-foreground xl:grid-cols-[1.1fr_.9fr]">
                <section class="bg-background p-5 sm:p-8">
                    <p class="technical-label text-primary">Project record</p>
                    <form
                        novalidate
                        class="mt-8 grid gap-6"
                        @submit.prevent="saveProject"
                    >
                        <Field
                            ><FieldLabel for="name">Project name</FieldLabel
                            ><Input
                                id="name"
                                v-model="projectForm.name"
                                required
                            /><FieldError v-if="projectForm.errors.name">{{
                                projectForm.errors.name
                            }}</FieldError></Field
                        ><Field
                            ><FieldLabel for="tagline"
                                >One-line description</FieldLabel
                            ><Input
                                id="tagline"
                                v-model="projectForm.tagline"
                                required /></Field
                        ><Field
                            ><FieldLabel for="description"
                                >The fuller story</FieldLabel
                            ><Textarea
                                id="description"
                                v-model="projectForm.description"
                                required /></Field
                        ><Field
                            ><FieldLabel for="category">Category</FieldLabel
                            ><Select v-model="projectForm.category_id"
                                ><SelectTrigger
                                    id="category"
                                    class="h-10 w-full rounded-none border-foreground"
                                    ><SelectValue /></SelectTrigger
                                ><SelectContent
                                    ><SelectItem
                                        v-for="category in categories"
                                        :key="category.id"
                                        :value="String(category.id)"
                                        >{{ category.name }}</SelectItem
                                    ></SelectContent
                                ></Select
                            ></Field
                        ><Field
                            ><FieldLabel for="live_url">Live URL</FieldLabel
                            ><Input
                                id="live_url"
                                v-model="projectForm.live_url"
                                type="url"
                                placeholder="https://your-project.com"
                            /><FieldError v-if="projectForm.errors.live_url">{{
                                projectForm.errors.live_url
                            }}</FieldError></Field
                        ><Field
                            ><FieldLabel>Cover image</FieldLabel
                            ><FileUpload
                                v-model="projectForm.cover_image"
                                :existing-url="
                                    project.cover_image_url
                                        ? project.cover_image_url
                                        : null
                                "
                                :error="
                                    projectForm.errors.cover_image
                                " /></Field
                        ><Button
                            type="submit"
                            :disabled="projectForm.processing"
                            >Save project record</Button
                        >
                    </form>
                </section>
                <section class="bg-secondary p-5 sm:p-8">
                    <p class="technical-label text-primary">Release station</p>
                    <h2 class="display-type mt-5 text-4xl">Make it real.</h2>
                    <form
                        novalidate
                        class="mt-8 grid gap-5"
                        @submit.prevent="submitRelease"
                    >
                        <Field
                            ><FieldLabel for="release_title"
                                >Release title</FieldLabel
                            ><Input
                                id="release_title"
                                v-model="releaseForm.title"
                                required
                                placeholder="What changed?"
                            /><FieldError v-if="releaseForm.errors.title">{{
                                releaseForm.errors.title
                            }}</FieldError></Field
                        ><Field
                            ><FieldLabel for="release_notes"
                                >Release notes</FieldLabel
                            ><Textarea
                                id="release_notes"
                                v-model="releaseForm.notes"
                                required
                                placeholder="Say what changed, why it matters, and where to try it."
                            /><FieldError v-if="releaseForm.errors.notes">{{
                                releaseForm.errors.notes
                            }}</FieldError></Field
                        ><Field
                            ><FieldLabel>Timing</FieldLabel
                            ><RadioGroup
                                v-model="releaseForm.timing"
                                class="grid gap-px border border-foreground bg-foreground sm:grid-cols-2"
                                ><label
                                    class="flex cursor-pointer items-center gap-3 bg-background p-4"
                                    ><RadioGroupItem value="now" /><span
                                        ><span class="technical-label">Now</span
                                        ><span class="mt-1 block text-sm"
                                            >Publish immediately</span
                                        ></span
                                    ></label
                                ><label
                                    class="flex cursor-pointer items-center gap-3 bg-background p-4"
                                    ><RadioGroupItem value="schedule" /><span
                                        ><span class="technical-label"
                                            >Schedule</span
                                        ><span class="mt-1 block text-sm"
                                            >Choose a future time</span
                                        ></span
                                    ></label
                                ></RadioGroup
                            ></Field
                        ><Field v-if="isScheduled"
                            ><FieldLabel for="published_at"
                                >Publication date and time</FieldLabel
                            ><DateTimePicker
                                id="published_at"
                                v-model="releaseForm.published_at"
                                @validity-change="updateScheduledTimeValidity"
                            />
                            <p class="mt-2 text-sm text-muted-foreground">
                                Pick a future moment. Your local time is sent to
                                Shipped and validated again by the server.
                            </p>
                            <FieldError
                                v-if="releaseForm.errors.published_at"
                                >{{
                                    releaseForm.errors.published_at
                                }}</FieldError
                            ></Field
                        ><Button
                            type="submit"
                            :disabled="
                                releaseForm.processing ||
                                (isScheduled && !isScheduledTimeValid)
                            "
                            ><CalendarClock
                                v-if="isScheduled"
                                class="size-4"
                            /><Send v-else class="size-4" />{{
                                isScheduled
                                    ? 'Schedule release'
                                    : 'Publish release now'
                            }}</Button
                        >
                    </form>
                    <Alert class="mt-8 border-foreground"
                        ><AlertTitle>Public visibility is deliberate</AlertTitle
                        ><AlertDescription
                            >Create a published release first, then confirm the
                            public project record below.</AlertDescription
                        ></Alert
                    ><AlertDialog v-model:open="confirmPublish"
                        ><AlertDialogTrigger as-child
                            ><Button
                                class="mt-6 w-full"
                                variant="outline"
                                :disabled="
                                    !hasPublishedRelease ||
                                    project.verification_status !== 'verified'
                                "
                                ><Eye class="size-4" />{{
                                    project.is_public
                                        ? 'Project is public'
                                        : 'Publish project'
                                }}</Button
                            ></AlertDialogTrigger
                        ><AlertDialogContent
                            class="rounded-none border-2 border-foreground"
                            ><AlertDialogHeader
                                ><AlertDialogTitle
                                    >Publish this project?</AlertDialogTitle
                                ><AlertDialogDescription
                                    >It will appear in the public Shipped
                                    registry with its published
                                    releases.</AlertDialogDescription
                                ></AlertDialogHeader
                            ><AlertDialogFooter
                                ><AlertDialogCancel
                                    >Keep private</AlertDialogCancel
                                ><AlertDialogAction @click="publishProject"
                                    ><Check class="size-4" /> Publish
                                    publicly</AlertDialogAction
                                ></AlertDialogFooter
                            ></AlertDialogContent
                        ></AlertDialog
                    >
                </section>
            </div>
            <VerificationPanel
                :project="project"
                :environments="connectedEnvironments"
            />
            <section class="border-t border-foreground">
                <div class="grid p-5 sm:grid-cols-[.45fr_1.55fr] sm:p-8">
                    <p class="technical-label text-primary">Release archive</p>
                    <ScrollArea
                        class="mt-8 max-h-96 border border-foreground sm:mt-0"
                        ><ol class="divide-y divide-foreground">
                            <li
                                v-for="release in project.releases"
                                :key="release.id"
                                class="p-4"
                            >
                                <p
                                    class="technical-label text-muted-foreground"
                                >
                                    {{
                                        release.published_at
                                            ? new Date(
                                                  release.published_at,
                                              ).toLocaleString()
                                            : 'Private draft'
                                    }}
                                </p>
                                <h3 class="mt-2 font-semibold">
                                    {{ release.title }}
                                </h3>
                                <p
                                    class="mt-2 text-sm leading-6 whitespace-pre-line"
                                >
                                    {{ release.notes }}
                                </p>
                            </li>
                            <li
                                v-if="!project.releases.length"
                                class="p-4 text-sm text-muted-foreground"
                            >
                                No release story yet.
                            </li>
                        </ol></ScrollArea
                    >
                </div>
            </section>
        </section>
    </PublicShell>
</template>
