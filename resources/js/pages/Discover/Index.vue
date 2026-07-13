<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import ProjectCard from '@/components/shipped/ProjectCard.vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { discover } from '@/routes';

const props = defineProps<{
    projects: { data: any[]; current_page: number; last_page: number };
    categories: any[];
    filters: { q?: string; category?: string };
}>();
const search = ref(props.filters.q ?? '');
const category = ref(props.filters.category ?? 'all');
const totalPages = computed(() => props.projects.last_page);
const projectGridClass = computed(() => {
    if (props.projects.data.length === 1) {
        return 'max-w-2xl grid-cols-1';
    }

    return 'md:grid-cols-2 xl:grid-cols-3';
});

function applyFilters(page = 1) {
    router.get(
        discover().url,
        {
            q: search.value || undefined,
            category: category.value === 'all' ? undefined : category.value,
            page,
        },
        { preserveScroll: true, preserveState: true, replace: true },
    );
}
</script>

<template>
    <PublicShell title="Discover launches">
        <section
            class="page-enter mx-auto w-full max-w-[90rem] min-w-0 border-x border-b border-foreground"
        >
            <div
                class="grid border-b border-foreground p-5 sm:grid-cols-[.45fr_1.55fr] sm:p-8"
            >
                <p class="technical-label text-primary">
                    Public registry / Browse
                </p>
                <div>
                    <h1
                        class="display-type mt-12 max-w-4xl text-[clamp(3.25rem,6.5vw,6.75rem)] sm:mt-0"
                    >
                        Explore launches.
                    </h1>
                    <p
                        class="mt-6 max-w-xl text-sm leading-6 text-muted-foreground"
                    >
                        Public records from Laravel builders, searchable by name
                        and category.
                    </p>
                </div>
            </div>
            <div
                class="grid border-b border-foreground bg-transparent md:grid-cols-[1fr_15rem_auto]"
            >
                <InputGroup class="h-10 bg-background"
                    ><InputGroupAddon
                        ><Search
                            class="size-4"
                            aria-hidden="true" /></InputGroupAddon
                    ><InputGroupInput
                        v-model="search"
                        name="q"
                        autocomplete="off"
                        placeholder="Search launches…"
                        @keyup.enter="applyFilters()"
                /></InputGroup>
                <Select v-model="category" @update:model-value="applyFilters()"
                    ><SelectTrigger
                        class="h-10 w-full rounded-none border-foreground bg-background text-foreground"
                        ><SelectValue
                            placeholder="All categories" /></SelectTrigger
                    ><SelectContent
                        ><SelectItem value="all">All categories</SelectItem
                        ><SelectItem
                            v-for="item in categories"
                            :key="item.id"
                            :value="item.slug"
                            >{{ item.name }}</SelectItem
                        ></SelectContent
                    ></Select
                >
                <Button class="h-10 bg-primary" @click="applyFilters()"
                    >Search</Button
                >
            </div>
            <div
                v-if="projects.data.length"
                class="grid gap-3 bg-secondary p-3 sm:gap-4 sm:p-4"
                :class="projectGridClass"
            >
                <ProjectCard
                    v-for="project in projects.data"
                    :key="project.id"
                    :project="project"
                />
            </div>
            <Empty v-else class="border-0 bg-background py-24"
                ><EmptyHeader
                    ><EmptyTitle>No launches match this search.</EmptyTitle
                    ><EmptyDescription
                        >Try another name or clear the category
                        filter.</EmptyDescription
                    ></EmptyHeader
                ><Button as-child variant="outline"
                    ><Link :href="discover()">Clear filters</Link></Button
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
                            applyFilters(Math.max(1, projects.current_page - 1))
                        " /><PaginationItem
                        v-for="item in totalPages"
                        :key="item"
                        :value="item"
                        :is-active="item === page"
                        @click="applyFilters(item)"
                        >{{ item }}</PaginationItem
                    ><PaginationNext
                        @click="
                            applyFilters(
                                Math.min(totalPages, projects.current_page + 1),
                            )
                        " /></PaginationContent
            ></Pagination>
        </section>
    </PublicShell>
</template>
