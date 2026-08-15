<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Briefcase, Code, Globe } from '@lucide/vue';
import FollowButton from '@/components/shipped/FollowButton.vue';
import ProjectCard from '@/components/shipped/ProjectCard.vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import SectionHeader from '@/components/shipped/SectionHeader.vue';
import { destroy as destroyFollow, store as storeFollow } from '@/routes/users/follow';

defineProps<{ creator: any; projects: any[] }>();

const page = usePage();

const linkIcon = (type: string) => {
    if (type === 'github') {
        return Code;
    }

    if (type === 'linkedin') {
        return Briefcase;
    }

    return Globe;
};
</script>

<template>
    <PublicShell :title="`@${creator.username}`"
        ><section
            class="page-enter mx-auto w-full max-w-[90rem] min-w-0 border-x border-b border-foreground"
        >
            <SectionHeader :label="`Creator file / @${creator.username}`">
                <div class="mt-12 flex flex-wrap items-center justify-between gap-4 sm:mt-0">
                    <div
                        v-if="creator.avatar_url"
                        class="mb-6 size-20 overflow-hidden border border-foreground bg-secondary"
                    >
                        <img
                            :src="creator.avatar_url"
                            :alt="`${creator.name} avatar`"
                            class="size-full object-cover"
                            data-test="creator-avatar"
                        />
                    </div>
                    <FollowButton
                        v-if="page.props.auth.user?.username !== creator.username"
                        :key="creator.username"
                        :count="creator.followers_count"
                        :following="creator.followed_by_viewer"
                        :action="
                            creator.followed_by_viewer
                                ? { ...destroyFollow(creator), method: 'delete' as const }
                                : { ...storeFollow(creator), method: 'post' as const }
                        "
                    />
                </div>
                <div
                    class="technical-label mt-6 text-muted-foreground"
                    data-test="creator-followers-count"
                >
                    {{ creator.followers_count }}
                    {{ creator.followers_count === 1 ? 'follower' : 'followers' }}
                </div>
                <h1
                    class="display-type mt-12 text-[clamp(3rem,7vw,7rem)] sm:mt-0"
                >
                    {{ creator.name }}
                </h1>
                <p
                    v-if="creator.title"
                    class="technical-label mt-4 text-primary"
                >
                    {{ creator.title }}
                </p>
                <p
                    v-if="creator.location"
                    class="mt-2 text-sm text-muted-foreground"
                >
                    {{ creator.location }}
                </p>
                <p
                    v-if="creator.bio"
                    class="mt-6 max-w-2xl leading-7 text-muted-foreground"
                >
                    {{ creator.bio }}
                </p>
                <ul
                    v-if="creator.links?.length"
                    class="mt-6 flex flex-wrap gap-3"
                >
                    <li
                        v-for="link in creator.links"
                        :key="link.type + link.url"
                    >
                        <a
                            :href="link.url"
                            class="technical-label inline-flex items-center gap-2 text-primary underline underline-offset-4"
                            target="_blank"
                            rel="noreferrer"
                        >
                            <component
                                :is="linkIcon(link.type)"
                                class="size-3.5"
                                aria-hidden="true"
                            />
                            {{ link.type }}
                        </a>
                    </li>
                </ul>
            </SectionHeader>
            <div
                class="grid gap-px bg-foreground md:grid-cols-2 xl:grid-cols-3"
            >
                <ProjectCard
                    v-for="project in projects"
                    :key="project.id"
                    :project="project"
                />
            </div>
            <p
                v-if="!projects.length"
                class="p-12 text-sm text-muted-foreground"
            >
                No public launches in this file.
            </p>
        </section></PublicShell
    >
</template>
