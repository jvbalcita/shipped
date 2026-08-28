<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    CalendarClock,
    Check,
    Eye,
    EyeOff,
    ImagePlus,
    Send,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import BadgeSnippet from '@/components/shipped/BadgeSnippet.vue';
import DatePicker from '@/components/shipped/DatePicker.vue';
import DateTimePicker from '@/components/shipped/DateTimePicker.vue';
import FileUpload from '@/components/shipped/FileUpload.vue';
import GitHubRepoPicker from '@/components/shipped/GitHubRepoPicker.vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import RichTextEditor from '@/components/shipped/RichTextEditor.vue';
import SectionHeader from '@/components/shipped/SectionHeader.vue';
import StackObservationPanel from '@/components/shipped/StackObservationPanel.vue';
import TechnologyPicker from '@/components/shipped/TechnologyPicker.vue';
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
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { update } from '@/routes/projects';
import { show as launchKit } from '@/routes/projects/launch-kit';
import { store as releaseStore } from '@/routes/projects/releases';
import shipStoryRoutes from '@/routes/projects/ship-story';
import visibility from '@/routes/projects/visibility';
import type { TechnologyGroupOption } from '@/types/technology';

type ShipStoryData = {
    id: number;
    problem: string | null;
    audience: string | null;
    shipped: string | null;
    build_decisions: string | null;
    hardest_problem: string | null;
    lessons_learned: string | null;
    next: string | null;
    is_complete: boolean;
    is_approved: boolean;
    approved_at: string | null;
};

const props = defineProps<{
    project: any;
    shipStory: ShipStoryData | null;
    categories: { id: number; name: string }[];
    pricingOptions: { value: string; label: string }[];
    suggestedTags: string[];
    technologyOptions: TechnologyGroupOption[];
    declaredTechnologies: string[];
    stackObservation: {
        github_url: string | null;
        observed_at: string | null;
        observed_slugs: string[];
    };
    badgeMarkdown: string | null;
    githubLinked?: boolean;
    githubRepos?: { name: string; url: string }[] | null;
}>();
const projectForm = useForm({
    name: props.project.name,
    tagline: props.project.tagline,
    description: props.project.description,
    category_id: String(props.project.category_id),
    live_url: props.project.live_url ?? '',
    github_url: props.project.github_url ?? '',
    pricing: props.project.pricing ?? 'free',
    launch_date: props.project.launch_date
        ? String(props.project.launch_date).slice(0, 10)
        : '',
    tags: (props.project.tags ?? [])
        .map((tag: { name: string }) => tag.name)
        .join(', '),
    technologies: props.declaredTechnologies ?? [],
    cover_image: null as File | null,
    cover_removal: false as boolean,
    logo: null as File | null,
    logo_removal: false as boolean,
    screenshots: [] as File[],
    screenshots_captions: [] as string[],
    screenshot_order: [] as number[],
    screenshot_captions: {} as Record<number, string>,
    removed_screenshots: [] as number[],
});

function appendSuggestedTag(tag: string): void {
    const current = projectForm.tags
        .split(',')
        .map((value: string) => value.trim())
        .filter(Boolean);

    if (!current.includes(tag)) {
        current.push(tag);
        projectForm.tags = current.join(', ');
    }
}
const releaseForm = useForm({
    title: '',
    notes: '',
    timing: 'now',
    published_at: '',
});
const shipStoryForm = useForm({
    problem: props.shipStory?.problem ?? '',
    audience: props.shipStory?.audience ?? '',
    shipped: props.shipStory?.shipped ?? '',
    build_decisions: props.shipStory?.build_decisions ?? '',
    hardest_problem: props.shipStory?.hardest_problem ?? '',
    lessons_learned: props.shipStory?.lessons_learned ?? '',
    next: props.shipStory?.next ?? '',
    approve: false,
});
const confirmPublish = ref(false);
const isScheduledTimeValid = ref(false);
const isScheduled = computed(() => releaseForm.timing === 'schedule');
const shipStoryStatus = computed(() => {
    if (props.shipStory?.is_approved) {
        return 'Approved for public discovery';
    }

    if (props.shipStory) {
        return 'Private draft';
    }

    return 'Not started';
});
const hasApprovedShipStory = computed(
    () => props.shipStory?.is_approved === true,
);
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

function onLogoChange(file: File | null): void {
    projectForm.logo = file;

    if (file) {
        projectForm.logo_removal = false;
    }
}

function onLogoRemove(): void {
    projectForm.logo_removal = true;
}

function saveProject(): void {
    projectForm.screenshots = newScreenshots.value.map(
        (screenshot) => screenshot.file,
    );
    projectForm.screenshots_captions = newScreenshots.value.map(
        (screenshot) => screenshot.caption,
    );
    projectForm.screenshot_order = existingOrder.value.filter(
        (id) => !removedScreenshots.value.includes(id),
    );
    projectForm.screenshot_captions = existingCaptions.value;
    projectForm.removed_screenshots = removedScreenshots.value;

    projectForm.patch(update(props.project).url, {
        forceFormData: true,
        preserveScroll: true,
    });
}

const MAX_SCREENSHOTS = 5;

const newScreenshots = ref<{ file: File; caption: string }[]>([]);
const screenshotInput = ref<HTMLInputElement | null>(null);
const removedScreenshots = ref<number[]>([]);
const existingOrder = ref<number[]>(
    (props.project.screenshots ?? []).map(
        (screenshot: { id: number }) => screenshot.id,
    ),
);
const existingCaptions = ref<Record<number, string>>(
    Object.fromEntries(
        (props.project.screenshots ?? []).map(
            (screenshot: { id: number; caption: string | null }) => [
                screenshot.id,
                screenshot.caption ?? '',
            ],
        ),
    ),
);

const screenshotMap = computed(() =>
    Object.fromEntries(
        (props.project.screenshots ?? []).map((screenshot: any) => [
            screenshot.id,
            screenshot,
        ]),
    ),
);

const visibleExisting = computed(() =>
    existingOrder.value
        .filter((id) => !removedScreenshots.value.includes(id))
        .map((id) => screenshotMap.value[id]),
);

const canAddScreenshot = computed(
    () =>
        visibleExisting.value.length + newScreenshots.value.length <
        MAX_SCREENSHOTS,
);

function addScreenshots(files: FileList | null): void {
    if (files === null) {
        return;
    }

    for (const file of Array.from(files)) {
        if (!canAddScreenshot.value) {
            break;
        }

        newScreenshots.value.push({ file, caption: '' });
    }
}

function removeNewScreenshot(index: number): void {
    newScreenshots.value.splice(index, 1);
}

function removeExistingScreenshot(id: number): void {
    removedScreenshots.value.push(id);
}

function moveExistingScreenshot(id: number, direction: -1 | 1): void {
    const index = existingOrder.value.indexOf(id);
    const target = index + direction;

    if (target < 0 || target >= existingOrder.value.length) {
        return;
    }

    const next = [...existingOrder.value];
    [next[index], next[target]] = [next[target], next[index]];
    existingOrder.value = next;
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

function saveShipStory(approve: boolean): void {
    shipStoryForm.approve = approve;
    shipStoryForm.put(shipStoryRoutes.update(props.project).url, {
        preserveScroll: true,
        onSuccess: () => {
            shipStoryForm.approve = false;
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
    const detail = (event as CustomEvent).detail as
        | {
              filed_serial?: string;
          }
        | undefined;

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
            <SectionHeader
                :label="`Creator studio / ${project.is_public ? 'Public record' : 'Private draft'}`"
            >
                <h1
                    class="display-type mt-12 text-[clamp(3rem,7vw,7rem)] sm:mt-0"
                >
                    {{ project.name }}
                </h1>
                <p class="mt-6 text-muted-foreground">
                    Shape the identity, then write a release people can
                    discover.
                </p>
            </SectionHeader>
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
                                >Short overview</FieldLabel
                            ><RichTextEditor
                                v-model="projectForm.description" /></Field
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
                            ><FieldLabel for="github_url"
                                >GitHub URL</FieldLabel
                            >
                            <div
                                v-if="githubRepos === undefined"
                                class="h-10 w-full animate-pulse border border-dashed border-foreground/40"
                                aria-hidden="true"
                            ></div>
                            <GitHubRepoPicker
                                v-else-if="githubRepos !== null"
                                v-model="projectForm.github_url"
                                :repos="githubRepos"
                            />
                            <template v-else>
                                <Input
                                    id="github_url"
                                    v-model="projectForm.github_url"
                                    type="url"
                                    placeholder="https://github.com/you/project"
                                />
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        githubLinked
                                            ? 'We could not load your repositories — paste the URL instead.'
                                            : 'Link GitHub in Settings → Security to pick from your repositories.'
                                    }}
                                </p>
                            </template>
                            <FieldError v-if="projectForm.errors.github_url">{{
                                projectForm.errors.github_url
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
                        ><Field
                            ><FieldLabel>Logo</FieldLabel
                            ><FileUpload
                                :model-value="projectForm.logo"
                                kind="logo"
                                :existing-url="
                                    project.logo_url ? project.logo_url : null
                                "
                                :error="projectForm.errors.logo"
                                @update:model-value="onLogoChange"
                                @remove-existing="onLogoRemove" /></Field
                        ><Field
                            ><FieldLabel>Screenshots</FieldLabel>
                            <p class="text-xs text-muted-foreground">
                                Up to {{ MAX_SCREENSHOTS }} images,
                                JPG/PNG/WebP, up to 5 MB each.
                            </p>
                            <div class="grid gap-3">
                                <div
                                    v-for="screenshot in visibleExisting"
                                    :key="screenshot.id"
                                    class="flex items-start gap-3 border border-foreground p-3"
                                >
                                    <img
                                        :src="screenshot.url"
                                        class="h-20 w-32 object-cover"
                                        alt=""
                                    />
                                    <div class="grid flex-1 gap-2">
                                        <Input
                                            :model-value="
                                                existingCaptions[screenshot.id]
                                            "
                                            placeholder="Caption (optional)"
                                            @update:model-value="
                                                (value: string | number) =>
                                                    (existingCaptions[
                                                        screenshot.id
                                                    ] = String(value))
                                            "
                                        />
                                        <div class="flex gap-2">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                @click="
                                                    moveExistingScreenshot(
                                                        screenshot.id,
                                                        -1,
                                                    )
                                                "
                                            >
                                                Up
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                @click="
                                                    moveExistingScreenshot(
                                                        screenshot.id,
                                                        1,
                                                    )
                                                "
                                            >
                                                Down
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                @click="
                                                    removeExistingScreenshot(
                                                        screenshot.id,
                                                    )
                                                "
                                            >
                                                Remove
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-for="(
                                        screenshot, index
                                    ) in newScreenshots"
                                    :key="`new-${index}`"
                                    class="flex items-start gap-3 border border-dashed border-foreground p-3"
                                >
                                    <div
                                        class="flex h-20 w-32 items-center justify-center bg-muted text-xs"
                                    >
                                        {{ screenshot.file.name }}
                                    </div>
                                    <div class="grid flex-1 gap-2">
                                        <Input
                                            v-model="screenshot.caption"
                                            placeholder="Caption (optional)"
                                        />
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            @click="removeNewScreenshot(index)"
                                        >
                                            Remove
                                        </Button>
                                    </div>
                                </div>

                                <input
                                    ref="screenshotInput"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    multiple
                                    class="sr-only"
                                    tabindex="-1"
                                    data-test="project-screenshots"
                                    @change="
                                        addScreenshots(
                                            ($event.target as HTMLInputElement)
                                                .files,
                                        );
                                        (
                                            $event.target as HTMLInputElement
                                        ).value = '';
                                    "
                                />
                                <Button
                                    v-if="canAddScreenshot"
                                    type="button"
                                    variant="outline"
                                    @click="screenshotInput?.click()"
                                >
                                    <ImagePlus class="size-4" />
                                    Add screenshots
                                </Button>
                            </div>
                            <FieldError v-if="projectForm.errors.screenshots">{{
                                projectForm.errors.screenshots
                            }}</FieldError></Field
                        ><Field
                            ><FieldLabel for="pricing">Pricing</FieldLabel
                            ><Select v-model="projectForm.pricing"
                                ><SelectTrigger
                                    id="pricing"
                                    class="h-10 w-full rounded-none border-foreground"
                                    ><SelectValue /></SelectTrigger
                                ><SelectContent
                                    ><SelectItem
                                        v-for="option in pricingOptions"
                                        :key="option.value"
                                        :value="option.value"
                                        >{{ option.label }}</SelectItem
                                    ></SelectContent
                                ></Select
                            ><FieldError v-if="projectForm.errors.pricing">{{
                                projectForm.errors.pricing
                            }}</FieldError></Field
                        ><Field
                            ><FieldLabel for="launch_date"
                                >Launch date</FieldLabel
                            ><DatePicker
                                id="launch_date"
                                v-model="projectForm.launch_date"
                                placeholder="Pick a launch date"
                            /><FieldError
                                v-if="projectForm.errors.launch_date"
                                >{{
                                    projectForm.errors.launch_date
                                }}</FieldError
                            ></Field
                        ><Field
                            ><FieldLabel for="tags">Tags</FieldLabel
                            ><Input
                                id="tags"
                                v-model="projectForm.tags"
                                placeholder="laravel, vue, indie"
                                data-test="project-tags"
                            />
                            <div class="mt-2 flex flex-wrap gap-2">
                                <button
                                    v-for="tag in suggestedTags"
                                    :key="tag"
                                    type="button"
                                    class="technical-label border border-foreground px-2 py-1 hover:bg-secondary"
                                    @click="appendSuggestedTag(tag)"
                                >
                                    {{ tag }}
                                </button>
                            </div>
                            <FieldError v-if="projectForm.errors.tags">{{
                                projectForm.errors.tags
                            }}</FieldError></Field
                        ><Field>
                            <FieldLabel>Built with</FieldLabel>
                            <p class="text-xs text-muted-foreground">
                                Declare the stack behind the project. Every
                                choice becomes a filter visitors can browse.
                            </p>
                            <TechnologyPicker
                                v-model="projectForm.technologies"
                                :groups="technologyOptions"
                            />
                            <FieldError
                                v-if="projectForm.errors.technologies"
                                >{{
                                    projectForm.errors.technologies
                                }}</FieldError
                            ></Field
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
                            ><Spinner
                                v-if="releaseForm.processing"
                            /><CalendarClock
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
                            >Approve a complete Ship Story, publish a release,
                            and verify the live URL before filing this project.
                        </AlertDescription></Alert
                    ><AlertDialog
                        v-if="!project.is_public"
                        v-model:open="confirmPublish"
                        ><AlertDialogTrigger as-child
                            ><Button
                                class="mt-6 w-full"
                                variant="outline"
                                :disabled="
                                    publishing ||
                                    !hasPublishedRelease ||
                                    project.verification_status !==
                                        'verified' ||
                                    !hasApprovedShipStory
                                "
                                ><Eye class="size-4" />Publish project</Button
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
                                ><AlertDialogCancel :disabled="publishing"
                                    >Keep private</AlertDialogCancel
                                ><AlertDialogAction
                                    :disabled="publishing"
                                    @click="publishProject"
                                    ><Spinner v-if="publishing" /><Check
                                        v-else
                                        class="size-4"
                                    />
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
                                        class="size-4"
                                    />
                                    Withdraw from public</AlertDialogAction
                                ></AlertDialogFooter
                            ></AlertDialogContent
                        ></AlertDialog
                    >
                </section>
            </div>
            <section class="border-t border-foreground bg-background">
                <div class="grid gap-8 p-5 sm:grid-cols-[.45fr_1.55fr] sm:p-8">
                    <div>
                        <p class="technical-label text-primary">Ship Story</p>
                        <h2 class="display-type mt-5 text-4xl">
                            Give the launch a reason to return.
                        </h2>
                        <p
                            class="mt-5 max-w-sm text-sm leading-7 text-muted-foreground"
                        >
                            Keep this private while you think. Approve it when
                            the story explains the problem, the people, and the
                            choices behind the shipped work.
                        </p>
                        <p
                            class="technical-label mt-8"
                            :class="
                                shipStory?.is_approved
                                    ? 'text-primary'
                                    : 'text-muted-foreground'
                            "
                            data-test="ship-story-status"
                        >
                            {{ shipStoryStatus }}
                        </p>
                    </div>
                    <form
                        novalidate
                        class="grid gap-5"
                        data-test="ship-story-form"
                        @submit.prevent="saveShipStory(false)"
                    >
                        <Field>
                            <FieldLabel for="story_problem"
                                >What problem made this worth
                                building?</FieldLabel
                            >
                            <Textarea
                                id="story_problem"
                                v-model="shipStoryForm.problem"
                                rows="4"
                                placeholder="Name the real problem behind the project."
                            />
                            <FieldError v-if="shipStoryForm.errors.problem">{{
                                shipStoryForm.errors.problem
                            }}</FieldError>
                        </Field>
                        <Field>
                            <FieldLabel for="story_audience"
                                >Who is this for?</FieldLabel
                            >
                            <Textarea
                                id="story_audience"
                                v-model="shipStoryForm.audience"
                                rows="3"
                                placeholder="Describe the people who get value from it."
                            />
                            <FieldError v-if="shipStoryForm.errors.audience">{{
                                shipStoryForm.errors.audience
                            }}</FieldError>
                        </Field>
                        <Field>
                            <FieldLabel for="story_shipped"
                                >What shipped?</FieldLabel
                            >
                            <Textarea
                                id="story_shipped"
                                v-model="shipStoryForm.shipped"
                                rows="4"
                                placeholder="Tell people what they can try now."
                            />
                            <FieldError v-if="shipStoryForm.errors.shipped">{{
                                shipStoryForm.errors.shipped
                            }}</FieldError>
                        </Field>
                        <div class="grid gap-5 md:grid-cols-2">
                            <Field>
                                <FieldLabel for="story_build_decisions"
                                    >What build choices mattered?</FieldLabel
                                >
                                <Textarea
                                    id="story_build_decisions"
                                    v-model="shipStoryForm.build_decisions"
                                    rows="5"
                                    placeholder="Explain a Laravel, product, or design decision."
                                />
                                <FieldError
                                    v-if="shipStoryForm.errors.build_decisions"
                                    >{{
                                        shipStoryForm.errors.build_decisions
                                    }}</FieldError
                                >
                            </Field>
                            <Field>
                                <FieldLabel for="story_hardest_problem"
                                    >What was hardest?</FieldLabel
                                >
                                <Textarea
                                    id="story_hardest_problem"
                                    v-model="shipStoryForm.hardest_problem"
                                    rows="5"
                                    placeholder="Share the obstacle that changed the build."
                                />
                                <FieldError
                                    v-if="shipStoryForm.errors.hardest_problem"
                                    >{{
                                        shipStoryForm.errors.hardest_problem
                                    }}</FieldError
                                >
                            </Field>
                        </div>
                        <Field>
                            <FieldLabel for="story_lessons_learned"
                                >What did you learn?</FieldLabel
                            >
                            <Textarea
                                id="story_lessons_learned"
                                v-model="shipStoryForm.lessons_learned"
                                rows="4"
                                placeholder="Leave the next builder a useful lesson."
                            />
                            <FieldError
                                v-if="shipStoryForm.errors.lessons_learned"
                                >{{
                                    shipStoryForm.errors.lessons_learned
                                }}</FieldError
                            >
                        </Field>
                        <Field>
                            <FieldLabel for="story_next"
                                >What comes next?
                                <span class="text-muted-foreground"
                                    >(optional)</span
                                ></FieldLabel
                            >
                            <Textarea
                                id="story_next"
                                v-model="shipStoryForm.next"
                                rows="3"
                                placeholder="Name the next experiment, release, or question."
                            />
                            <FieldError v-if="shipStoryForm.errors.next">{{
                                shipStoryForm.errors.next
                            }}</FieldError>
                        </Field>
                        <div class="flex flex-wrap gap-3">
                            <Button
                                type="submit"
                                variant="outline"
                                :disabled="shipStoryForm.processing"
                                data-test="save-ship-story"
                            >
                                <Spinner v-if="shipStoryForm.processing" />
                                Save draft
                            </Button>
                            <Button
                                type="button"
                                :disabled="shipStoryForm.processing"
                                data-test="approve-ship-story"
                                @click="saveShipStory(true)"
                            >
                                <Spinner v-if="shipStoryForm.processing" />
                                <Check v-else class="size-4" />
                                Approve Ship Story
                            </Button>
                        </div>
                    </form>
                </div>
            </section>
            <VerificationPanel :project="project" />
            <StackObservationPanel
                :project-slug="project.slug"
                :github-url="stackObservation.github_url"
                :observed-at="stackObservation.observed_at"
                :observed-slugs="stackObservation.observed_slugs"
            />
            <BadgeSnippet v-if="badgeMarkdown" :markdown="badgeMarkdown" />
            <section class="border-t border-foreground">
                <div class="grid p-5 sm:grid-cols-[.45fr_1.55fr] sm:p-8">
                    <p class="technical-label text-primary">Launch Kit</p>
                    <div class="mt-8 flex flex-col gap-3 sm:mt-0">
                        <p class="text-sm leading-6 text-muted-foreground">
                            The share text, launch card, Ship Manifest, and
                            README badge for this launch — every shareable asset
                            in one place.
                        </p>
                        <div>
                            <Button
                                as-child
                                variant="outline"
                                data-test="open-launch-kit"
                            >
                                <Link
                                    :href="launchKit({ project: project.slug })"
                                >
                                    Open Launch Kit
                                    <ArrowUpRight class="size-4" />
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </section>
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
                <p class="technical-label text-muted-foreground tabular-nums">
                    {{ filedSerial }}
                </p>
            </div>
        </div>
    </Teleport>
</template>
