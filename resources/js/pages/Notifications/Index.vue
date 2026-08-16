<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    CornerUpLeft,
    Heart,
    MessageSquare,
    Star,
    UserPlus,
} from '@lucide/vue';
import { computed } from 'vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import SectionHeader from '@/components/shipped/SectionHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyTitle,
} from '@/components/ui/empty';
import { feed } from '@/routes';
import { show as creatorShow } from '@/routes/creators';
import { index as notificationsIndex, readAll } from '@/routes/notifications';
import { show as projectShow } from '@/routes/projects';

type NotificationRow = {
    id: number;
    type: 'follow' | 'cheer' | 'review' | 'comment' | 'reply';
    actor: { name: string; username: string } | null;
    project: {
        name: string;
        slug: string;
        creator_username: string | null;
    } | null;
    data: Record<string, unknown> | null;
    read: boolean;
    created_at: string | null;
};

const props = defineProps<{
    notifications: { items: NotificationRow[]; next_cursor: string | null };
}>();

const verbs = {
    follow: { icon: UserPlus, label: 'followed you' },
    cheer: { icon: Heart, label: 'cheered your project' },
    review: { icon: Star, label: 'reviewed your project' },
    comment: { icon: MessageSquare, label: 'commented on your project' },
    reply: { icon: CornerUpLeft, label: 'replied to your comment' },
} as const;

const hasUnread = computed(() =>
    props.notifications.items.some((notification) => !notification.read),
);

function targetHref(notification: NotificationRow): string | null {
    if (notification.type === 'follow') {
        return notification.actor !== null
            ? creatorShow(notification.actor)
            : null;
    }

    return notification.project !== null
        ? projectShow({
              creator: {
                  username: notification.project.creator_username ?? '',
              },
              project: notification.project,
          })
        : null;
}

function relativeTime(iso: string | null): string {
    if (iso === null) {
        return '';
    }

    const seconds = Math.max(1, (Date.now() - new Date(iso).getTime()) / 1000);

    if (seconds < 60) {
        return 'just now';
    }

    const minutes = Math.floor(seconds / 60);

    if (minutes < 60) {
        return `${minutes}m ago`;
    }

    const hours = Math.floor(minutes / 60);

    if (hours < 24) {
        return `${hours}h ago`;
    }

    const days = Math.floor(hours / 24);

    if (days < 30) {
        return `${days}d ago`;
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
    }).format(new Date(iso));
}

function markAllRead(): void {
    router.post(readAll().url, {}, { preserveScroll: true });
}

function loadMore(): void {
    router.get(
        notificationsIndex().url,
        { cursor: props.notifications.next_cursor },
        { preserveScroll: true, preserveState: true },
    );
}
</script>

<template>
    <PublicShell title="Notifications">
        <section
            class="page-enter mx-auto w-full max-w-[90rem] min-w-0 border-x border-b border-foreground"
        >
            <SectionHeader label="Notifications / Inbox">
                <div class="mt-12 flex flex-wrap items-end justify-between gap-4 sm:mt-0">
                    <h1
                        class="display-type text-[clamp(3rem,7vw,7rem)]"
                    >
                        What reached your desk.
                    </h1>
                    <Button
                        v-if="hasUnread"
                        variant="outline"
                        data-test="mark-all-read"
                        @click="markAllRead"
                        >Mark all read</Button
                    >
                </div>
                <p class="mt-6 max-w-xl text-sm leading-6 text-muted-foreground">
                    Follows, cheers, reviews, comments, and replies about you
                    and your launches.
                </p>
            </SectionHeader>
            <Empty
                v-if="!notifications.items.length"
                class="border-0 bg-background py-24"
                data-test="notifications-empty"
                ><EmptyHeader
                    ><EmptyTitle>No notifications yet.</EmptyTitle
                    ><EmptyDescription
                        >When someone follows you, cheers, reviews, or
                        comments on your work, it lands here.</EmptyDescription
                    ></EmptyHeader
                ><Button as-child variant="outline"
                    ><Link :href="feed()">Browse your feed</Link></Button
                ></Empty
            >
            <ul
                v-else
                class="divide-y divide-foreground border-t border-foreground"
                data-test="notification-items"
            >
                <li
                    v-for="notification in notifications.items"
                    :key="notification.id"
                    class="flex items-start gap-4 px-5 py-4 sm:px-8"
                    :class="notification.read ? undefined : 'bg-secondary'"
                    :data-test="`notification-${notification.type}`"
                >
                    <component
                        :is="verbs[notification.type].icon"
                        class="mt-0.5 size-4 shrink-0 text-primary"
                        aria-hidden="true"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm leading-6">
                            <Link
                                v-if="notification.actor"
                                :href="creatorShow(notification.actor)"
                                class="font-medium text-foreground underline-offset-4 hover:underline"
                                >{{ notification.actor.name }}</Link
                            >
                            <span v-else class="text-muted-foreground"
                                >A former member</span
                            >
                            {{ ' ' }}{{ verbs[notification.type].label }}
                            <Link
                                v-if="
                                    notification.type !== 'follow' &&
                                    notification.project
                                "
                                :href="targetHref(notification)!"
                                class="font-medium text-foreground underline decoration-foreground/40 underline-offset-4 transition-colors hover:decoration-foreground"
                                >{{ notification.project.name }}</Link
                            >
                        </p>
                        <p
                            class="technical-label mt-1 text-muted-foreground"
                        >
                            <span
                                v-if="!notification.read"
                                class="mr-2 inline-block size-2 border border-foreground bg-primary align-middle"
                                aria-hidden="true"
                            ></span>
                            {{ relativeTime(notification.created_at) }}
                        </p>
                    </div>
                </li>
            </ul>
            <div
                v-if="notifications.next_cursor"
                class="border-t border-foreground py-6 text-center"
            >
                <Button
                    variant="outline"
                    data-test="notifications-load-more"
                    @click="loadMore"
                    >Load more</Button
                >
            </div>
        </section>
    </PublicShell>
</template>
