<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import ActivityItem from '@/components/shipped/ActivityItem.vue';
import FollowButton from '@/components/shipped/FollowButton.vue';
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
import { store as storeCreatorFollow } from '@/routes/users/follow';
import type { FeedActivity, SuggestedCreator } from '@/types/feed';

const props = defineProps<{
    activities: { items: FeedActivity[]; next_cursor: string | null };
    followedCreators: number;
    followedProjects: number;
    empty: boolean;
    suggestedCreators: SuggestedCreator[];
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
                    class="display-type mt-8 text-[clamp(2.25rem,4.5vw,4rem)] sm:mt-0"
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
                >
                <ul
                    v-if="suggestedCreators.length"
                    class="mx-auto mb-8 w-full max-w-md divide-y divide-foreground border border-foreground"
                    data-test="feed-suggested-creators"
                >
                    <li
                        v-for="creator in suggestedCreators"
                        :key="creator.username"
                        class="flex items-center justify-between gap-4 bg-background p-4"
                    >
                        <div class="min-w-0">
                            <p class="technical-label truncate">
                                {{ creator.name }}
                            </p>
                            <p
                                class="technical-label mt-1 text-muted-foreground"
                            >
                                @{{ creator.username
                                }}<template v-if="creator.followers_count > 0">
                                    · {{ creator.followers_count }}
                                    {{
                                        creator.followers_count === 1
                                            ? 'follower'
                                            : 'followers'
                                    }}</template
                                >
                            </p>
                        </div>
                        <FollowButton
                            :key="creator.username"
                            :count="creator.followers_count"
                            :following="false"
                            :action="storeCreatorFollow(creator)"
                            class="shrink-0"
                        />
                    </li>
                </ul>
                <Button as-child
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
