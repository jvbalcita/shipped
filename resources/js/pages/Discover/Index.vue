<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { X } from '@lucide/vue';
import { Layers } from '@lucide/vue';
import { computed, ref } from 'vue';
import ProjectCard from '@/components/shipped/ProjectCard.vue';
import ProjectCardSkeleton from '@/components/shipped/ProjectCardSkeleton.vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import SectionHeader from '@/components/shipped/SectionHeader.vue';
import TechnologyPicker from '@/components/shipped/TechnologyPicker.vue';
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
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { discover } from '@/routes';
import type { TechnologyGroupOption } from '@/types/technology';

const props = defineProps<{
    projects: {
        data: any[];
        current_page: number;
        last_page: number;
        total: number;
        from?: number;
        to?: number;
    };
    categories: any[];
    pricingOptions: { value: string; label: string }[];
    activeCategory: { id: number; name: string; slug: string } | null;
    technologyOptions: TechnologyGroupOption[];
    activeTechnologies: { id: number; name: string; slug: string }[];
    filters: {
        q?: string;
        category?: string;
        pricing?: string;
        technologies?: string[];
        sort?: string;
    };
}>();
const search = ref(props.filters.q ?? '');
const category = ref(props.filters.category ?? 'all');
const pricing = ref(props.filters.pricing ?? 'all');
const sort = ref(props.filters.sort ?? 'latest');
const selectedTechnologies = ref<string[]>(props.filters.technologies ?? []);
const stackPopoverOpen = ref(false);
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

function applyFilters(page = 1) {
    loading.value = true;
    // The stack popover stays open across selections so a creator can
    // pick several technologies in one visit; it closes on outside
    // click or escape like any popover.
    router.get(
        discover().url,
        {
            q: search.value || undefined,
            category: category.value === 'all' ? undefined : category.value,
            pricing: pricing.value === 'all' ? undefined : pricing.value,
            technologies: selectedTechnologies.value.length
                ? selectedTechnologies.value
                : undefined,
            sort: sort.value === 'latest' ? undefined : sort.value,
            page,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onFinish: () => {
                loading.value = false;
            },
        },
    );
}

function onTechnologiesChange(value: string[]) {
    selectedTechnologies.value = value;
    applyFilters();
}

function clearTechnology(slug: string) {
    selectedTechnologies.value = selectedTechnologies.value.filter(
        (value) => value !== slug,
    );
    applyFilters();
}

function clearSearch() {
    search.value = '';
    applyFilters();
}

function clearCategory() {
    category.value = 'all';
    applyFilters();
}

function clearPricing() {
    pricing.value = 'all';
    applyFilters();
}
</script>

<template>
    <PublicShell title="Discover launches">
        <section
            class="page-enter mx-auto w-full max-w-[90rem] min-w-0 border-x border-b border-foreground"
        >
            <SectionHeader label="Public registry / Browse">
                <h1
                    class="display-type mt-8 max-w-4xl text-[clamp(2.5rem,5vw,4.5rem)] sm:mt-0"
                >
                    Explore launches.
                </h1>
                <p
                    class="mt-6 max-w-xl text-sm leading-6 text-muted-foreground"
                >
                    Public records from Laravel builders, searchable by name and
                    category.
                </p>
            </SectionHeader>
            <div
                class="grid border-b border-foreground bg-transparent md:grid-cols-[1fr_12rem_12rem_12rem_auto]"
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
                <Select v-model="pricing" @update:model-value="applyFilters()"
                    ><SelectTrigger
                        class="h-10 w-full rounded-none border-foreground bg-background text-foreground"
                        data-test="discover-pricing-filter"
                        ><SelectValue
                            placeholder="All pricing" /></SelectTrigger
                    ><SelectContent
                        ><SelectItem value="all">All pricing</SelectItem
                        ><SelectItem
                            v-for="option in pricingOptions"
                            :key="option.value"
                            :value="option.value"
                            >{{ option.label }}</SelectItem
                        ></SelectContent
                    ></Select
                >
                <Popover v-model:open="stackPopoverOpen">
                    <PopoverTrigger
                        class="flex h-10 w-full items-center justify-between gap-2 rounded-none border border-foreground bg-background px-3 text-left text-foreground"
                        data-test="discover-stack-filter"
                    >
                        <span class="flex items-center gap-2 text-sm">
                            <Layers class="size-4" aria-hidden="true" />
                            Stack
                        </span>
                        <span
                            v-if="selectedTechnologies.length"
                            class="technical-label text-primary tabular-nums"
                        >
                            {{ selectedTechnologies.length }}
                        </span>
                    </PopoverTrigger>
                    <PopoverContent
                        align="end"
                        class="w-80 rounded-none border-foreground"
                    >
                        <div class="grid max-h-96 gap-4 overflow-y-auto p-1">
                            <TechnologyPicker
                                :groups="technologyOptions"
                                :model-value="selectedTechnologies"
                                @update:model-value="onTechnologiesChange"
                            />
                            <Button
                                v-if="selectedTechnologies.length"
                                variant="outline"
                                size="sm"
                                class="w-full"
                                data-test="clear-stack-filters"
                                @click="onTechnologiesChange([])"
                            >
                                Clear stack filters
                            </Button>
                        </div>
                    </PopoverContent>
                </Popover>
                <Button class="h-10 bg-primary" @click="applyFilters()"
                    >Search</Button
                >
            </div>
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b border-foreground bg-background px-5 py-3 sm:px-8"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <span class="technical-label tabular-nums">{{
                        resultsLabel
                    }}</span>
                    <span
                        v-if="search"
                        class="inline-flex items-center gap-1.5 border border-foreground bg-background px-2 py-1 font-mono text-[10px] tracking-[.08em] uppercase"
                    >
                        <span class="max-w-[12rem] truncate"
                            >"{{ search }}"</span
                        >
                        <button
                            type="button"
                            aria-label="Clear search"
                            class="text-primary"
                            @click="clearSearch"
                        >
                            <X class="size-3" aria-hidden="true" />
                        </button>
                    </span>
                    <span
                        v-if="activeCategory"
                        class="inline-flex items-center gap-1.5 border border-foreground bg-background px-2 py-1 font-mono text-[10px] tracking-[.08em] uppercase"
                    >
                        {{ activeCategory.name }}
                        <button
                            type="button"
                            aria-label="Clear category filter"
                            class="text-primary"
                            @click="clearCategory"
                        >
                            <X class="size-3" aria-hidden="true" />
                        </button>
                    </span>
                    <span
                        v-if="pricing !== 'all'"
                        class="inline-flex items-center gap-1.5 border border-foreground bg-background px-2 py-1 font-mono text-[10px] tracking-[.08em] uppercase"
                    >
                        {{
                            pricingOptions.find(
                                (option) => option.value === pricing,
                            )?.label ?? pricing
                        }}
                        <button
                            type="button"
                            aria-label="Clear pricing filter"
                            class="text-primary"
                            @click="clearPricing"
                        >
                            <X class="size-3" aria-hidden="true" />
                        </button>
                    </span>
                    <span
                        v-for="technology in activeTechnologies"
                        :key="technology.slug"
                        class="inline-flex items-center gap-1.5 border border-foreground bg-background px-2 py-1 font-mono text-[10px] tracking-[.08em] uppercase"
                    >
                        {{ technology.name }}
                        <button
                            type="button"
                            :aria-label="`Clear ${technology.name} stack filter`"
                            class="text-primary"
                            @click="clearTechnology(technology.slug)"
                        >
                            <X class="size-3" aria-hidden="true" />
                        </button>
                    </span>
                </div>
                <Select v-model="sort" @update:model-value="applyFilters()"
                    ><SelectTrigger
                        class="h-8 w-[12rem] rounded-none border-foreground bg-background font-mono text-[10px] tracking-[.08em] uppercase"
                        ><span class="text-muted-foreground">Sort /</span>
                        <SelectValue /> </SelectTrigger
                    ><SelectContent>
                        <SelectItem value="latest">Latest</SelectItem>
                        <SelectItem value="cheered">Most cheered</SelectItem>
                        <SelectItem value="launch_date">Launch date</SelectItem>
                    </SelectContent></Select
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
                        >Try another name or clear the category and stack
                        filters.</EmptyDescription
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
