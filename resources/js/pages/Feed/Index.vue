<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import ActivityItem from '@/components/shipped/ActivityItem.vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import SectionHeader from '@/components/shipped/SectionHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyTitle,
} from '@/components/ui/empty';
import { discover, feed } from '@/routes';
import type { FeedActivity } from '@/types/feed';

const props = defineProps<{
    activities: { items: FeedActivity[]; next_cursor: string | null };
    followedCreators: number;
    followedProjects: number;
    empty: boolean;
}>();

function loadMore(): void {
    router.get(
        feed().url,
        { cursor: props.activities.next_cursor },
        { preserveScroll: true, preserveState: true },
    );
}
</script>

<template>
    <PublicShell title="Following">
        <section
            class="page-enter mx-auto w-full max-w-[90rem] min-w-0 border-x border-b border-foreground"
        >
            <SectionHeader
                :label="`Activity feed / ${followedCreators} creators · ${followedProjects} projects`"
            >
                <h1
                    class="display-type mt-12 text-[clamp(3rem,7vw,7rem)] sm:mt-0"
                >
                    What your fleet is shipping.
                </h1>
                <p
                    class="mt-6 max-w-xl text-sm leading-6 text-muted-foreground"
                >
                    Launches, releases, reviews, cheers, and verifications from
                    the creators and projects you follow.
                </p>
            </SectionHeader>
            <Empty
                v-if="empty"
                class="border-0 bg-background py-24"
                data-test="feed-empty"
                ><EmptyHeader
                    ><EmptyTitle
                        >Nothing on the wire yet.</EmptyTitle
                    ><EmptyDescription
                        >Follow creators to see their ships. New launches,
                        releases, and cheers land here.</EmptyDescription
                    ></EmptyHeader
                ><Button as-child
                    ><Link :href="discover()">Discover creators</Link></Button
                ></Empty
            >
            <ol
                v-else
                class="divide-y divide-foreground border-t border-foreground"
                data-test="feed-items"
            >
                <ActivityItem
                    v-for="activity in activities.items"
                    :key="activity.id"
                    :activity="activity"
                />
            </ol>
            <div
                v-if="activities.next_cursor"
                class="border-t border-foreground py-6 text-center"
            >
                <Button
                    variant="outline"
                    data-test="feed-load-more"
                    @click="loadMore"
                    >Load more</Button
                >
            </div>
        </section>
    </PublicShell>
</template>
