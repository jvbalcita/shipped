<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Check,
    ImagePlus,
    RefreshCw,
    Send,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import DatePicker from '@/components/shipped/DatePicker.vue';
import FileUpload from '@/components/shipped/FileUpload.vue';
import GitHubRepoPicker from '@/components/shipped/GitHubRepoPicker.vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import RichTextEditor from '@/components/shipped/RichTextEditor.vue';
import SectionHeader from '@/components/shipped/SectionHeader.vue';
import TechnologyPicker from '@/components/shipped/TechnologyPicker.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Stepper,
    StepperIndicator,
    StepperItem,
    StepperTitle,
    StepperTrigger,
} from '@/components/ui/stepper';
import { useUnsavedChangesGuard } from '@/composables/useUnsavedChangesGuard';
import { focusFirstError } from '@/lib/focusFirstError';
import { link as linkOauth } from '@/routes/oauth';
import { index, store } from '@/routes/projects';
import type { TechnologyGroupOption } from '@/types/technology';

const props = defineProps<{
    categories: { id: number; name: string }[];
    pricingOptions: { value: string; label: string }[];
    suggestedTags: string[];
    technologyOptions: TechnologyGroupOption[];
    githubLinked?: boolean;
    githubRepos?: { name: string; url: string }[] | null;
}>();

const STEPS = ['Identity', 'Media', 'Details', 'Review'] as const;
const LAST_STEP = STEPS.length;
const step = ref(1);

const form = useForm({
    name: '',
    tagline: '',
    description: '',
    category_id: String(props.categories[0]?.id ?? ''),
    live_url: '',
    github_url: '',
    pricing: props.pricingOptions[0]?.value ?? 'free',
    launch_date: '',
    tags: '',
    technologies: [] as string[],
    cover_image: null as File | null,
    logo: null as File | null,
    screenshots: [] as File[],
    screenshots_captions: [] as string[],
});

// The repository picker falls back to a URL input when GitHub cannot be
// read; reconnecting runs the OAuth flow again to rotate the token.
const reconnectForm = useForm({});

useUnsavedChangesGuard(computed(() => form.isDirty));

const MAX_SCREENSHOTS = 5;

const technologyNameBySlug = computed(() =>
    Object.fromEntries(
        props.technologyOptions.flatMap((group) =>
            group.technologies.map((technology) => [
                technology.slug,
                technology.name,
            ]),
        ),
    ),
);

const selectedTechnologyNames = computed(() =>
    form.technologies
        .map((slug) => technologyNameBySlug.value[slug] ?? slug)
        .join(', '),
);

const newScreenshots = ref<{ file: File; caption: string }[]>([]);
const screenshotInput = ref<HTMLInputElement | null>(null);

const canAddScreenshot = computed(
    () => newScreenshots.value.length < MAX_SCREENSHOTS,
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

function appendSuggestedTag(tag: string): void {
    const current = form.tags
        .split(',')
        .map((value) => value.trim())
        .filter(Boolean);

    if (!current.includes(tag)) {
        current.push(tag);
        form.tags = current.join(', ');
    }
}

const progress = computed(() => (step.value / LAST_STEP) * 100);

const ERROR_FIELD_IDS: Record<string, string> = {
    name: 'name',
    tagline: 'tagline',
    category_id: 'category',
    pricing: 'pricing',
    launch_date: 'launch_date',
    tags: 'tags',
    live_url: 'live_url',
    github_url: 'github_url',
    cover_image: 'cover-upload',
    logo: 'logo-upload',
    screenshots: 'screenshots-field',
};

function isValidUrl(value: string): boolean {
    try {
        new URL(value);

        return true;
    } catch {
        return false;
    }
}

function validateCurrentStep(): boolean {
    form.clearErrors();

    const errors: Record<string, string> = {};

    if (step.value === 1) {
        if (!form.name.trim()) {
            errors.name = 'Give the project a name.';
        }

        if (!form.tagline.trim()) {
            errors.tagline = 'Write a one-line description.';
        }

        if (!form.description.trim()) {
            errors.description = 'Write a short project overview.';
        }
    }

    if (step.value === 2) {
        if (!form.cover_image) {
            errors.cover_image = 'Add a cover image.';
        }

        if (newScreenshots.value.length === 0) {
            errors.screenshots = 'Add at least one screenshot.';
        }
    }

    if (step.value === 3) {
        if (!form.category_id) {
            errors.category_id = 'Choose a category.';
        }

        if (!form.live_url && !form.github_url) {
            errors.live_url = 'Add a live URL or a GitHub URL.';
        }

        if (form.live_url && !isValidUrl(form.live_url)) {
            errors.live_url = 'Enter a complete URL, including https://.';
        }

        if (form.github_url && !isValidUrl(form.github_url)) {
            errors.github_url = 'Enter a complete URL, including https://.';
        }
    }

    if (Object.keys(errors).length) {
        form.setError(errors);
        focusFirstError(errors, ERROR_FIELD_IDS);

        return false;
    }

    return true;
}

function continueComposer(): void {
    if (!validateCurrentStep()) {
        return;
    }

    if (step.value < LAST_STEP) {
        step.value += 1;

        return;
    }

    form.screenshots = newScreenshots.value.map(
        (screenshot) => screenshot.file,
    );
    form.screenshots_captions = newScreenshots.value.map(
        (screenshot) => screenshot.caption,
    );

    form.post(store().url, {
        forceFormData: true,
        onError: (errors) => {
            step.value = ['name', 'tagline', 'description'].some(
                (field) => errors[field],
            )
                ? 1
                : ['cover_image', 'logo', 'screenshots'].some(
                        (field) => errors[field],
                    )
                  ? 2
                  : 3;
            focusFirstError(errors, ERROR_FIELD_IDS);
        },
    });
}

function exitComposer(): void {
    if (
        form.isDirty &&
        !window.confirm(
            'Leave the composer? Nothing is saved yet and your answers will be lost.',
        )
    ) {
        return;
    }

    router.visit(index().url);
}

const fieldNotes = [
    {
        text: 'The record stays private until it has a Ship Story, a release, and a deliberate public filing. Start with the words a visitor reads first.',
        required: 'Name, line, overview',
    },
    {
        text: 'Show the thing. The cover sets the first impression and the screenshots prove the product works.',
        required: 'Cover, one screenshot',
    },
    {
        text: 'Category and links decide where visitors land and how they find you. The rest is optional polish you can change in the studio later.',
        required: 'Category, one link',
    },
    {
        text: 'Check the summary, then create the private draft. You will finish the launch inside your project studio.',
        required: 'Review',
    },
];
</script>

<template>
    <PublicShell title="Launch composer">
        <section
            class="page-enter mx-auto w-full max-w-[90rem] min-w-0 border-x border-b border-foreground"
        >
            <SectionHeader
                :label="`Launch composer / ${String(step).padStart(2, '0')} / ${String(LAST_STEP).padStart(2, '0')}`"
            >
                <h1
                    class="display-type mt-12 text-[clamp(3rem,7vw,7rem)] sm:mt-0"
                >
                    Give it a shape.
                </h1>
                <p class="mt-6 max-w-2xl leading-7 text-muted-foreground">
                    Start private. Build a launch record with enough substance
                    to become public — four short steps.
                </p>
            </SectionHeader>
            <div class="border-b border-foreground p-5 sm:p-8">
                <Stepper
                    v-model="step"
                    class="grid grid-cols-4 gap-px bg-foreground"
                >
                    <StepperItem
                        v-for="(item, index) in STEPS"
                        :key="item"
                        v-slot="{ state }"
                        :step="index + 1"
                        class="bg-background"
                    >
                        <StepperTrigger
                            class="flex w-full justify-center gap-3 p-3 text-left disabled:opacity-100 sm:justify-start sm:p-4"
                            ><StepperIndicator
                                class="grid size-7 place-items-center border border-foreground text-xs"
                                :class="
                                    state === 'active' || state === 'completed'
                                        ? 'bg-primary text-primary-foreground'
                                        : ''
                                "
                                ><Check
                                    v-if="state === 'completed'"
                                    class="size-3"
                                /><span v-else>{{
                                    index + 1
                                }}</span></StepperIndicator
                            ><StepperTitle
                                class="technical-label hidden sm:block"
                                >{{ item }}</StepperTitle
                            ></StepperTrigger
                        >
                    </StepperItem>
                </Stepper>
                <Progress
                    class="mt-5"
                    :model-value="progress"
                    aria-label="Launch composer progress"
                />
            </div>
            <form novalidate class="grid" @submit.prevent="continueComposer">
                <div
                    class="grid gap-px bg-foreground lg:grid-cols-[1.15fr_.85fr]"
                >
                    <div class="bg-background p-5 sm:p-8">
                        <template v-if="step === 1">
                            <p class="technical-label text-primary">
                                01 / Identity
                            </p>
                            <div class="mt-8 grid gap-6">
                                <Field
                                    ><FieldLabel for="name"
                                        >Project name
                                        <span
                                            class="text-primary"
                                            aria-hidden="true"
                                            >*</span
                                        > </FieldLabel
                                    ><Input
                                        id="name"
                                        v-model="form.name"
                                        required
                                        autofocus
                                    /><FieldError v-if="form.errors.name">{{
                                        form.errors.name
                                    }}</FieldError></Field
                                ><Field
                                    ><FieldLabel for="tagline"
                                        >One-line description
                                        <span
                                            class="text-primary"
                                            aria-hidden="true"
                                            >*</span
                                        > </FieldLabel
                                    ><Input
                                        id="tagline"
                                        v-model="form.tagline"
                                        required
                                        placeholder="The hook a visitor reads in one breath."
                                    /><FieldError v-if="form.errors.tagline">{{
                                        form.errors.tagline
                                    }}</FieldError></Field
                                ><Field
                                    ><FieldLabel for="description"
                                        >Short overview
                                        <span
                                            class="text-primary"
                                            aria-hidden="true"
                                            >*</span
                                        > </FieldLabel
                                    ><RichTextEditor
                                        v-model="form.description"
                                    /><FieldError
                                        v-if="form.errors.description"
                                        >{{
                                            form.errors.description
                                        }}</FieldError
                                    ></Field
                                >
                            </div>
                        </template>
                        <template v-else-if="step === 2">
                            <p class="technical-label text-primary">
                                02 / Media
                            </p>
                            <div class="mt-8 grid gap-6">
                                <Field
                                    ><FieldLabel
                                        >Cover image
                                        <span
                                            class="text-primary"
                                            aria-hidden="true"
                                            >*</span
                                        >
                                    </FieldLabel>
                                    <div id="cover-upload" tabindex="-1">
                                        <FileUpload
                                            v-model="form.cover_image"
                                            :error="form.errors.cover_image"
                                        /></div></Field
                                ><Field
                                    ><FieldLabel>Logo</FieldLabel>
                                    <div id="logo-upload" tabindex="-1">
                                        <FileUpload
                                            v-model="form.logo"
                                            kind="logo"
                                            :error="form.errors.logo"
                                        /></div></Field
                                ><Field
                                    ><FieldLabel
                                        >Screenshots
                                        <span
                                            class="text-primary"
                                            aria-hidden="true"
                                            >*</span
                                        >
                                    </FieldLabel>
                                    <p class="text-xs text-muted-foreground">
                                        Up to {{ MAX_SCREENSHOTS }} images,
                                        JPG/PNG/WebP, up to 5 MB each.
                                    </p>
                                    <div
                                        id="screenshots-field"
                                        tabindex="-1"
                                        class="grid gap-3"
                                    >
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
                                                    @click="
                                                        removeNewScreenshot(
                                                            index,
                                                        )
                                                    "
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
                                                    (
                                                        $event.target as HTMLInputElement
                                                    ).files,
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
                                            class="self-start"
                                            @click="screenshotInput?.click()"
                                        >
                                            <ImagePlus class="size-4" />
                                            Add screenshots
                                        </Button>
                                    </div>
                                    <FieldError
                                        v-if="form.errors.screenshots"
                                        >{{
                                            form.errors.screenshots
                                        }}</FieldError
                                    ></Field
                                >
                            </div>
                        </template>
                        <template v-else-if="step === 3">
                            <p class="technical-label text-primary">
                                03 / Details
                            </p>
                            <div class="mt-8 grid gap-6">
                                <Field
                                    ><FieldLabel for="category"
                                        >Category
                                        <span
                                            class="text-primary"
                                            aria-hidden="true"
                                            >*</span
                                        > </FieldLabel
                                    ><Select v-model="form.category_id"
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
                                    ><FieldError
                                        v-if="form.errors.category_id"
                                        >{{
                                            form.errors.category_id
                                        }}</FieldError
                                    ></Field
                                ><Field
                                    ><FieldLabel for="pricing"
                                        >Pricing</FieldLabel
                                    ><Select v-model="form.pricing"
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
                                    ><FieldError v-if="form.errors.pricing">{{
                                        form.errors.pricing
                                    }}</FieldError></Field
                                ><Field
                                    ><FieldLabel for="launch_date"
                                        >Launch date</FieldLabel
                                    ><DatePicker
                                        id="launch_date"
                                        v-model="form.launch_date"
                                        placeholder="Pick a launch date"
                                    /><FieldError
                                        v-if="form.errors.launch_date"
                                        >{{
                                            form.errors.launch_date
                                        }}</FieldError
                                    ></Field
                                ><Field
                                    ><FieldLabel for="tags">Tags</FieldLabel
                                    ><Input
                                        id="tags"
                                        v-model="form.tags"
                                        placeholder="laravel, vue, indie"
                                        data-test="project-tags"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Tags help visitors find you from
                                        Discover. Tap a suggestion to add it.
                                    </p>
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
                                    <FieldError v-if="form.errors.tags">{{
                                        form.errors.tags
                                    }}</FieldError></Field
                                ><Field>
                                    <FieldLabel>Built with</FieldLabel>
                                    <p class="text-xs text-muted-foreground">
                                        Declare the stack behind the project.
                                        Every choice becomes a filter visitors
                                        can browse.
                                    </p>
                                    <TechnologyPicker
                                        v-model="form.technologies"
                                        :groups="technologyOptions"
                                    />
                                    <FieldError
                                        v-if="form.errors.technologies"
                                        >{{
                                            form.errors.technologies
                                        }}</FieldError
                                    ></Field
                                ><Field
                                    ><FieldLabel for="live_url"
                                        >Live URL
                                        <span
                                            class="text-muted-foreground"
                                            aria-hidden="true"
                                            >— or GitHub below</span
                                        > </FieldLabel
                                    ><Input
                                        id="live_url"
                                        v-model="form.live_url"
                                        type="url"
                                        placeholder="https://yourapp.com"
                                    /><FieldError v-if="form.errors.live_url">{{
                                        form.errors.live_url
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
                                        v-model="form.github_url"
                                        :repos="githubRepos"
                                    />
                                    <template v-else>
                                        <Input
                                            id="github_url"
                                            v-model="form.github_url"
                                            type="url"
                                            placeholder="https://github.com/you/project"
                                        />
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                githubLinked
                                                    ? 'We could not load your repositories — paste the URL instead, or reconnect GitHub.'
                                                    : 'Link GitHub in Settings → Security to pick from your repositories.'
                                            }}
                                        </p>
                                        <Button
                                            v-if="githubLinked"
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            class="self-start"
                                            :disabled="reconnectForm.processing"
                                            data-test="reconnect-github"
                                            @click="
                                                reconnectForm.post(
                                                    linkOauth.url({
                                                        provider: 'github',
                                                    }),
                                                    { preserveScroll: true },
                                                )
                                            "
                                        >
                                            <RefreshCw
                                                class="size-4"
                                                :class="{
                                                    'animate-spin':
                                                        reconnectForm.processing,
                                                }"
                                            />
                                            Reconnect GitHub
                                        </Button>
                                    </template>
                                    <FieldError v-if="form.errors.github_url">{{
                                        form.errors.github_url
                                    }}</FieldError></Field
                                >
                            </div>
                        </template>
                        <template v-else>
                            <p class="technical-label text-primary">
                                04 / Review
                            </p>
                            <h2 class="display-type mt-8 text-5xl">
                                Ready to draft.
                            </h2>
                            <dl
                                class="mt-10 grid gap-px bg-foreground sm:grid-cols-2"
                            >
                                <div class="bg-background p-4">
                                    <dt
                                        class="technical-label text-muted-foreground"
                                    >
                                        Project
                                    </dt>
                                    <dd class="mt-2">{{ form.name }}</dd>
                                </div>
                                <div class="bg-background p-4">
                                    <dt
                                        class="technical-label text-muted-foreground"
                                    >
                                        Launch state
                                    </dt>
                                    <dd class="mt-2">Private draft</dd>
                                </div>
                                <div
                                    v-if="form.technologies.length"
                                    class="bg-background p-4 sm:col-span-2"
                                >
                                    <dt
                                        class="technical-label text-muted-foreground"
                                    >
                                        Built with
                                    </dt>
                                    <dd class="mt-2">
                                        {{ selectedTechnologyNames }}
                                    </dd>
                                </div>
                            </dl>
                            <Alert class="mt-8 border-foreground"
                                ><AlertTitle>What happens next</AlertTitle
                                ><AlertDescription
                                    >You will write and publish the first
                                    release from your project
                                    studio.</AlertDescription
                                ></Alert
                            >
                        </template>
                    </div>
                    <aside class="bg-secondary p-5 sm:p-8">
                        <p class="technical-label">Field notes</p>
                        <p class="mt-8 max-w-sm text-sm leading-7">
                            {{ fieldNotes[step - 1].text }}
                        </p>
                        <p class="technical-label mt-12 text-primary">
                            Required / {{ fieldNotes[step - 1].required }}
                        </p>
                    </aside>
                </div>
                <div
                    class="flex flex-col gap-4 border-t border-foreground p-5 sm:flex-row sm:items-center sm:justify-between sm:p-8"
                >
                    <Button
                        type="button"
                        variant="ghost"
                        class="w-full sm:w-auto"
                        :disabled="step === 1"
                        @click="step -= 1"
                        ><ArrowLeft class="size-4" /> Back</Button
                    >
                    <div class="grid w-full gap-2 sm:flex sm:w-auto">
                        <Button
                            type="button"
                            variant="outline"
                            class="w-full sm:w-auto"
                            @click="exitComposer"
                            >Cancel</Button
                        ><Button
                            type="submit"
                            class="w-full sm:w-auto"
                            :disabled="form.processing"
                            >{{
                                step === LAST_STEP
                                    ? 'Create private draft'
                                    : 'Continue'
                            }}<Send
                                v-if="step === LAST_STEP"
                                class="size-4" /><ArrowRight
                                v-else
                                class="size-4"
                        /></Button>
                    </div>
                </div>
            </form>
        </section>
    </PublicShell>
</template>
