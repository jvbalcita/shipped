<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PublicShell from '@/components/shipped/PublicShell.vue';
import SectionHeader from '@/components/shipped/SectionHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyTitle,
} from '@/components/ui/empty';
import { discover } from '@/routes';
import { show as technologyShow } from '@/routes/technologies';

defineProps<{
    groups: {
        group: string;
        label: string;
        technologies: {
            id: number;
            name: string;
            slug: string;
            projects_count: number;
        }[];
    }[];
}>();
</script>

<template>
    <PublicShell title="Built With">
        <section
            class="page-enter mx-auto w-full max-w-[90rem] min-w-0 border-x border-b border-foreground"
        >
            <SectionHeader label="Public registry / Browse by stack">
                <h1
                    class="display-type mt-12 max-w-4xl text-[clamp(3.25rem,6.5vw,6.75rem)] sm:mt-0"
                >
                    Built with.
                </h1>
                <p
                    class="mt-6 max-w-xl text-sm leading-6 text-muted-foreground"
                >
                    The curated stack vocabulary behind every verified launch.
                    Follow a technology to see the Laravel builders who shipped
                    with it.
                </p>
            </SectionHeader>
            <div class="grid gap-px bg-foreground">
                <section
                    v-for="group in groups"
                    :key="group.group"
                    class="bg-background p-5 sm:p-8"
                    :data-test="`technology-group-${group.group}`"
                >
                    <div class="flex items-baseline justify-between gap-4">
                        <h2 class="technical-label text-primary">
                            {{ group.label }}
                        </h2>
                        <span
                            class="technical-label text-muted-foreground tabular-nums"
                        >
                            {{ group.technologies.length }} entries
                        </span>
                    </div>
                    <ul class="mt-4 flex flex-wrap gap-2">
                        <li
                            v-for="technology in group.technologies"
                            :key="technology.slug"
                        >
                            <Link
                                :href="technologyShow(technology)"
                                class="technical-label inline-flex items-center gap-2 border border-foreground px-3 py-1.5 transition-colors hover:bg-primary hover:text-primary-foreground"
                                :class="
                                    technology.projects_count === 0
                                        ? 'text-muted-foreground'
                                        : ''
                                "
                                :data-test="`technology-link-${technology.slug}`"
                            >
                                {{ technology.name }}
                                <span class="tabular-nums">{{
                                    technology.projects_count
                                }}</span>
                            </Link>
                        </li>
                    </ul>
                </section>
            </div>
            <Empty class="border-0 bg-background py-16"
                ><EmptyHeader
                    ><EmptyTitle
                        >Stacks become interesting with launches.</EmptyTitle
                    ><EmptyDescription
                        >Builders declare the stack behind each project, and
                        this directory fills in as they ship.</EmptyDescription
                    ></EmptyHeader
                ><Button as-child variant="outline"
                    ><Link :href="discover()">Browse launches</Link></Button
                ></Empty
            >
        </section>
    </PublicShell>
</template>
