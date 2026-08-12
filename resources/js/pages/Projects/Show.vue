<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ExternalLink, GitFork, Heart, ShieldCheck } from '@lucide/vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { defaultCoverUrl } from '@/lib/cover';
import { show as creatorShow } from '@/routes/creators';
import { store as cheer } from '@/routes/projects/cheers';
import { show as releaseShow } from '@/routes/releases';

defineProps<{ project: any }>();
const form = useForm({});

function cheerProject(project: any): void {
    form.post(cheer(project).url, {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <PublicShell :title="project.name">
        <section
            class="page-enter mx-auto w-full max-w-[90rem] min-w-0 border-x border-foreground"
        >
            <div
                class="grid border-b border-foreground lg:grid-cols-[minmax(0,1.1fr)_minmax(0,.9fr)]"
            >
                <div class="min-w-0 p-5 sm:p-8">
                    <div class="flex flex-wrap items-center gap-3">
                        <Badge variant="outline">{{
                            project.category.name
                        }}</Badge>
                        <span
                            v-if="project.filed_serial"
                            class="technical-label tabular-nums text-muted-foreground"
                            >{{ project.filed_serial }}</span
                        >
                        <span
                            v-if="project.verification_status === 'verified'"
                            class="technical-label inline-flex items-center gap-1 text-primary"
                            ><ShieldCheck class="size-4" />Verified Laravel
                            Cloud</span
                        >
                    </div>
                    <h1
                        class="display-type launch-name mt-12 text-[clamp(3.5rem,9vw,9rem)]"
                    >
                        {{ project.name }}
                    </h1>
                    <p class="mt-8 max-w-2xl text-lg leading-8">
                        {{ project.tagline }}
                    </p>
                    <p
                        class="font-prose mt-8 max-w-2xl text-[1.0625rem] leading-7 whitespace-pre-line text-foreground"
                    >
                        {{ project.description }}
                    </p>
                    <div class="mt-10 flex flex-wrap gap-2">
                        <Button v-if="project.live_url" as-child
                            ><a
                                :href="project.live_url"
                                target="_blank"
                                rel="noreferrer"
                                >Visit product
                                <ExternalLink class="size-4" /></a
                        ></Button>
                        <Button
                            v-if="project.github_url"
                            as-child
                            variant="outline"
                            ><a
                                :href="project.github_url"
                                target="_blank"
                                rel="noreferrer"
                                ><GitFork class="size-4" />Source</a
                            ></Button
                        >
                        <Button
                            v-if="$page.props.auth.user"
                            variant="outline"
                            :disabled="form.processing"
                            @click="cheerProject(project)"
                            ><Heart class="size-4" />Cheer /
                            {{ project.cheers_count }}</Button
                        >
                    </div>
                    <p class="technical-label mt-12">
                        Made by
                        <Link
                            :href="creatorShow(project.creator)"
                            class="text-primary underline underline-offset-4"
                            >@{{ project.creator.handle }}</Link
                        >
                    </p>
                </div>
                <div
                    class="relative min-w-0 border-t border-foreground bg-secondary lg:border-t-0 lg:border-l"
                >
                    <img
                        :src="project.cover_image_url ?? defaultCoverUrl(project)"
                        :alt="`${project.name} cover image`"
                        class="media-reveal size-full min-h-80 object-cover"
                        :class="{ grayscale: !!project.cover_image_url }"
                    />
                    <span
                        v-if="!project.cover_image_url"
                        class="technical-label absolute top-6 left-6 text-primary"
                        >Cover pending</span
                    >
                </div>
            </div>
            <div
                class="grid border-b border-foreground p-5 sm:grid-cols-[.45fr_1.55fr] sm:p-8"
            >
                <p class="technical-label text-primary">Release chronology</p>
                <ol
                    class="mt-10 divide-y divide-foreground border-y border-foreground sm:mt-0"
                >
                    <li
                        v-for="release in project.releases"
                        :key="release.id"
                        class="grid gap-4 py-5 sm:grid-cols-[10rem_1fr]"
                    >
                        <time class="technical-label text-muted-foreground">{{
                            new Date(release.published_at).toLocaleDateString(
                                undefined,
                                {
                                    year: 'numeric',
                                    month: 'short',
                                    day: 'numeric',
                                },
                            )
                        }}</time>
                        <div>
                            <h2 class="font-semibold">
                                <Link
                                    :href="
                                        releaseShow({
                                            creator: project.creator,
                                            project,
                                            release,
                                        })
                                    "
                                    class="underline decoration-primary underline-offset-4"
                                    >{{ release.title }}</Link
                                >
                            </h2>
                            <p
                                class="font-prose mt-3 text-sm leading-7 whitespace-pre-line"
                            >
                                {{ release.notes }}
                            </p>
                        </div>
                    </li>
                    <li
                        v-if="!project.releases.length"
                        class="py-5 text-sm text-muted-foreground"
                    >
                        No public release notes yet.
                    </li>
                </ol>
            </div>
        </section>
    </PublicShell>
</template>
