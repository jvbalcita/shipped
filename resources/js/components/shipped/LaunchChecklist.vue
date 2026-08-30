<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, CircleDashed, Eye, EyeOff } from '@lucide/vue';
import { computed, ref } from 'vue';
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
import { Progress } from '@/components/ui/progress';
import { Spinner } from '@/components/ui/spinner';
import visibility from '@/routes/projects/visibility';
import type { ProjectShipStory, StudioProject } from '@/types/creator';

type ChecklistGate = {
    id: string;
    label: string;
    hint: string;
    done: boolean;
};

const props = defineProps<{
    project: StudioProject;
    shipStory: ProjectShipStory | null;
}>();

const recordComplete = computed(() =>
    Boolean(
        props.project.name &&
        props.project.tagline &&
        props.project.description &&
        props.project.category_id &&
        props.project.cover_image_url &&
        props.project.screenshots.length &&
        (props.project.live_url || props.project.github_url),
    ),
);

const hasPublishedRelease = computed(() =>
    props.project.releases.some(
        (release) =>
            release.published_at !== null &&
            new Date(release.published_at) <= new Date(),
    ),
);

const gates = computed<ChecklistGate[]>(() => [
    {
        id: 'studio-record',
        label: 'Project record complete',
        hint: 'Name, description, media, and a live or GitHub link.',
        done: recordComplete.value,
    },
    {
        id: 'studio-story',
        label: 'Ship Story approved',
        hint: 'Write the story, then approve it for public discovery.',
        done: props.shipStory?.is_approved === true,
    },
    {
        id: 'studio-releases',
        label: 'First release published',
        hint: 'Publish at least one release from the release station.',
        done: hasPublishedRelease.value,
    },
    {
        id: 'studio-verification',
        label: 'Live URL verified',
        hint: 'Verify the Laravel Cloud deployment URL.',
        done: props.project.verification_status === 'verified',
    },
]);

const readyCount = computed(
    () => gates.value.filter((gate) => gate.done).length,
);
const progress = computed(() => (readyCount.value / gates.value.length) * 100);
const allReady = computed(() => readyCount.value === gates.value.length);
const missingLabels = computed(() =>
    gates.value.filter((gate) => !gate.done).map((gate) => gate.label),
);

function scrollToGate(gate: ChecklistGate): void {
    document
        .getElementById(gate.id)
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

const publishing = ref(false);
const withdrawing = ref(false);
const confirmPublish = ref(false);
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
</script>

<template>
    <div data-test="launch-checklist">
        <p class="technical-label text-primary">Launch readiness</p>
        <p class="mt-3 text-sm leading-6 text-muted-foreground">
            A launch stays private until every gate below is done. Work top to
            bottom, then file the project public.
        </p>

        <p class="technical-label mt-6 tabular-nums">
            {{ readyCount }} of {{ gates.length }} ready
        </p>
        <Progress
            class="mt-2"
            :model-value="progress"
            aria-label="Launch readiness progress"
        />

        <ol class="mt-4 grid gap-px border border-foreground bg-foreground">
            <li v-for="gate in gates" :key="gate.id">
                <button
                    type="button"
                    class="flex w-full items-start gap-3 bg-background p-3 text-left hover:bg-secondary"
                    :data-test="`checklist-${gate.id}`"
                    @click="scrollToGate(gate)"
                >
                    <span
                        class="mt-0.5 grid size-5 shrink-0 place-items-center border border-foreground text-xs"
                        :class="
                            gate.done
                                ? 'bg-primary text-primary-foreground'
                                : ''
                        "
                    >
                        <Check v-if="gate.done" class="size-3" />
                        <CircleDashed
                            v-else
                            class="size-3"
                            aria-hidden="true"
                        />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-medium">{{
                            gate.label
                        }}</span>
                        <span
                            v-if="!gate.done"
                            class="mt-1 block text-xs leading-5 text-muted-foreground"
                        >
                            {{ gate.hint }}
                        </span>
                    </span>
                    <span
                        class="technical-label ml-auto shrink-0 pt-0.5"
                        :class="
                            gate.done ? 'text-primary' : 'text-muted-foreground'
                        "
                        >{{ gate.done ? 'Done' : 'To do' }}</span
                    >
                </button>
            </li>
        </ol>

        <AlertDialog v-if="!project.is_public" v-model:open="confirmPublish"
            ><AlertDialogTrigger as-child
                ><Button class="mt-4 w-full" :disabled="publishing || !allReady"
                    ><Eye class="size-4" />Publish project</Button
                ></AlertDialogTrigger
            ><AlertDialogContent class="rounded-none border-2 border-foreground"
                ><AlertDialogHeader
                    ><AlertDialogTitle>Publish this project?</AlertDialogTitle
                    ><AlertDialogDescription
                        >It will appear in the public Shipped registry with its
                        published releases.</AlertDialogDescription
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
        >
        <p
            v-else-if="!allReady"
            class="mt-4 text-xs leading-5 text-muted-foreground"
        >
            Still missing: {{ missingLabels.join(', ') }}. The record stays
            public until you withdraw it, but completing these keeps it
            discoverable.
        </p>
        <AlertDialog v-if="project.is_public" v-model:open="confirmWithdraw"
            ><AlertDialogTrigger as-child
                ><Button
                    class="mt-4 w-full"
                    variant="ghost"
                    :disabled="withdrawing"
                    data-test="withdraw-project"
                    ><EyeOff class="size-4" />Withdraw from public</Button
                ></AlertDialogTrigger
            ><AlertDialogContent class="rounded-none border-2 border-foreground"
                ><AlertDialogHeader
                    ><AlertDialogTitle>Withdraw this project?</AlertDialogTitle
                    ><AlertDialogDescription
                        >It will be removed from the public registry. Its
                        dispatch number stays on the record, and you can
                        republish anytime.</AlertDialogDescription
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
        <p
            v-if="!project.is_public && !allReady"
            class="mt-3 text-xs leading-5 text-muted-foreground"
        >
            Still missing: {{ missingLabels.join(', ') }}.
        </p>
    </div>
</template>
