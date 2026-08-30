<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import BadgeSnippet from '@/components/shipped/BadgeSnippet.vue';
import LaunchChecklist from '@/components/shipped/LaunchChecklist.vue';
import ProjectRecordForm from '@/components/shipped/ProjectRecordForm.vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import ReleaseStation from '@/components/shipped/ReleaseStation.vue';
import SectionHeader from '@/components/shipped/SectionHeader.vue';
import ShipStoryForm from '@/components/shipped/ShipStoryForm.vue';
import StackObservationPanel from '@/components/shipped/StackObservationPanel.vue';
import VerificationPanel from '@/components/shipped/VerificationPanel.vue';
import { Button } from '@/components/ui/button';
import { show as launchKit } from '@/routes/projects/launch-kit';
import type { ProjectShipStory, StudioProject } from '@/types/creator';
import type { TechnologyGroupOption } from '@/types/technology';

const props = defineProps<{
    project: StudioProject;
    shipStory: ProjectShipStory | null;
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

const shipStoryStatus = computed(() => {
    if (props.shipStory?.is_approved) {
        return 'Approved for public discovery';
    }

    if (props.shipStory) {
        return 'Private draft';
    }

    return 'Not started';
});

const hasPublishedRelease = computed(() =>
    props.project.releases.some(
        (release) =>
            release.published_at !== null &&
            new Date(release.published_at) <= new Date(),
    ),
);

const verificationStatus = computed(() => {
    switch (props.project.verification_status) {
        case 'verified':
            return 'Verified';
        case 'failed':
            return 'Failed';
        case 'stale':
            return 'Stale';
        default:
            return 'Not verified';
    }
});

const studioNav = [
    { id: 'studio-record', number: '01', label: 'Record' },
    { id: 'studio-story', number: '02', label: 'Ship story' },
    { id: 'studio-releases', number: '03', label: 'Releases' },
    { id: 'studio-verification', number: '04', label: 'Verification' },
    { id: 'studio-launch-kit', number: '05', label: 'Launch kit' },
];

function scrollToSection(id: string): void {
    document
        .getElementById(id)
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
                <p class="mt-6 max-w-2xl leading-7 text-muted-foreground">
                    One pipeline, five stations: shape the record, tell the
                    story, ship a release, verify the deployment, then file it
                    public. The launch readiness panel keeps score.
                </p>
            </SectionHeader>
            <div
                class="grid gap-px bg-foreground lg:grid-cols-[18rem_minmax(0,1fr)]"
            >
                <aside
                    class="bg-secondary p-5 sm:p-8 lg:sticky lg:top-16 lg:max-h-[calc(100dvh-4rem)] lg:self-start lg:overflow-y-auto"
                >
                    <LaunchChecklist
                        :project="project"
                        :ship-story="shipStory"
                    />
                    <nav
                        class="mt-8 border-t border-foreground pt-4"
                        aria-label="Studio sections"
                    >
                        <p class="technical-label text-muted-foreground">
                            Stations
                        </p>
                        <ul class="mt-2 grid">
                            <li v-for="item in studioNav" :key="item.id">
                                <button
                                    type="button"
                                    class="technical-label flex w-full items-baseline gap-3 border-b border-foreground/10 py-2.5 text-left last:border-b-0 hover:text-primary"
                                    @click="scrollToSection(item.id)"
                                >
                                    <span class="text-muted-foreground">{{
                                        item.number
                                    }}</span>
                                    {{ item.label }}
                                </button>
                            </li>
                        </ul>
                    </nav>
                </aside>
                <div class="min-w-0 bg-background">
                    <section
                        id="studio-record"
                        class="scroll-mt-20 p-5 sm:p-8"
                        data-test="studio-record"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-4"
                        >
                            <div class="min-w-0">
                                <p class="technical-label text-primary">
                                    01 / Project record
                                </p>
                                <h2 class="display-type mt-4 text-4xl">
                                    Shape the identity.
                                </h2>
                                <p
                                    class="mt-3 max-w-xl text-sm leading-7 text-muted-foreground"
                                >
                                    Everything here is what visitors scan first:
                                    the name, the hook, the media that proves
                                    something real shipped.
                                </p>
                            </div>
                            <p
                                class="technical-label"
                                :class="
                                    recordComplete
                                        ? 'text-primary'
                                        : 'text-muted-foreground'
                                "
                                data-test="record-status"
                            >
                                {{
                                    recordComplete ? 'Complete' : 'In progress'
                                }}
                            </p>
                        </div>
                        <ProjectRecordForm
                            class="mt-10"
                            :project="project"
                            :categories="categories"
                            :pricing-options="pricingOptions"
                            :suggested-tags="suggestedTags"
                            :technology-options="technologyOptions"
                            :declared-technologies="declaredTechnologies"
                            :github-linked="githubLinked"
                            :github-repos="githubRepos"
                        />
                    </section>
                    <section
                        id="studio-story"
                        class="scroll-mt-20 border-t border-foreground p-5 sm:p-8"
                        data-test="studio-story"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-4"
                        >
                            <div class="min-w-0">
                                <p class="technical-label text-primary">
                                    02 / Ship Story
                                </p>
                                <h2 class="display-type mt-4 text-4xl">
                                    Give the launch a reason to return.
                                </h2>
                                <p
                                    class="mt-3 max-w-xl text-sm leading-7 text-muted-foreground"
                                >
                                    Keep this private while you think. Approve
                                    it when the story explains the problem, the
                                    people, and the choices behind the shipped
                                    work.
                                </p>
                            </div>
                            <p
                                class="technical-label"
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
                        <ShipStoryForm
                            class="mt-10"
                            :project="project"
                            :ship-story="shipStory"
                        />
                    </section>
                    <section
                        id="studio-releases"
                        class="scroll-mt-20 border-t border-foreground p-5 sm:p-8"
                        data-test="studio-releases"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-4"
                        >
                            <div class="min-w-0">
                                <p class="technical-label text-primary">
                                    03 / Releases
                                </p>
                                <h2 class="display-type mt-4 text-4xl">
                                    Make it real.
                                </h2>
                                <p
                                    class="mt-3 max-w-xl text-sm leading-7 text-muted-foreground"
                                >
                                    A launch becomes discoverable through
                                    published releases. Say what changed, why it
                                    matters, and where to try it — now or
                                    scheduled.
                                </p>
                            </div>
                            <p
                                class="technical-label"
                                :class="
                                    hasPublishedRelease
                                        ? 'text-primary'
                                        : 'text-muted-foreground'
                                "
                                data-test="release-status"
                            >
                                {{
                                    hasPublishedRelease
                                        ? 'Published release'
                                        : 'No release yet'
                                }}
                            </p>
                        </div>
                        <ReleaseStation class="mt-10" :project="project" />
                    </section>
                    <section
                        id="studio-verification"
                        class="scroll-mt-20 border-t border-foreground p-5 sm:p-8"
                        data-test="studio-verification"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-4"
                        >
                            <div class="min-w-0">
                                <p class="technical-label text-primary">
                                    04 / Verification
                                </p>
                                <h2 class="display-type mt-4 text-4xl">
                                    Prove it.
                                </h2>
                                <p
                                    class="mt-3 max-w-xl text-sm leading-7 text-muted-foreground"
                                >
                                    Public records must point at a live
                                    deployment on Laravel Cloud. Verify once,
                                    recheck when it changes.
                                </p>
                            </div>
                            <p
                                class="technical-label"
                                :class="
                                    project.verification_status === 'verified'
                                        ? 'text-primary'
                                        : 'text-muted-foreground'
                                "
                                data-test="verification-status"
                            >
                                {{ verificationStatus }}
                            </p>
                        </div>
                        <VerificationPanel class="mt-10" :project="project" />
                        <div class="mt-10 border-t border-foreground pt-8">
                            <p class="technical-label text-primary">
                                Stack observation
                            </p>
                            <p
                                class="mt-3 max-w-xl text-sm leading-7 text-muted-foreground"
                            >
                                Show the receipts: Shipped reads the public
                                repository and marks the technologies the code
                                confirms.
                            </p>
                            <StackObservationPanel
                                class="mt-6"
                                :project-slug="project.slug"
                                :github-url="stackObservation.github_url"
                                :observed-at="stackObservation.observed_at"
                                :observed-slugs="
                                    stackObservation.observed_slugs
                                "
                            />
                        </div>
                    </section>
                    <section
                        id="studio-launch-kit"
                        class="scroll-mt-20 border-t border-foreground p-5 sm:p-8"
                        data-test="studio-launch-kit"
                    >
                        <div class="grid p-0 sm:grid-cols-[.45fr_1.55fr]">
                            <p class="technical-label text-primary">
                                05 / Launch kit
                            </p>
                            <div class="mt-8 sm:mt-0">
                                <h2 class="display-type text-4xl">
                                    Take it with you.
                                </h2>
                                <p
                                    class="mt-3 max-w-xl text-sm leading-7 text-muted-foreground"
                                >
                                    The share text, launch card, Ship Manifest,
                                    and README badge for this launch — every
                                    shareable asset in one place.
                                </p>
                                <div class="mt-6">
                                    <Button
                                        as-child
                                        variant="outline"
                                        data-test="open-launch-kit"
                                    >
                                        <Link
                                            :href="
                                                launchKit({
                                                    project: project.slug,
                                                })
                                            "
                                        >
                                            Open Launch Kit
                                            <ArrowUpRight class="size-4" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </section>
                    <BadgeSnippet
                        v-if="badgeMarkdown"
                        :markdown="badgeMarkdown"
                    />
                </div>
            </div>
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
