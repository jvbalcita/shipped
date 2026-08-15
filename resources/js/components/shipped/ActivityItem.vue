<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Heart, Package, Rocket, ShieldCheck, Star } from '@lucide/vue';
import { computed } from 'vue';
import { show as creatorShow } from '@/routes/creators';
import { show as projectShow } from '@/routes/projects';
import { show as releaseShow } from '@/routes/releases';
import type { FeedActivity } from '@/types/feed';

const props = defineProps<{ activity: FeedActivity }>();

const verbs = {
    launched: { icon: Rocket, label: 'Launched' },
    released: { icon: Package, label: 'Released' },
    reviewed: { icon: Star, label: 'Reviewed' },
    cheered: { icon: Heart, label: 'Cheered' },
    verified: { icon: ShieldCheck, label: 'Verified' },
} as const;

// Built as one string so Vue's whitespace condensing can never collapse
// the gaps between the actor link, verb phrase, and target link.
const verbPhrase = computed(() => {
    switch (props.activity.verb) {
        case 'launched':
            return 'launched';
        case 'released':
            return `released ${(props.activity.meta?.release_title as string) ?? 'a release'} of`;
        case 'reviewed':
            return `reviewed (${(props.activity.meta?.rating as number) ?? '?'}/5)`;
        case 'cheered':
            return 'cheered';
        case 'verified':
            return 'passed verification';
        default:
            return '';
    }
});

const projectHref = computed(() => {
    const project = props.activity.project;

    if (project === null) {
        return null;
    }

    if (props.activity.verb === 'released' && props.activity.meta?.release_id) {
        return releaseShow({
            creator: { username: project.creator_username ?? '' },
            project,
            release: { id: props.activity.meta.release_id as number },
        });
    }

    return projectShow({
        creator: { username: project.creator_username ?? '' },
        project,
    });
});

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
            <template v-if="activity.verb === 'verified'">
                <Link
                    v-if="activity.project"
                    :href="projectHref!"
                    class="font-medium text-foreground underline decoration-foreground/40 underline-offset-4 transition-colors hover:decoration-foreground"
                    >{{ activity.project.name }}</Link
                >
                <span v-else class="text-muted-foreground"
                    >a ship that has sailed</span
                >
                {{ ' ' }}{{ verbPhrase }}
            </template>
            <template v-else>
                <Link
                    v-if="activity.actor"
                    :href="creatorShow(activity.actor)"
                    class="font-medium text-foreground underline-offset-4 hover:underline"
                    >{{ activity.actor.name }}</Link
                >
                <span v-else class="text-muted-foreground">A former member</span>
                {{ ' ' }}{{ verbPhrase }} {{ ' ' }}
                <Link
                    v-if="activity.project"
                    :href="projectHref!"
                    class="font-medium text-foreground underline decoration-foreground/40 underline-offset-4 transition-colors hover:decoration-foreground"
                    >{{ activity.project.name }}</Link
                >
                <span v-else class="text-muted-foreground"
                    >a ship that has sailed</span
                >
            </template>
            <span
                class="technical-label mt-1 block text-muted-foreground"
                :data-test="`activity-${activity.verb}`"
                >{{ verbs[activity.verb].label }} /
                {{ formatTimestamp(activity.occurred_at) }}</span
            >
        </p>
    </li>
</template>
