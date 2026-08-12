<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight } from '@lucide/vue';
import { edit } from '@/routes/projects';

interface StudioProject {
    id: number;
    slug: string;
    name: string;
    tagline: string;
    is_public: boolean;
    releases_count: number;
    filed_serial?: string | null;
    category: {
        name: string;
    } | null;
}

defineProps<{
    project: StudioProject;
    nextStep: string;
}>();
</script>

<template>
    <Link
        :href="edit(project)"
        class="group grid gap-4 p-5 transition-colors hover:bg-secondary sm:grid-cols-[8rem_minmax(0,1fr)_auto] sm:items-center sm:p-8"
    >
        <div class="grid content-start gap-2">
            <p class="technical-label text-primary">
                {{ project.is_public ? 'Public' : 'Private' }}
            </p>
            <span
                v-if="project.filed_serial"
                class="technical-label tabular-nums text-muted-foreground"
            >
                {{ project.filed_serial }}
            </span>
            <span
                class="w-fit border border-foreground px-1.5 py-0.5 font-mono text-[9px] leading-3 tracking-[0.08em] uppercase"
            >
                {{ project.category?.name ?? 'Uncategorised' }}
            </span>
        </div>

        <div class="min-w-0">
            <h2 class="display-type text-3xl break-words sm:text-4xl">
                {{ project.name }}
            </h2>
            <p class="mt-3 line-clamp-2 text-sm text-muted-foreground">
                {{ project.tagline }}
            </p>
            <p class="technical-label mt-4 text-muted-foreground">
                {{ project.releases_count }}
                {{ project.releases_count === 1 ? 'release' : 'releases' }}
            </p>
        </div>

        <div
            class="flex flex-wrap items-center justify-between gap-4 sm:flex-col sm:items-end sm:justify-center"
        >
            <span class="technical-label text-primary">{{ nextStep }}</span>
            <span
                class="technical-label inline-flex items-center gap-2 group-hover:underline"
            >
                Open record <ArrowUpRight class="size-4" />
            </span>
        </div>
    </Link>
</template>
