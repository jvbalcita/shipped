<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Heart, ShieldCheck } from '@lucide/vue';
import { AspectRatio } from '@/components/ui/aspect-ratio';
import { Badge } from '@/components/ui/badge';
import { show } from '@/routes/projects';

defineProps<{ project: any }>();
</script>

<template>
    <Link
        :href="show({ creator: project.creator, project })"
        class="group grid border border-foreground bg-background transition-[transform,background-color] duration-200 ease-out focus-visible:outline-3 focus-visible:outline-offset-3 focus-visible:outline-ring motion-safe:will-change-transform motion-safe:hover:-translate-y-1"
    >
        <AspectRatio
            :ratio="16 / 10"
            class="border-b border-foreground bg-secondary"
        >
            <img
                v-if="project.cover_image_url"
                :src="project.cover_image_url"
                :alt="`${project.name} cover image`"
                loading="lazy"
                width="960"
                height="600"
                class="size-full object-cover grayscale transition-[filter,transform] duration-300 ease-out group-hover:scale-[1.02] group-hover:grayscale-0"
            />
            <div
                v-else
                class="grid size-full place-items-center p-5 text-center"
            >
                <span
                    class="display-type max-w-[12ch] text-3xl leading-[.84]"
                    >{{ project.name }}</span
                >
            </div>
        </AspectRatio>
        <div class="grid gap-px bg-foreground">
            <div
                class="flex items-center justify-between bg-background px-4 py-3"
            >
                <Badge
                    variant="outline"
                    class="rounded-none border-foreground font-mono text-[10px] tracking-[.08em] uppercase"
                    >{{ project.category.name }}</Badge
                >
                <span class="inline-flex items-center gap-1 text-xs"
                    ><Heart class="size-3" aria-hidden="true" />{{
                        project.cheers_count
                    }}</span
                >
            </div>
            <div class="bg-background p-4">
                <h2 class="display-type text-2xl">{{ project.name }}</h2>
                <p
                    class="mt-4 line-clamp-2 text-sm leading-6 text-muted-foreground"
                >
                    {{ project.tagline }}
                </p>
            </div>
            <div
                class="technical-label flex items-center justify-between bg-background px-4 py-3"
            >
                <span>@{{ project.creator.handle }}</span>
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
