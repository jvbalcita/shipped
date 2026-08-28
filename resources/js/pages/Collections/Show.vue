<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ProjectCard from '@/components/shipped/ProjectCard.vue';
import ProjectCardSkeleton from '@/components/shipped/ProjectCardSkeleton.vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import SectionHeader from '@/components/shipped/SectionHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import { recordProductEvent } from '@/lib/productEvents';
import { discover } from '@/routes';
import { index as collectionsIndex } from '@/routes/collections';

const props = defineProps<{
    collection: {
        id: number;
        title: string;
        slug: string;
        description: string;
        cover_image_url: string | null;
        updated_at: string;
    };
    projects: {
        data: any[];
        current_page: number;
        last_page: number;
        total: number;
        from?: number;
        to?: number;
    };
}>();

const loading = ref(false);
const totalPages = computed(() => props.projects.last_page);

const projectGridClass = computed(() => {
    if (props.projects.data.length === 1) {
        return 'max-w-2xl grid-cols-1';
    }

    return 'md:grid-cols-2 xl:grid-cols-3';
});

const resultsLabel = computed(() => {
    const { total, from, to } = props.projects;

    if (total === 0) {
        return 'No records';
    }

    if (total === 1) {
        return '1 record';
    }

    return `${from}–${to} of ${total} records`;
});

function visitPage(page: number) {
    loading.value = true;
    router.get(
        window.location.pathname,
        { page },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                loading.value = false;
            },
        },
    );
}

// Roadmap evidence: a visitor clicking through from the curated page
// into a member project. Capture-phase so links inside the card count.
function recordMemberClick(event: MouseEvent): void {
    const target = event.target as HTMLElement | null;

    if (target === null) {
        return;
    }

    const card = target.closest('[data-member-id]');
    const link = target.closest('a');

    if (card === null || link === null) {
        return;
    }

    recordProductEvent('collection_project_clicked', {
        projectId: Number(card.getAttribute('data-member-id')),
        collectionId: props.collection.id,
    });
}
</script>

<template>
    <PublicShell :title="collection.title">
        <section
            class="page-enter mx-auto w-full min-w-0 border-x border-b border-foreground"
        >
            <SectionHeader label="Public registry / Curated collection">
                <h1
                    class="display-type mt-12 max-w-4xl text-[clamp(3rem,6.5vw,6.75rem)] sm:mt-0"
                >
                    {{ collection.title }}
                </h1>
                <p
                    class="mt-6 max-w-2xl text-sm leading-6 text-muted-foreground"
                >
                    {{ collection.description }}
                </p>
            </SectionHeader>
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b border-foreground bg-background px-5 py-3 sm:px-8"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <span class="technical-label tabular-nums">{{
                        resultsLabel
                    }}</span>
                </div>
                <Button as-child variant="outline" size="sm"
                    ><Link :href="collectionsIndex()"
                        >All collections</Link
                    ></Button
                >
            </div>
            <div
                v-if="loading"
                class="grid gap-3 bg-secondary p-3 sm:gap-4 sm:p-4"
                :class="projectGridClass"
                aria-label="Loading launches"
            >
                <ProjectCardSkeleton :count="projects.data.length || 9" />
            </div>
            <div
                v-else-if="projects.data.length"
                class="grid gap-3 bg-secondary p-3 sm:gap-4 sm:p-4"
                :class="projectGridClass"
                @click.capture="recordMemberClick"
            >
                <div
                    v-for="project in projects.data"
                    :key="project.id"
                    :data-member-id="project.id"
                >
                    <ProjectCard :project="project" />
                </div>
            </div>
            <Empty v-else class="border-0 bg-background py-24"
                ><EmptyHeader
                    ><EmptyTitle>This collection is between picks.</EmptyTitle
                    ><EmptyDescription
                        >Its members are being re-verified; check back
                        soon.</EmptyDescription
                    ></EmptyHeader
                ><Button as-child variant="outline"
                    ><Link :href="discover()">Browse all launches</Link></Button
                ></Empty
            >
            <Pagination
                v-if="totalPages > 1"
                v-slot="{ page }"
                :items-per-page="9"
                :total="totalPages * 9"
                :default-page="projects.current_page"
                class="border-t border-foreground py-6"
                ><PaginationContent
                    ><PaginationPrevious
                        @click="
                            visitPage(Math.max(1, projects.current_page - 1))
                        " /><PaginationItem
                        v-for="item in totalPages"
                        :key="item"
                        :value="item"
                        :is-active="item === page"
                        @click="visitPage(item)"
                        >{{ item }}</PaginationItem
                    ><PaginationNext
                        @click="
                            visitPage(
                                Math.min(totalPages, projects.current_page + 1),
                            )
                        " /></PaginationContent
            ></Pagination>
        </section>
    </PublicShell>
</template>
