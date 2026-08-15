<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Heart, ShieldCheck } from '@lucide/vue';
import { ref, watch } from 'vue';
import { AspectRatio } from '@/components/ui/aspect-ratio';
import { Badge } from '@/components/ui/badge';
import { defaultCoverUrl } from '@/lib/cover';
import { show } from '@/routes/projects';
import { store as storeCheer } from '@/routes/projects/cheers';

const props = defineProps<{ project: any }>();

const page = usePage();
const cheered = ref(Boolean(props.project.cheered_by_viewer));
const count = ref(Number(props.project.cheers_count ?? 0));

// Resync local state when the Discover page re-renders with fresh counts.
watch(() => props.project.cheers_count, (value) => {
    count.value = Number(value ?? 0);
});
watch(() => props.project.cheered_by_viewer, (value) => {
    cheered.value = Boolean(value);
});

function toggleCheer(): void {
    const authed = page.props.auth.user !== null;

    const previousCheered = cheered.value;
    const previousCount = count.value;

    // Authenticated creators toggle optimistically; guests get routed to login
    // by the auth-protected cheer route (intended returns them to Discover).
    if (authed) {
        cheered.value = !previousCheered;
        count.value = previousCheered
            ? Math.max(0, previousCount - 1)
            : previousCount + 1;
    }

    router.post(
        storeCheer(props.project).url,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                // Automatic rollback on failure.
                cheered.value = previousCheered;
                count.value = previousCount;
            },
        },
    );
}
</script>

<template>
    <Link
        :href="show({ creator: project.creator, project })"
        class="group grid border border-foreground bg-background transition-[transform,background-color] duration-200 ease-out focus-visible:outline-3 focus-visible:outline-offset-3 focus-visible:outline-ring motion-safe:will-change-transform motion-safe:hover:-translate-y-1"
    >
        <AspectRatio
            :ratio="16 / 10"
            class="relative border-b border-foreground bg-secondary"
        >
            <span
                v-if="project.filed_serial"
                class="technical-label absolute top-3 left-3 z-10 border border-foreground bg-background px-2 py-1 tabular-nums"
                >{{ project.filed_serial }}</span
            >
            <img
                :src="project.cover_image_url ?? defaultCoverUrl(project)"
                :alt="`${project.name} cover image`"
                loading="lazy"
                width="960"
                height="600"
                class="size-full object-cover transition-[filter] duration-300 ease-out"
                :class="{
                    grayscale: !!project.cover_image_url,
                    'group-hover:grayscale-0': !!project.cover_image_url,
                }"
            />
        </AspectRatio>
        <div class="grid gap-px bg-foreground">
            <div
                class="flex items-center justify-between bg-background px-4 py-3"
            >
                <Badge variant="outline">{{ project.category.name }}</Badge>
                <button
                    type="button"
                    class="technical-label inline-flex items-center gap-1 focus-visible:outline-2"
                    :class="cheered ? 'text-primary' : 'text-muted-foreground'"
                    data-test="project-cheer"
                    :aria-pressed="cheered"
                    :aria-label="`Cheer ${project.name}`"
                    @click.prevent.stop="toggleCheer"
                >
                    <Heart
                        class="size-3"
                        :class="{ 'fill-current': cheered }"
                        aria-hidden="true"
                    />{{ count }}
                </button>
            </div>
            <div class="bg-background p-4">
                <div class="flex items-start gap-3">
                    <img
                        v-if="project.logo_url"
                        :src="project.logo_url"
                        :alt="`${project.name} logo`"
                        class="size-10 shrink-0 border border-foreground object-cover"
                    />
                    <div class="min-w-0">
                        <h2 class="display-type text-2xl">{{ project.name }}</h2>
                        <p
                            v-if="project.pricing_label || project.pricing"
                            class="technical-label mt-2 text-primary"
                        >
                            {{
                                project.pricing_label ||
                                String(project.pricing).replaceAll('_', ' ')
                            }}
                        </p>
                        <p
                            v-if="project.launch_date"
                            class="technical-label mt-1 text-muted-foreground"
                        >
                            Launched
                            {{
                                new Date(
                                    project.launch_date,
                                ).toLocaleDateString(undefined, {
                                    year: 'numeric',
                                    month: 'short',
                                    day: 'numeric',
                                })
                            }}
                        </p>
                        <p
                            v-if="project.rating_average != null"
                            class="technical-label mt-1 text-primary"
                            data-test="project-rating"
                        >
                            ★ {{ project.rating_average }} / 5
                        </p>
                    </div>
                </div>
                <p
                    class="mt-4 line-clamp-2 text-sm leading-6 text-muted-foreground"
                >
                    {{ project.tagline }}
                </p>
                <ul
                    v-if="project.tags?.length"
                    class="mt-3 flex flex-wrap gap-2"
                >
                    <li
                        v-for="tag in project.tags"
                        :key="tag.id ?? tag.slug ?? tag.name"
                        class="technical-label border border-foreground px-2 py-0.5"
                    >
                        {{ tag.name }}
                    </li>
                </ul>
            </div>
            <div
                class="technical-label flex items-center justify-between bg-background px-4 py-3"
            >
                <span>@{{ project.creator.username }}</span>
                <span
                    v-if="project.verification_status === 'verified'"
                    class="inline-flex items-center gap-1 text-primary"
                    ><ShieldCheck class="size-3" aria-hidden="true" />
                    Verified</span
                >
                <ArrowUpRight v-else class="size-4" aria-hidden="true" />
            </div>
        </div>
    </Link>
</template>
