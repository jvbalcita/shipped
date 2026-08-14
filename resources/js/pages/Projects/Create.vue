<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Check, Send } from '@lucide/vue';
import { computed, ref } from 'vue';
import FileUpload from '@/components/shipped/FileUpload.vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import SectionHeader from '@/components/shipped/SectionHeader.vue';
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
import RichTextEditor from '@/components/shipped/RichTextEditor.vue';
import { index, store } from '@/routes/projects';

const props = defineProps<{
    categories: { id: number; name: string }[];
    pricingOptions: { value: string; label: string }[];
    suggestedTags: string[];
}>();
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
    cover_image: null as File | null,
    logo: null as File | null,
    screenshots: [] as File[],
    screenshots_captions: [] as string[],
});

const MAX_SCREENSHOTS = 5;

const newScreenshots = ref<{ file: File; caption: string }[]>([]);

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
const progress = computed(() => (step.value / 3) * 100);

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

    if (step.value === 1) {
        const errors: Record<string, string> = {};

        if (!form.name.trim()) {
            errors.name = 'Give the project a name.';
        }

        if (!form.tagline.trim()) {
            errors.tagline = 'Write a one-line description.';
        }

        if (!form.description.trim()) {
            errors.description = 'Tell the fuller story.';
        }

        if (Object.keys(errors).length) {
            form.setError(errors);

            return false;
        }
    }

    if (step.value === 2) {
        const errors: Record<string, string> = {};

        if (!form.category_id) {
            errors.category_id = 'Choose a category.';
        }

        if (form.live_url && !isValidUrl(form.live_url)) {
            errors.live_url = 'Enter a complete URL, including https://.';
        }

        if (form.github_url && !isValidUrl(form.github_url)) {
            errors.github_url = 'Enter a complete URL, including https://.';
        }

        if (Object.keys(errors).length) {
            form.setError(errors);

            return false;
        }
    }

    return true;
}

function continueComposer(): void {
    if (!validateCurrentStep()) {
        return;
    }

    if (step.value < 3) {
        step.value += 1;

        return;
    }

    form.screenshots = newScreenshots.value.map((screenshot) => screenshot.file);
    form.screenshots_captions = newScreenshots.value.map((screenshot) => screenshot.caption);

    form.post(store().url, {
        forceFormData: true,
        onError: (errors) => {
            step.value = ['name', 'tagline', 'description'].some(
                (field) => errors[field],
            )
                ? 1
                : 2;
        },
    });
}
</script>

<template>
    <PublicShell title="Launch composer">
        <section
            class="page-enter mx-auto w-full max-w-[90rem] min-w-0 border-x border-b border-foreground"
        >
            <SectionHeader
                :label="`Launch composer / ${String(step).padStart(2, '0')} / 03`"
            >
                <h1
                    class="display-type mt-12 text-[clamp(3rem,7vw,7rem)] sm:mt-0"
                >
                    Give it a shape.
                </h1>
                <p class="mt-6 max-w-2xl leading-7 text-muted-foreground">
                    Start private. Build a launch record with enough
                    substance to become public.
                </p>
            </SectionHeader>
            <div class="border-b border-foreground p-5 sm:p-8">
                <Stepper
                    v-model="step"
                    class="grid grid-cols-3 gap-px bg-foreground"
                >
                    <StepperItem
                        v-for="item in 3"
                        :key="item"
                        v-slot="{ state }"
                        :step="item"
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
                                    item
                                }}</span></StepperIndicator
                            ><StepperTitle
                                class="technical-label hidden sm:block"
                                >{{
                                    ['Identity', 'Evidence', 'Review'][item - 1]
                                }}</StepperTitle
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
                                        >Project name</FieldLabel
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
                                        >One-line description</FieldLabel
                                    ><Input
                                        id="tagline"
                                        v-model="form.tagline"
                                        required
                                    /><FieldError v-if="form.errors.tagline">{{
                                        form.errors.tagline
                                    }}</FieldError></Field
                                ><Field
                                    ><FieldLabel for="description"
                                        >The fuller story</FieldLabel
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
                                02 / Evidence
                            </p>
                            <div class="mt-8 grid gap-6">
                                <Field
                                    ><FieldLabel for="category"
                                        >Category</FieldLabel
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
                                    ><FieldLabel>Cover image</FieldLabel
                                    ><FileUpload
                                        v-model="form.cover_image"
                                        :error="
                                            form.errors.cover_image
                                        " /></Field
                                ><Field
                                    ><FieldLabel>Logo</FieldLabel
                                    ><FileUpload
                                        v-model="form.logo"
                                        :error="form.errors.logo"
                                    /><p
                                        class="text-xs text-muted-foreground"
                                    >
                                         Square PNG, JPG, or WebP. At least
                                        256×256, up to 6 MB.
                                    </p></Field
                                ><Field
                                    ><FieldLabel>Screenshots</FieldLabel>
                                    <p class="text-xs text-muted-foreground">
                                        Up to {{ MAX_SCREENSHOTS }} images,
                                        JPG/PNG/WebP, up to 5 MB each.
                                    </p>
                                    <div class="grid gap-3">
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
                                            v-if="canAddScreenshot"
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            multiple
                                            data-test="project-screenshots"
                                            @change="addScreenshots(($event.target as HTMLInputElement).files)"
                                        />
                                    </div>
                                    <FieldError
                                        v-if="form.errors.screenshots"
                                        >{{ form.errors.screenshots }}</FieldError
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
                                    ><Input
                                        id="launch_date"
                                        v-model="form.launch_date"
                                        type="date"
                                        data-test="project-launch-date"
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
                                ><Field
                                    ><FieldLabel for="live_url"
                                        >Live URL</FieldLabel
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
                                    ><Input
                                        id="github_url"
                                        v-model="form.github_url"
                                        type="url"
                                        placeholder="https://github.com/you/project"
                                    /><FieldError
                                        v-if="form.errors.github_url"
                                        >{{
                                            form.errors.github_url
                                        }}</FieldError
                                    ></Field
                                >
                            </div>
                        </template>
                        <template v-else>
                            <p class="technical-label text-primary">
                                03 / Review
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
                            A launch stays private until it has a release story
                            and you deliberately publish it. This first step
                            simply creates the project record.
                        </p>
                        <p class="technical-label mt-12 text-primary">
                            Required /
                            {{
                                step === 1
                                    ? 'Name, line, story'
                                    : step === 2
                                      ? 'Category'
                                      : 'Review'
                            }}
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
                            @click="router.visit(index().url)"
                            >Save for later</Button
                        ><Button
                            type="submit"
                            class="w-full sm:w-auto"
                            :disabled="form.processing"
                            >{{
                                step === 3
                                    ? 'Create private draft'
                                    : 'Continue'
                            }}<Send
                                v-if="step === 3"
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
