<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ImagePlus, RefreshCw } from '@lucide/vue';
import { computed, ref } from 'vue';
import DatePicker from '@/components/shipped/DatePicker.vue';
import FileUpload from '@/components/shipped/FileUpload.vue';
import GitHubRepoPicker from '@/components/shipped/GitHubRepoPicker.vue';
import RichTextEditor from '@/components/shipped/RichTextEditor.vue';
import TechnologyPicker from '@/components/shipped/TechnologyPicker.vue';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldError,
    FieldLabel,
    FieldLegend,
    FieldSet,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useUnsavedChangesGuard } from '@/composables/useUnsavedChangesGuard';
import { focusFirstError } from '@/lib/focusFirstError';
import { link as linkOauth } from '@/routes/oauth';
import { update } from '@/routes/projects';
import type { StudioProject, StudioProjectScreenshot } from '@/types/creator';
import type { TechnologyGroupOption } from '@/types/technology';

const props = defineProps<{
    project: StudioProject;
    categories: { id: number; name: string }[];
    pricingOptions: { value: string; label: string }[];
    suggestedTags: string[];
    technologyOptions: TechnologyGroupOption[];
    declaredTechnologies: string[];
    githubLinked?: boolean;
    githubRepos?: { name: string; url: string }[] | null;
}>();

const projectForm = useForm({
    name: props.project.name,
    tagline: props.project.tagline ?? '',
    description: props.project.description ?? '',
    category_id: String(props.project.category_id),
    live_url: props.project.live_url ?? '',
    github_url: props.project.github_url ?? '',
    pricing: props.project.pricing ?? 'free',
    launch_date: props.project.launch_date
        ? String(props.project.launch_date).slice(0, 10)
        : '',
    tags: (props.project.tags ?? []).map((tag) => tag.name).join(', '),
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

// The repository picker falls back to a URL input when GitHub cannot be
// read; reconnecting runs the OAuth flow again to rotate the token.
const reconnectForm = useForm({});

useUnsavedChangesGuard(computed(() => projectForm.isDirty));

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

const MAX_SCREENSHOTS = 5;

const newScreenshots = ref<{ file: File; caption: string }[]>([]);
const screenshotInput = ref<HTMLInputElement | null>(null);
const removedScreenshots = ref<number[]>([]);
const existingOrder = ref<number[]>(
    (props.project.screenshots ?? []).map(
        (screenshot: StudioProjectScreenshot) => screenshot.id,
    ),
);
const existingCaptions = ref<Record<number, string>>(
    Object.fromEntries(
        (props.project.screenshots ?? []).map(
            (screenshot: StudioProjectScreenshot) => [
                screenshot.id,
                screenshot.caption ?? '',
            ],
        ),
    ),
);

const screenshotMap = computed(() =>
    Object.fromEntries(
        (props.project.screenshots ?? []).map((screenshot) => [
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
        onError: (errors) => focusFirstError(errors, ERROR_FIELD_IDS),
    });
}
</script>

<template>
    <form
        novalidate
        class="grid gap-10"
        data-test="project-record-form"
        @submit.prevent="saveProject"
    >
        <FieldSet>
            <FieldLegend class="technical-label text-primary"
                >Identity</FieldLegend
            >
            <Field
                ><FieldLabel for="name"
                    >Project name
                    <span class="text-primary" aria-hidden="true"
                        >*</span
                    > </FieldLabel
                ><Input
                    id="name"
                    v-model="projectForm.name"
                    required
                /><FieldError v-if="projectForm.errors.name">{{
                    projectForm.errors.name
                }}</FieldError></Field
            >
            <Field
                ><FieldLabel for="tagline"
                    >One-line description
                    <span class="text-primary" aria-hidden="true"
                        >*</span
                    > </FieldLabel
                ><Input
                    id="tagline"
                    v-model="projectForm.tagline"
                    required
                    placeholder="The hook a visitor reads in one breath."
                /><FieldError v-if="projectForm.errors.tagline">{{
                    projectForm.errors.tagline
                }}</FieldError></Field
            >
            <Field
                ><FieldLabel for="description"
                    >Short overview
                    <span class="text-primary" aria-hidden="true"
                        >*</span
                    > </FieldLabel
                ><RichTextEditor v-model="projectForm.description" /><FieldError
                    v-if="projectForm.errors.description"
                    >{{ projectForm.errors.description }}</FieldError
                ></Field
            >
        </FieldSet>

        <FieldSet>
            <FieldLegend class="technical-label text-primary"
                >Media</FieldLegend
            >
            <Field
                ><FieldLabel
                    >Cover image
                    <span class="text-primary" aria-hidden="true">*</span>
                </FieldLabel>
                <div id="cover-upload" tabindex="-1">
                    <FileUpload
                        :model-value="projectForm.cover_image"
                        :existing-url="
                            project.cover_image_url
                                ? project.cover_image_url
                                : null
                        "
                        :error="projectForm.errors.cover_image"
                        @update:model-value="onCoverChange"
                        @remove-existing="onCoverRemove"
                    /></div
            ></Field>
            <Field
                ><FieldLabel>Logo</FieldLabel>
                <div id="logo-upload" tabindex="-1">
                    <FileUpload
                        :model-value="projectForm.logo"
                        kind="logo"
                        :existing-url="
                            project.logo_url ? project.logo_url : null
                        "
                        :error="projectForm.errors.logo"
                        @update:model-value="onLogoChange"
                        @remove-existing="onLogoRemove"
                    /></div
            ></Field>
            <Field
                ><FieldLabel
                    >Screenshots
                    <span class="text-primary" aria-hidden="true">*</span>
                </FieldLabel>
                <p class="text-xs text-muted-foreground">
                    Up to {{ MAX_SCREENSHOTS }} images, JPG/PNG/WebP, up to 5 MB
                    each.
                </p>
                <div id="screenshots-field" tabindex="-1" class="grid gap-3">
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
                                :model-value="existingCaptions[screenshot.id]"
                                placeholder="Caption (optional)"
                                @update:model-value="
                                    (value: string | number) =>
                                        (existingCaptions[screenshot.id] =
                                            String(value))
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
                                        moveExistingScreenshot(screenshot.id, 1)
                                    "
                                >
                                    Down
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="
                                        removeExistingScreenshot(screenshot.id)
                                    "
                                >
                                    Remove
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div
                        v-for="(screenshot, index) in newScreenshots"
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
                                ($event.target as HTMLInputElement).files,
                            );
                            ($event.target as HTMLInputElement).value = '';
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
                <FieldError v-if="projectForm.errors.screenshots">{{
                    projectForm.errors.screenshots
                }}</FieldError></Field
            >
        </FieldSet>

        <FieldSet>
            <FieldLegend class="technical-label text-primary"
                >Details</FieldLegend
            >
            <div class="grid gap-6 md:grid-cols-2">
                <Field
                    ><FieldLabel for="category"
                        >Category
                        <span class="text-primary" aria-hidden="true"
                            >*</span
                        > </FieldLabel
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
                >
                <Field
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
                    ></Field
                >
            </div>
            <Field
                ><FieldLabel for="launch_date">Launch date</FieldLabel
                ><DatePicker
                    id="launch_date"
                    v-model="projectForm.launch_date"
                    placeholder="Pick a launch date"
                /><FieldError v-if="projectForm.errors.launch_date">{{
                    projectForm.errors.launch_date
                }}</FieldError></Field
            >
            <Field
                ><FieldLabel for="tags">Tags</FieldLabel
                ><Input
                    id="tags"
                    v-model="projectForm.tags"
                    placeholder="laravel, vue, indie"
                    data-test="project-tags"
                />
                <p class="text-xs text-muted-foreground">
                    Tags help visitors find you from Discover. Tap a suggestion
                    to add it.
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
                <FieldError v-if="projectForm.errors.tags">{{
                    projectForm.errors.tags
                }}</FieldError></Field
            >
            <Field>
                <FieldLabel>Built with</FieldLabel>
                <p class="text-xs text-muted-foreground">
                    Declare the stack behind the project. Every choice becomes a
                    filter visitors can browse.
                </p>
                <TechnologyPicker
                    v-model="projectForm.technologies"
                    :groups="technologyOptions"
                />
                <FieldError v-if="projectForm.errors.technologies">{{
                    projectForm.errors.technologies
                }}</FieldError></Field
            >
        </FieldSet>

        <FieldSet>
            <FieldLegend class="technical-label text-primary"
                >Links</FieldLegend
            >
            <Field
                ><FieldLabel for="live_url">Live URL</FieldLabel
                ><Input
                    id="live_url"
                    v-model="projectForm.live_url"
                    type="url"
                    placeholder="https://your-project.com"
                /><FieldError v-if="projectForm.errors.live_url">{{
                    projectForm.errors.live_url
                }}</FieldError></Field
            >
            <Field
                ><FieldLabel for="github_url">GitHub URL</FieldLabel>
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
                                'animate-spin': reconnectForm.processing,
                            }"
                        />
                        Reconnect GitHub
                    </Button>
                </template>
                <FieldError v-if="projectForm.errors.github_url">{{
                    projectForm.errors.github_url
                }}</FieldError></Field
            >
        </FieldSet>

        <div>
            <Button type="submit" :disabled="projectForm.processing"
                ><Spinner v-if="projectForm.processing" />Save project
                record</Button
            >
        </div>
    </form>
</template>
