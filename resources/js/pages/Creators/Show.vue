<script setup lang="ts">
import ProjectCard from '@/components/shipped/ProjectCard.vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
defineProps<{ creator: any; projects: any[] }>();
</script>

<template>
    <PublicShell :title="`@${creator.handle}`"
        ><section
            class="page-enter mx-auto w-full max-w-[90rem] min-w-0 border-x border-b border-foreground"
        >
            <div
                class="grid border-b border-foreground p-5 sm:grid-cols-[.45fr_1.55fr] sm:p-8"
            >
                <p class="technical-label text-primary">
                    Creator file / @{{ creator.handle }}
                </p>
                <div>
                    <h1
                        class="display-type mt-12 text-[clamp(3rem,7vw,7rem)] sm:mt-0"
                    >
                        {{ creator.name }}
                    </h1>
                    <p
                        v-if="creator.bio"
                        class="mt-6 max-w-2xl leading-7 text-muted-foreground"
                    >
                        {{ creator.bio }}
                    </p>
                </div>
            </div>
            <div
                class="grid gap-px bg-foreground md:grid-cols-2 xl:grid-cols-3"
            >
                <ProjectCard
                    v-for="project in projects"
                    :key="project.id"
                    :project="project"
                />
            </div>
            <p
                v-if="!projects.length"
                class="p-12 text-sm text-muted-foreground"
            >
                No public launches in this file.
            </p>
        </section></PublicShell
    >
</template>
