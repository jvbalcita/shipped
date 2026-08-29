<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Plus } from '@lucide/vue';
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
import {
    create as collectionCreate,
    show as collectionShow,
} from '@/routes/collections';

defineProps<{
    collections: {
        id: number;
        title: string;
        slug: string;
        description: string;
        cover_image_url: string | null;
        projects_count: number;
    }[];
}>();

const page = usePage();
</script>

<template>
    <PublicShell title="Collections">
        <section
            class="page-enter mx-auto w-full min-w-0 border-x border-b border-foreground"
        >
            <SectionHeader label="Public registry / Curated by Shipped">
                <h1
                    class="display-type mt-12 max-w-4xl text-[clamp(3.25rem,6.5vw,6.75rem)] sm:mt-0"
                >
                    Collections.
                </h1>
                <p
                    class="mt-6 max-w-xl text-sm leading-6 text-muted-foreground"
                >
                    Hand-picked sets of verified Laravel projects. Every member
                    is a real, discoverable launch — picked for what was
                    actually shipped, not for votes.
                </p>
                <Button
                    v-if="page.props.can?.curate"
                    as-child
                    class="mt-6"
                    variant="outline"
                    data-test="new-collection"
                    ><Link :href="collectionCreate()"
                        ><Plus class="size-4" />New collection</Link
                    ></Button
                >
            </SectionHeader>
            <div v-if="collections.length" class="grid gap-px bg-foreground">
                <Link
                    v-for="collection in collections"
                    :key="collection.id"
                    :href="collectionShow({ slug: collection.slug })"
                    class="group flex items-center gap-5 bg-background p-5 transition-colors hover:bg-primary hover:text-primary-foreground sm:p-8"
                    :data-test="`collection-link-${collection.slug}`"
                >
                    <img
                        v-if="collection.cover_image_url"
                        :src="collection.cover_image_url"
                        :alt="collection.title"
                        class="hidden h-16 w-28 shrink-0 border border-foreground object-cover sm:block"
                    />
                    <span class="min-w-0 flex-1">
                        <h2 class="font-display text-2xl tracking-tight">
                            {{ collection.title }}
                        </h2>
                        <p
                            class="mt-2 line-clamp-2 max-w-3xl text-sm leading-6 opacity-80"
                        >
                            {{ collection.description }}
                        </p>
                    </span>
                    <span class="flex shrink-0 items-center gap-3 self-stretch">
                        <span class="technical-label tabular-nums">{{
                            collection.projects_count
                        }}</span>
                        <ArrowUpRight
                            class="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                        />
                    </span>
                </Link>
            </div>
            <Empty v-else class="border-0 bg-background py-16"
                ><EmptyHeader
                    ><EmptyTitle>No collections are live yet.</EmptyTitle
                    ><EmptyDescription
                        >Curated sets of verified launches appear here as the
                        registry grows.</EmptyDescription
                    ></EmptyHeader
                ><Button as-child variant="outline"
                    ><Link :href="discover()">Browse all launches</Link></Button
                ></Empty
            >
        </section>
    </PublicShell>
</template>
