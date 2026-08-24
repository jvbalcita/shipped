<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Anchor, Heart } from '@lucide/vue';
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/composables/useInitials';
import { store as cheer } from '@/routes/projects/cheers';

export type CheerCard = {
    name: string | null;
    username: string | null;
    avatar_url: string | null;
    cheered_at: string | null;
};

const props = defineProps<{
    cheers: CheerCard[] | null;
    hasCheered: boolean;
    canCheer: boolean;
    project: {
        slug: string;
        name: string;
        cheers_count: number;
    };
}>();

const { getInitials } = useInitials();

const wall = computed(() => props.cheers ?? []);

function cheerProject(): void {
    router.post(
        cheer(props.project).url,
        {},
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
}

function cheeredOn(iso: string | null): string {
    if (iso === null) {
        return '';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
    }).format(new Date(iso));
}
</script>

<template>
    <section
        v-if="cheers !== null"
        class="border-t border-foreground"
        data-test="cheer-wall"
        aria-label="Supporters"
    >
        <div class="grid p-5 sm:grid-cols-[.45fr_1.55fr] sm:p-8">
            <p
                class="technical-label text-primary"
                data-test="cheer-wall-count"
            >
                Supporters / {{ project.cheers_count }}
            </p>
            <div>
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h2 class="display-type text-2xl">The crew who cheered.</h2>
                    <Button
                        :variant="hasCheered ? 'outline' : 'default'"
                        :class="hasCheered ? 'text-primary' : undefined"
                        data-test="wall-cheer-button"
                        :aria-pressed="hasCheered"
                        @click="cheerProject"
                    >
                        <Heart
                            class="size-4"
                            :class="{ 'fill-current': hasCheered }"
                            aria-hidden="true"
                        />
                        {{
                            hasCheered
                                ? 'You cheered'
                                : wall.length
                                  ? 'Join the wall'
                                  : 'Cheer this launch'
                        }}
                    </Button>
                </div>

                <p
                    v-if="!wall.length"
                    class="mt-6 text-sm leading-6 text-muted-foreground"
                    data-test="cheer-wall-empty"
                >
                    No cheers yet — be the First Mate ⚓
                </p>

                <ul
                    v-else
                    class="mt-6 grid gap-px border border-foreground sm:grid-cols-2 lg:grid-cols-3"
                    data-test="cheer-wall-grid"
                >
                    <li
                        v-for="(supporter, index) in wall"
                        :key="supporter.username ?? index"
                        class="bg-background p-4"
                        :data-test="index === 0 ? 'first-mate' : 'cheer-card'"
                    >
                        <div class="flex items-center gap-3">
                            <Avatar
                                class="size-9 rounded-none border border-foreground"
                            >
                                <AvatarImage
                                    v-if="supporter.avatar_url"
                                    :src="supporter.avatar_url"
                                    :alt="`${supporter.name} avatar`"
                                />
                                <AvatarFallback
                                    class="rounded-none bg-primary font-mono text-xs font-semibold text-primary-foreground"
                                >
                                    {{ getInitials(supporter.name ?? '?') }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">
                                    {{ supporter.name ?? 'A former member' }}
                                </p>
                                <p
                                    class="technical-label truncate text-muted-foreground"
                                >
                                    @{{ supporter.username }}
                                </p>
                            </div>
                        </div>
                        <p class="technical-label mt-3 text-muted-foreground">
                            <span
                                v-if="index === 0"
                                class="mr-2 inline-flex items-center gap-1 border border-foreground bg-primary px-1.5 py-0.5 text-primary-foreground"
                                data-test="first-mate-marker"
                            >
                                <Anchor class="size-3" aria-hidden="true" />
                                First Mate
                            </span>
                            Cheered on {{ cheeredOn(supporter.cheered_at) }}
                        </p>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>
