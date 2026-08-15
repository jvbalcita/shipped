<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Heart, Package, Rocket, ShieldCheck, Star } from '@lucide/vue';
import { show as creatorShow } from '@/routes/creators';
import { show as projectShow } from '@/routes/projects';
import { show as releaseShow } from '@/routes/releases';
import type { FeedActivity } from '@/types/feed';

defineProps<{ activity: FeedActivity }>();

const verbs = {
    launched: { icon: Rocket, label: 'Launched' },
    released: { icon: Package, label: 'Released' },
    reviewed: { icon: Star, label: 'Reviewed' },
    cheered: { icon: Heart, label: 'Cheered' },
    verified: { icon: ShieldCheck, label: 'Verified' },
} as const;

function formatTimestamp(iso: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
    }).format(new Date(iso));
}
</script>

<template>
    <li class="flex items-start gap-4 px-5 py-4 sm:px-8">
        <component
            :is="verbs[activity.verb].icon"
            class="mt-0.5 size-4 shrink-0 text-primary"
            aria-hidden="true"
        />
        <p class="min-w-0 text-sm leading-6">
            <Link
                v-if="activity.actor"
                :href="creatorShow(activity.actor)"
                class="font-medium text-foreground underline-offset-4 hover:underline"
                >{{ activity.actor.name }}</Link
            >
            <span v-else class="text-muted-foreground">A former member</span>
            <template v-if="activity.verb === 'launched'"
                >launched</template
            >
            <template v-else-if="activity.verb === 'released'"
                >released
                <span class="font-medium">{{
                    (activity.meta?.release_title as string) ?? 'a release'
                }}</span>
                of</template
            >
            <template v-else-if="activity.verb === 'reviewed'"
                >reviewed
                <span class="font-medium"
                    >({{ activity.meta?.rating ?? '?' }}/5)</span
                ></template
            >
            <template v-else-if="activity.verb === 'cheered'">cheered</template>
            <template v-else>passed verification for</template>
            <Link
                v-if="activity.project"
                :href="
                    activity.verb === 'released' &&
                    activity.meta?.release_id
                        ? releaseShow({
                              creator: {
                                  username:
                                      activity.project.creator_username ?? '',
                              },
                              project: activity.project,
                              release: {
                                  id: activity.meta.release_id as number,
                              },
                          })
                        : projectShow({
                              creator: {
                                  username:
                                      activity.project.creator_username ?? '',
                              },
                              project: activity.project,
                          })
                "
                class="font-medium text-primary underline underline-offset-4"
                >{{ activity.project.name }}</Link
            >
            <span v-else class="text-muted-foreground"
                >a ship that has sailed</span
            >
            <span
                class="technical-label mt-1 block text-muted-foreground"
                :data-test="`activity-${activity.verb}`"
                >{{ verbs[activity.verb].label }} /
                {{ formatTimestamp(activity.occurred_at) }}</span
            >
        </p>
    </li>
</template>
