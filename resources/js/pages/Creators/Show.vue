<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Check, Copy, History, ShieldCheck } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import BrandIcon from '@/components/BrandIcon.vue';
import FollowButton from '@/components/shipped/FollowButton.vue';
import ProjectCard from '@/components/shipped/ProjectCard.vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import SectionHeader from '@/components/shipped/SectionHeader.vue';
import { Button } from '@/components/ui/button';
import { normalizeProfileLinkType, profileLinkLabel } from '@/lib/profileLinks';
import { show as projectShow } from '@/routes/projects';
import { show as releaseShow } from '@/routes/releases';
import {
    destroy as destroyFollow,
    store as storeFollow,
} from '@/routes/users/follow';
import type {
    CreatorProfile,
    ProjectCardData,
    ShippingHistoryEntry,
} from '@/types/creator';

const props = defineProps<{
    creator: CreatorProfile;
    profile_url: string;
    featured_projects: ProjectCardData[];
    shipping_history: ShippingHistoryEntry[];
}>();

const page = usePage();
const copied = ref(false);

const initials = computed(() =>
    props.creator.name
        .split(' ')
        .map((part) => part.charAt(0))
        .join('')
        .slice(0, 2)
        .toUpperCase(),
);

const statItems = computed(() => [
    { label: 'Public projects', value: props.creator.stats.public_projects },
    {
        label: 'Verified projects',
        value: props.creator.stats.verified_projects,
    },
    { label: 'Ship Stories', value: props.creator.stats.ship_stories },
    { label: 'Published releases', value: props.creator.stats.releases },
]);

function projectHref(project: ProjectCardData) {
    return projectShow({
        creator: { username: props.creator.username },
        project: { slug: project.slug },
    });
}

function releaseHref(entry: ShippingHistoryEntry) {
    if (entry.latest_release === null) {
        return '#';
    }

    return releaseShow({
        creator: { username: props.creator.username },
        project: { slug: entry.project.slug },
        release: { id: entry.latest_release.id },
    });
}

function historyDate(entry: ShippingHistoryEntry): string {
    const date =
        entry.latest_release?.published_at ?? entry.project.launch_date;

    if (!date) {
        return 'Filed record';
    }

    return new Date(date).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

async function copyProfileLink(): Promise<void> {
    try {
        await navigator.clipboard.writeText(props.profile_url);
        copied.value = true;
        toast.success('Shipping profile link copied.');
        window.setTimeout(() => {
            copied.value = false;
        }, 2200);
    } catch {
        toast('Copy the profile link from your browser.');
    }
}
</script>

<template>
    <PublicShell :title="creator.name + ' — Shipping Profile'">
        <section
            class="page-enter mx-auto w-full max-w-[90rem] min-w-0 border-x border-b border-foreground"
        >
            <SectionHeader :label="'Shipping profile / @' + creator.username">
                <div class="flex flex-wrap items-start justify-between gap-6">
                    <div
                        class="grid size-20 shrink-0 place-items-center overflow-hidden border border-foreground bg-secondary font-display text-2xl"
                    >
                        <img
                            v-if="creator.avatar_url"
                            :src="creator.avatar_url"
                            :alt="creator.name + ' avatar'"
                            class="size-full object-cover"
                            data-test="creator-avatar"
                        />
                        <span v-else aria-hidden="true">{{ initials }}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Button
                            variant="outline"
                            class="min-h-11"
                            data-test="copy-profile-link"
                            @click="copyProfileLink"
                        >
                            <Check v-if="copied" class="size-4" />
                            <Copy v-else class="size-4" />
                            {{ copied ? 'Copied' : 'Copy profile link' }}
                        </Button>
                        <FollowButton
                            v-if="
                                page.props.auth.user?.username !==
                                creator.username
                            "
                            :key="creator.username"
                            :count="creator.followers_count"
                            :following="creator.followed_by_viewer"
                            :action="
                                creator.followed_by_viewer
                                    ? {
                                          ...destroyFollow(creator),
                                          method: 'delete' as const,
                                      }
                                    : {
                                          ...storeFollow(creator),
                                          method: 'post' as const,
                                      }
                            "
                            class="min-h-11"
                        />
                    </div>
                </div>
                <div
                    class="technical-label mt-6 text-muted-foreground sm:mt-8"
                    data-test="creator-followers-count"
                >
                    {{ creator.followers_count }}
                    {{
                        creator.followers_count === 1 ? 'follower' : 'followers'
                    }}
                </div>
                <h1
                    class="display-type mt-6 max-w-5xl text-[clamp(3rem,7vw,7rem)] break-words sm:mt-3"
                >
                    {{ creator.name }}
                </h1>
                <p
                    v-if="creator.title"
                    class="technical-label mt-4 text-primary"
                >
                    {{ creator.title }}
                </p>
                <p
                    v-if="creator.location"
                    class="mt-2 text-sm text-muted-foreground"
                >
                    {{ creator.location }}
                </p>
                <p
                    v-if="creator.bio"
                    class="mt-6 max-w-2xl leading-7 text-muted-foreground"
                >
                    {{ creator.bio }}
                </p>
                <ul
                    v-if="creator.links?.length"
                    class="mt-6 flex flex-wrap gap-3"
                >
                    <li
                        v-for="link in creator.links"
                        :key="link.type + link.url"
                    >
                        <a
                            :href="link.url"
                            class="technical-label inline-flex min-h-11 items-center gap-2 px-1 text-primary underline underline-offset-4 transition-colors hover:bg-secondary"
                            target="_blank"
                            rel="noreferrer"
                        >
                            <BrandIcon
                                :brand="normalizeProfileLinkType(link.type)"
                                class="size-3.5"
                                aria-hidden="true"
                            />
                            {{ profileLinkLabel(link.type) }}
                        </a>
                    </li>
                </ul>
            </SectionHeader>

            <div
                class="grid grid-cols-2 border-b border-foreground bg-background sm:grid-cols-4"
                data-test="creator-profile-stats"
            >
                <div
                    v-for="(stat, statIndex) in statItems"
                    :key="stat.label"
                    :class="[
                        'bg-background p-5 sm:p-6',
                        statIndex % 2 === 0 ? 'border-r border-foreground' : '',
                        statIndex < 2
                            ? 'border-b border-foreground sm:border-b-0'
                            : '',
                        statIndex < 3 ? 'sm:border-r' : 'sm:border-r-0',
                    ]"
                >
                    <p class="display-type text-4xl tabular-nums">
                        {{ stat.value }}
                    </p>
                    <p class="technical-label mt-2 text-muted-foreground">
                        {{ stat.label }}
                    </p>
                </div>
            </div>

            <SectionHeader label="Public proof / curated">
                <div class="space-y-5">
                    <h2 class="display-type text-3xl sm:text-4xl">
                        Featured projects
                    </h2>
                    <div
                        v-if="featured_projects.length"
                        class="grid gap-px border border-foreground sm:grid-cols-2 xl:grid-cols-3"
                        data-test="featured-projects"
                    >
                        <ProjectCard
                            v-for="project in featured_projects"
                            :key="project.id"
                            :project="project"
                        />
                    </div>
                    <p
                        v-else
                        class="border border-dashed border-foreground bg-secondary p-6 text-sm leading-6 text-muted-foreground"
                    >
                        Featured projects will appear here when this Creator
                        curates their shipping profile.
                    </p>
                </div>
            </SectionHeader>

            <SectionHeader label="Public proof / history">
                <div class="space-y-5">
                    <div>
                        <h2 class="display-type text-3xl sm:text-4xl">
                            Shipping history
                        </h2>
                        <p class="mt-2 text-sm text-muted-foreground">
                            One public record for each shipped Project.
                        </p>
                    </div>
                    <div
                        v-if="shipping_history.length"
                        class="border-y border-foreground"
                        data-test="shipping-history"
                    >
                        <article
                            v-for="entry in shipping_history"
                            :key="entry.project.id"
                            class="grid gap-5 border-b border-foreground py-6 last:border-b-0 sm:grid-cols-[10rem_1fr_auto] sm:gap-8 sm:py-8"
                        >
                            <time
                                class="technical-label pt-1 text-muted-foreground"
                            >
                                {{ historyDate(entry) }}
                            </time>
                            <div class="min-w-0">
                                <div
                                    class="flex flex-wrap items-center gap-x-3 gap-y-2"
                                >
                                    <h3
                                        class="display-type text-3xl break-words"
                                    >
                                        <Link
                                            :href="projectHref(entry.project)"
                                            class="underline decoration-primary underline-offset-4"
                                            >{{ entry.project.name }}</Link
                                        >
                                    </h3>
                                    <span
                                        v-if="
                                            entry.project
                                                .verification_status ===
                                            'verified'
                                        "
                                        class="technical-label inline-flex items-center gap-1 text-primary"
                                    >
                                        <ShieldCheck
                                            class="size-3"
                                            aria-hidden="true"
                                        />
                                        Verified
                                    </span>
                                </div>
                                <p
                                    v-if="entry.project.tagline"
                                    class="mt-2 text-sm leading-6 text-muted-foreground"
                                >
                                    {{ entry.project.tagline }}
                                </p>
                                <p
                                    v-if="entry.latest_release"
                                    class="mt-4 text-sm"
                                >
                                    <span class="technical-label text-primary"
                                        >Latest release</span
                                    >
                                    <Link
                                        :href="releaseHref(entry)"
                                        class="ml-2 underline decoration-primary underline-offset-4"
                                    >
                                        {{ entry.latest_release.title }}
                                    </Link>
                                </p>
                                <p
                                    v-if="entry.ship_story_excerpt"
                                    class="mt-4 max-w-2xl border-l-2 border-primary pl-3 text-sm leading-6"
                                >
                                    {{ entry.ship_story_excerpt }}
                                </p>
                            </div>
                            <div
                                class="flex items-start justify-between gap-4 sm:block sm:text-right"
                            >
                                <p
                                    class="technical-label text-muted-foreground"
                                >
                                    {{ entry.release_count }}
                                    {{
                                        entry.release_count === 1
                                            ? 'release'
                                            : 'releases'
                                    }}
                                </p>
                                <Link
                                    :href="projectHref(entry.project)"
                                    class="technical-label mt-2 inline-flex min-h-11 items-center gap-1 text-primary underline underline-offset-4 sm:mt-3"
                                >
                                    View project
                                    <ArrowUpRight
                                        class="size-3"
                                        aria-hidden="true"
                                    />
                                </Link>
                            </div>
                        </article>
                    </div>
                    <div
                        v-else
                        class="border border-dashed border-foreground bg-secondary p-8 text-sm leading-6 text-muted-foreground"
                    >
                        No public shipping records yet.
                    </div>
                </div>
            </SectionHeader>

            <div
                class="flex flex-wrap items-center justify-between gap-4 border-t border-foreground bg-secondary p-5 sm:p-8"
            >
                <p class="technical-label text-muted-foreground">
                    Public evidence from verified Projects, Releases, and
                    approved Ship Stories.
                </p>
                <Link
                    v-if="shipping_history[0]"
                    :href="projectHref(shipping_history[0].project)"
                    class="technical-label inline-flex min-h-11 items-center gap-1 text-primary underline underline-offset-4"
                >
                    Browse latest proof
                    <History class="size-3" aria-hidden="true" />
                </Link>
            </div>
        </section>
    </PublicShell>
</template>
