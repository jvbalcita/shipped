<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, ExternalLink } from '@lucide/vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { show as creatorShow } from '@/routes/creators';
import { show as projectShow } from '@/routes/projects';

defineProps<{ release: any }>();
</script>

<template>
    <PublicShell :title="release.title">
        <section
            class="page-enter mx-auto w-full max-w-[90rem] min-w-0 border-x border-b border-foreground"
        >
            <div class="border-b border-foreground p-5 sm:p-8">
                <Link
                    :href="
                        projectShow({
                            creator: release.project.creator,
                            project: release.project,
                        })
                    "
                    class="technical-label inline-flex items-center gap-2 text-primary underline underline-offset-4"
                    ><ArrowLeft class="size-4" />Back to project</Link
                >
            </div>
            <div
                class="grid border-b border-foreground sm:grid-cols-[.45fr_1.55fr]"
            >
                <div
                    class="border-b border-foreground p-5 sm:border-r sm:border-b-0 sm:p-8"
                >
                    <p class="technical-label text-primary">Release record</p>
                    <time
                        class="technical-label mt-12 block text-muted-foreground"
                        >{{
                            new Date(release.published_at).toLocaleDateString(
                                undefined,
                                {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                },
                            )
                        }}</time
                    >
                </div>
                <div class="p-5 sm:p-8">
                    <div class="flex flex-wrap items-center gap-3">
                        <Badge variant="outline">{{
                            release.project.category.name
                        }}</Badge>
                        <span
                            v-if="release.project.filed_serial"
                            class="technical-label tabular-nums text-muted-foreground"
                            >{{ release.project.filed_serial }}</span
                        >
                        <Link
                            :href="
                                projectShow({
                                    creator: release.project.creator,
                                    project: release.project,
                                })
                            "
                            class="technical-label text-primary underline underline-offset-4"
                            >{{ release.project.name }}</Link
                        >
                    </div>
                    <h1
                        class="display-type mt-12 max-w-5xl text-[clamp(3.5rem,8vw,8rem)]"
                    >
                        {{ release.title }}
                    </h1>
                    <p
                        class="font-prose mt-8 max-w-3xl text-[1.0625rem] leading-8 whitespace-pre-line text-foreground"
                    >
                        {{ release.notes }}
                    </p>
                    <div class="mt-10 flex flex-wrap gap-2">
                        <Button v-if="release.project.live_url" as-child
                            ><a
                                :href="release.project.live_url"
                                target="_blank"
                                rel="noreferrer"
                                >Visit product
                                <ExternalLink class="size-4" /></a
                        ></Button>
                        <Button as-child variant="outline"
                            ><Link
                                :href="
                                    projectShow({
                                        creator: release.project.creator,
                                        project: release.project,
                                    })
                                "
                                >Back to project</Link
                            ></Button
                        >
                    </div>
                    <p class="technical-label mt-12">
                        Made by
                        <Link
                            :href="creatorShow(release.project.creator)"
                            class="text-primary underline underline-offset-4"
                            >@{{ release.project.creator.handle }}</Link
                        >
                    </p>
                </div>
            </div>
        </section>
    </PublicShell>
</template>
