<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowUpRight } from '@lucide/vue';
import { computed } from 'vue';
import ProjectCard from '@/components/shipped/ProjectCard.vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import { Button } from '@/components/ui/button';
import { discover, register } from '@/routes';
import { create } from '@/routes/projects';
import type { ProjectCardData } from '@/types/creator';

const page = usePage();

defineProps<{
    launchCount: number;
    creatorCount: number;
    latestDispatchAt: string | null;
    recentLaunches: ProjectCardData[];
}>();

const latestDispatchLabel = computed((): string => {
    const props = (page.props as { latestDispatchAt?: string | null }).latestDispatchAt;

    if (!props) {
return 'Awaiting first dispatch';
}

    const then = new Date(props).getTime();
    const minutes = Math.round((Date.now() - then) / 60000);
    const rtf = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });

    if (minutes < 1) {
return 'Filed just now';
}

    if (minutes < 60) {
return `Filed ${rtf.format(-minutes, 'minute')}`;
}

    const hours = Math.round(minutes / 60);

    if (hours < 24) {
return `Filed ${rtf.format(-hours, 'hour')}`;
}

    const days = Math.round(hours / 24);

    return `Filed ${rtf.format(-days, 'day')}`;
});

// Sparse registries would leave black gap-px backdrop showing through
// empty grid cells, so the grid follows the card count like Discover.
const launchesGridClass = computed(() => {
    const props = (page.props as { recentLaunches?: ProjectCardData[] }).recentLaunches ?? [];

    if (props.length === 1) {
        return 'max-w-2xl grid-cols-1';
    }

    if (props.length === 2) {
        return 'md:grid-cols-2';
    }

    return 'md:grid-cols-2 xl:grid-cols-3';
});
</script>

<template>
    <PublicShell title="A public home for launches">
        <section class="page-enter landing-hero border-b border-foreground">
            <div
                class="mx-auto grid w-full max-w-[90rem] border-x border-foreground lg:grid-cols-[1.1fr_.9fr]"
            >
                <div
                    class="flex min-h-[31rem] flex-col p-6 sm:min-h-[34rem] sm:p-10 lg:min-h-[42rem] lg:p-14"
                >
                    <p
                        class="landing-reveal landing-reveal-kicker technical-label text-primary"
                    >
                        A public registry for independent launches
                    </p>
                    <div class="mt-auto">
                        <p
                            class="landing-reveal landing-reveal-status technical-label mb-5 text-muted-foreground"
                        >
                            Status / ready for the record
                        </p>
                        <h1
                            class="landing-reveal landing-reveal-title display-type max-w-4xl text-[clamp(4.15rem,10vw,9rem)]"
                        >
                            Make it<br />public.
                        </h1>
                    </div>
                    <div
                        class="landing-reveal landing-reveal-actions mt-8 max-w-xl"
                    >
                        <p class="text-base leading-7 sm:text-lg">
                            Shipped gives a finished product a permanent home:
                            the work, its release story, and the person who made
                            it.
                        </p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            <Button as-child size="lg"
                                ><Link
                                    :href="
                                        page.props.auth.user
                                            ? create()
                                            : register()
                                    "
                                    >Ship yours
                                    <ArrowUpRight
                                        class="size-4" /></Link></Button
                            ><Button as-child size="lg" variant="outline"
                                ><Link :href="discover()"
                                    >Browse launches</Link
                                ></Button
                            >
                        </div>
                    </div>
                </div>
                <figure
                    class="landing-record group grid min-h-[22rem] grid-rows-[1fr_auto] border-t border-foreground bg-secondary sm:min-h-[30rem] lg:min-h-[42rem] lg:border-t-0 lg:border-l"
                >
                    <div class="relative min-h-0 overflow-hidden">
                        <img
                            src="/images/shipped/hero-worktable.png"
                            alt="A worktable prepared for a product launch"
                            width="1792"
                            height="1024"
                            fetchpriority="high"
                            class="landing-record-image media-reveal size-full min-h-0 object-cover contrast-125 grayscale"
                        />
                        <div
                            class="landing-record-stamp technical-label absolute top-5 left-5 border border-foreground bg-background px-3 py-2 text-primary sm:top-6 sm:left-6"
                        >
                            Filed / public
                        </div>
                        <p
                            class="technical-label absolute right-0 bottom-0 bg-foreground px-4 py-3 text-background"
                        >
                            A durable URL
                        </p>
                    </div>
                    <figcaption
                        class="grid border-t border-foreground bg-background p-5 sm:grid-cols-[10rem_1fr]"
                    >
                        <span class="technical-label text-primary"
                            >The record</span
                        ><span class="mt-4 text-sm leading-6 sm:mt-0"
                            >A launch page that people can revisit after the
                            moment has passed.</span
                        >
                    </figcaption>
                </figure>
            </div>
            <div
                class="mx-auto grid w-full max-w-[90rem] gap-px border-x border-b border-foreground bg-foreground sm:grid-cols-3"
                aria-label="Registry status"
            >
                <div
                    class="flex items-baseline gap-2 bg-background px-5 py-4 sm:px-8"
                    data-test="stat-launch-count"
                >
                    <template v-if="launchCount > 0">
                        <span class="font-display text-2xl tabular-nums sm:text-3xl">{{
                            launchCount
                        }}</span>
                        <span class="technical-label text-muted-foreground"
                            >Launches filed</span
                        >
                    </template>
                    <span v-else class="technical-label text-muted-foreground"
                        >First records are being filed</span
                    >
                </div>
                <div
                    class="flex items-baseline gap-2 bg-background px-5 py-4 sm:px-8"
                    data-test="stat-creator-count"
                >
                    <template v-if="creatorCount > 0">
                        <span class="font-display text-2xl tabular-nums sm:text-3xl">{{
                            creatorCount
                        }}</span>
                        <span class="technical-label text-muted-foreground"
                            >Creators on record</span
                        >
                    </template>
                    <span v-else class="technical-label text-muted-foreground"
                        >Builders are claiming their records</span
                    >
                </div>
                <div
                    class="flex items-baseline gap-2 bg-background px-5 py-4 sm:px-8"
                >
                    <span class="technical-label text-primary">{{
                        latestDispatchLabel
                    }}</span>
                </div>
            </div>
            <Link
                :href="discover()"
                class="landing-ticker mx-auto block w-full max-w-[90rem] overflow-hidden border-x border-foreground bg-foreground text-background focus-visible:outline-3 focus-visible:outline-offset-[-3px] focus-visible:outline-ring"
                aria-label="Browse the live Shipped registry"
            >
                <span class="landing-ticker-track" aria-hidden="true">
                    <span>Live registry</span><i>✦</i><span>Public work</span
                    ><i>✦</i><span>Release stories</span><i>✦</i
                    ><span>Independent launches</span><i>✦</i
                    ><span>Live registry</span><i>✦</i><span>Public work</span
                    ><i>✦</i><span>Release stories</span><i>✦</i
                    ><span>Independent launches</span><i>✦</i>
                </span>
            </Link>
        </section>
        <section
            class="mx-auto w-full max-w-[90rem] border-x border-b border-foreground"
        >
            <div
                class="grid min-w-0 border-b border-foreground p-6 sm:grid-cols-[12rem_minmax(0,1fr)] sm:p-10"
            >
                <p class="technical-label text-primary">What happens here</p>
                <h2
                    class="display-type mt-10 max-w-4xl text-[clamp(2.5rem,6vw,6rem)] break-words sm:mt-0"
                >
                    A launch deserves a record, not a disappearing link.
                </h2>
            </div>
            <ol
                class="grid divide-y divide-foreground sm:grid-cols-3 sm:divide-x sm:divide-y-0"
            >
                <li>
                    <Link
                        :href="page.props.auth.user ? create() : register()"
                        class="launch-step group"
                    >
                        <span class="technical-label text-primary"
                            >01 / Record</span
                        >
                        <strong class="mt-10 font-display text-2xl uppercase"
                            >Declare it</strong
                        >
                        <span class="mt-4 max-w-xs text-sm leading-6"
                            >Set the product’s identity, category, cover, and
                            place to try it.</span
                        >
                        <ArrowUpRight
                            class="launch-step-arrow mt-auto size-5"
                        />
                    </Link>
                </li>
                <li>
                    <Link
                        :href="page.props.auth.user ? create() : register()"
                        class="launch-step group"
                    >
                        <span class="technical-label text-primary"
                            >02 / Release</span
                        >
                        <strong class="mt-10 font-display text-2xl uppercase"
                            >Tell the story</strong
                        >
                        <span class="mt-4 max-w-xs text-sm leading-6"
                            >Write what changed and decide when the release
                            becomes public.</span
                        >
                        <ArrowUpRight
                            class="launch-step-arrow mt-auto size-5"
                        />
                    </Link>
                </li>
                <li>
                    <Link :href="discover()" class="launch-step group">
                        <span class="technical-label text-primary"
                            >03 / Discover</span
                        >
                        <strong class="mt-10 font-display text-2xl uppercase"
                            >Send it out</strong
                        >
                        <span class="mt-4 max-w-xs text-sm leading-6"
                            >Give the community one durable place to find the
                            work and cheer it on.</span
                        >
                        <ArrowUpRight
                            class="launch-step-arrow mt-auto size-5"
                        />
                    </Link>
                </li>
            </ol>
        </section>
        <section
            v-if="recentLaunches.length"
            class="mx-auto w-full max-w-[90rem] border-x border-b border-foreground"
            data-test="fresh-launches"
        >
            <div
                class="flex flex-wrap items-end justify-between gap-4 border-b border-foreground p-6 sm:p-10"
            >
                <div>
                    <p class="technical-label text-primary">
                        Live registry / Latest dispatches
                    </p>
                    <h2 class="display-type mt-4 text-4xl sm:text-5xl">
                        Freshly filed.
                    </h2>
                </div>
                <Link
                    :href="discover()"
                    class="technical-label inline-flex min-h-11 items-center gap-1 text-primary underline underline-offset-4"
                    >Browse all launches
                    <ArrowUpRight class="size-3" aria-hidden="true" /></Link
                >
            </div>
            <div class="grid gap-px bg-foreground" :class="launchesGridClass">
                <ProjectCard
                    v-for="project in recentLaunches"
                    :key="project.id"
                    :project="project"
                />
            </div>
        </section>
        <section
            class="landing-final-cta mx-auto flex w-full max-w-[90rem] flex-col items-start gap-6 border-x border-b border-foreground p-6 sm:flex-row sm:items-center sm:justify-between sm:p-10"
        >
            <p class="display-type text-4xl sm:text-6xl">Ready to ship?</p>
            <Button as-child size="lg" class="w-full sm:w-auto"
                ><Link :href="page.props.auth.user ? create() : register()"
                    >Open composer <ArrowUpRight class="size-4" /></Link
            ></Button>
        </section>
    </PublicShell>
</template>
