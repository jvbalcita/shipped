<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { CalendarClock, Check, Eye, EyeOff, Send } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
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
import { Spinner } from '@/components/ui/spinner';
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
    cover_removal: false as boolean,
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

function onCoverChange(file: File | null): void {
    projectForm.cover_image = file;
    // A fresh upload supersedes any pending removal of the old cover.
    if (file) {
        projectForm.cover_removal = false;
    }
}

function onCoverRemove(): void {
    projectForm.cover_removal = true;
}

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
const publishing = ref(false);
const withdrawing = ref(false);
const confirmWithdraw = ref(false);

function publishProject(): void {
    publishing.value = true;
    router.patch(
        visibility.update(props.project).url,
        { is_public: true },
        {
            preserveScroll: true,
            onFinish: () => {
                confirmPublish.value = false;
                publishing.value = false;
            },
        },
    );
}

function withdrawProject(): void {
    withdrawing.value = true;
    router.patch(
        visibility.update(props.project).url,
        { is_public: false },
        {
            preserveScroll: true,
            onFinish: () => {
                confirmWithdraw.value = false;
                withdrawing.value = false;
            },
        },
    );
}

// The "FILED" moment: when the server flashes a filed payload on first publish,
// slam a stamp over the studio for ~3s, then dismiss.
const filedSerial = ref<string | null>(null);
let filedTimeout: ReturnType<typeof setTimeout> | null = null;

function handleFiled(event: Event): void {
    const detail = (event as CustomEvent).detail as {
        filed_serial?: string;
    } | undefined;
    if (!detail?.filed_serial) {
        return;
    }

    filedSerial.value = detail.filed_serial;
    if (filedTimeout) {
        clearTimeout(filedTimeout);
    }
    filedTimeout = setTimeout(() => {
        filedSerial.value = null;
    }, 3000);
}

onMounted(() => window.addEventListener('shipped:filed', handleFiled));
onUnmounted(() => {
    window.removeEventListener('shipped:filed', handleFiled);
    if (filedTimeout) {
        clearTimeout(filedTimeout);
    }
});
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
                                    :model-value="projectForm.cover_image"
                                    :existing-url="
                                        project.cover_image_url
                                            ? project.cover_image_url
                                            : null
                                    "
                                    :error="projectForm.errors.cover_image"
                                    @update:model-value="onCoverChange"
                                    @remove-existing="onCoverRemove" /></Field
                        ><Button
                            type="submit"
                            :disabled="projectForm.processing"
                            ><Spinner v-if="projectForm.processing" />Save
                            project record</Button
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
                            ><Spinner v-if="releaseForm.processing" /><CalendarClock
                                v-else-if="isScheduled"
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
                    ><AlertDialog v-if="!project.is_public" v-model:open="confirmPublish"
                        ><AlertDialogTrigger as-child
                            ><Button
                                class="mt-6 w-full"
                                variant="outline"
                                :disabled="
                                    publishing ||
                                    !hasPublishedRelease ||
                                    project.verification_status !== 'verified'
                                "
                                ><Eye class="size-4" />Publish
                                project</Button
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
                                    :disabled="publishing"
                                    >Keep private</AlertDialogCancel
                                ><AlertDialogAction
                                    :disabled="publishing"
                                    @click="publishProject"
                                    ><Spinner v-if="publishing" /><Check
                                        v-else
                                        class="size-4" />
                                    Publish publicly</AlertDialogAction
                                ></AlertDialogFooter
                            ></AlertDialogContent
                        ></AlertDialog
                    ><AlertDialog v-else v-model:open="confirmWithdraw"
                        ><AlertDialogTrigger as-child
                            ><Button
                                class="mt-6 w-full"
                                variant="ghost"
                                :disabled="withdrawing"
                                ><EyeOff class="size-4" />Withdraw from
                                public</Button
                            ></AlertDialogTrigger
                        ><AlertDialogContent
                            class="rounded-none border-2 border-foreground"
                            ><AlertDialogHeader
                                ><AlertDialogTitle
                                    >Withdraw this project?</AlertDialogTitle
                                ><AlertDialogDescription
                                    >It will be removed from the public
                                    registry. Its dispatch number stays on the
                                    record, and you can republish
                                    anytime.</AlertDialogDescription
                                ></AlertDialogHeader
                            ><AlertDialogFooter
                                ><AlertDialogCancel :disabled="withdrawing"
                                    >Keep public</AlertDialogCancel
                                ><AlertDialogAction
                                    :disabled="withdrawing"
                                    @click="withdrawProject"
                                    ><Spinner v-if="withdrawing" /><Check
                                        v-else
                                        class="size-4" />
                                    Withdraw from public</AlertDialogAction
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
    <Teleport to="body">
        <div
            v-if="filedSerial"
            class="fixed inset-0 z-50 flex items-center justify-center bg-foreground/80 p-6 motion-safe:animate-[shipped-page-enter_0.2s_ease-out]"
            role="dialog"
            aria-modal="true"
            aria-label="Launch filed"
            @click="filedSerial = null"
        >
            <div
                class="filed-stamp flex flex-col items-center gap-6 border-[6px] border-primary bg-background px-12 py-10 text-center sm:px-20 sm:py-14"
            >
                <p class="technical-label text-primary">Launch filed</p>
                <p
                    class="display-type text-[clamp(3.5rem,12vw,7rem)] leading-[0.82] text-primary"
                >
                    FILED
                </p>
                <p
                    class="technical-label tabular-nums text-muted-foreground"
                >
                    {{ filedSerial }}
                </p>
            </div>
        </div>
    </Teleport>
</template>
