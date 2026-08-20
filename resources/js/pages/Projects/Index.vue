<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import SectionHeader from '@/components/shipped/SectionHeader.vue';
import StudioProjectRow from '@/components/shipped/StudioProjectRow.vue';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyTitle,
} from '@/components/ui/empty';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { create } from '@/routes/projects';

interface StudioProject {
    id: number;
    slug: string;
    name: string;
    tagline: string;
    is_public: boolean;
    verification_status: 'verified' | 'failed' | 'stale' | 'unverified';
    releases_count: number;
    category: {
        name: string;
    } | null;
}

const draftProjects = (projects: StudioProject[]): StudioProject[] =>
    projects.filter((project) => !project.is_public);

const publicProjects = (projects: StudioProject[]): StudioProject[] =>
    projects.filter((project) => project.is_public);

function draftNextStep(project: StudioProject): string {
    if (project.releases_count === 0) {
        return 'Write a release';
    }

    if (project.verification_status !== 'verified') {
        return 'Verify deployment';
    }

    return 'Make public';
}

function projectNextStep(project: StudioProject): string {
    if (!project.is_public) {
        return draftNextStep(project);
    }

    return project.verification_status === 'verified'
        ? 'Public launch'
        : 'Verify deployment';
}

defineProps<{
    projects: StudioProject[];
}>();
</script>

<template>
    <PublicShell title="Creator Studio">
        <section
            class="page-enter mx-auto flex w-full max-w-[90rem] min-w-0 flex-1 flex-col border-x border-b border-foreground"
        >
            <SectionHeader label="Creator studio / Workspace">
                <div
                    class="flex flex-col items-start gap-5 sm:flex-row sm:justify-between"
                >
                    <h1 class="display-type text-[clamp(3rem,7vw,7rem)]">
                        Your launches.
                    </h1>
                    <Button as-child class="w-full shrink-0 sm:w-auto"
                        ><Link :href="create()"
                            ><Plus class="size-4" />New launch</Link
                        ></Button
                    >
                </div>
                <p class="mt-6 max-w-xl text-muted-foreground">
                    Draft the record, publish the release, then make the project
                    public when it is ready.
                </p>
            </SectionHeader>
            <Tabs default-value="all" class="w-full min-w-0"
                ><div
                    class="overflow-x-auto border-b border-foreground p-5 sm:px-8"
                >
                    <TabsList
                        class="h-auto rounded-none border border-foreground bg-background p-0"
                        ><TabsTrigger
                            value="all"
                            class="rounded-none data-[state=active]:bg-foreground data-[state=active]:text-background"
                            >All projects</TabsTrigger
                        ><TabsTrigger
                            value="public"
                            class="rounded-none data-[state=active]:bg-foreground data-[state=active]:text-background"
                            >Public</TabsTrigger
                        ><TabsTrigger
                            value="draft"
                            class="rounded-none data-[state=active]:bg-foreground data-[state=active]:text-background"
                            >Drafts</TabsTrigger
                        ></TabsList
                    >
                </div>
                <TabsContent value="all" class="m-0"
                    ><div
                        v-if="projects.length"
                        class="divide-y divide-foreground"
                    >
                        <StudioProjectRow
                            v-for="project in projects"
                            :key="project.id"
                            :project="project"
                            :next-step="projectNextStep(project)"
                        />
                    </div>
                    <Empty v-else class="py-28"
                        ><EmptyHeader
                            ><EmptyTitle class="display-type text-4xl"
                                >Nothing launched yet.</EmptyTitle
                            ><EmptyDescription
                                >Create the first private project record, then
                                write its first release.</EmptyDescription
                            ></EmptyHeader
                        ><Button as-child
                            ><Link :href="create()"
                                >Begin a launch</Link
                            ></Button
                        ></Empty
                    ></TabsContent
                ><TabsContent value="public" class="m-0"
                    ><div class="divide-y divide-foreground">
                        <StudioProjectRow
                            v-for="project in publicProjects(projects)"
                            :key="project.id"
                            :project="project"
                            :next-step="projectNextStep(project)"
                        />
                        <p
                            v-if="!publicProjects(projects).length"
                            class="p-8 text-sm text-muted-foreground"
                        >
                            No public projects yet.
                        </p>
                    </div></TabsContent
                ><TabsContent value="draft" class="m-0"
                    ><div
                        v-if="draftProjects(projects).length"
                        class="divide-y divide-foreground"
                    >
                        <StudioProjectRow
                            v-for="project in draftProjects(projects)"
                            :key="project.id"
                            :project="project"
                            :next-step="draftNextStep(project)"
                        />
                    </div>
                    <p v-else class="p-8 text-sm text-muted-foreground">
                        No private drafts.
                    </p></TabsContent
                ></Tabs
            >
        </section>
    </PublicShell>
</template>
