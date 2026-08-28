<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Copy, Download, ExternalLink, Lock } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import BadgeSnippet from '@/components/shipped/BadgeSnippet.vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import SectionHeader from '@/components/shipped/SectionHeader.vue';
import { Button } from '@/components/ui/button';
import { recordProductEvent } from '@/lib/productEvents';
import { edit as projectEdit } from '@/routes/projects';

interface LaunchKitProject {
    id: number;
    name: string;
    slug: string;
    tagline: string;
    creator: {
        name: string;
        username: string;
    };
}

interface LaunchKitAssets {
    is_discoverable: boolean;
    badge_markdown: string | null;
    share_text: string;
    canonical_url: string;
    card_url: string | null;
    cover_url: string;
    manifest_url: string | null;
}

const props = defineProps<{
    project: LaunchKitProject;
    kit: LaunchKitAssets;
}>();

const shareInput = ref<HTMLInputElement | null>(null);

const shareIntents = [
    { network: 'x' as const, label: 'Share on X' },
    { network: 'linkedin' as const, label: 'Share on LinkedIn' },
    { network: 'reddit' as const, label: 'Share on Reddit' },
];

function shareIntentUrl(network: 'x' | 'linkedin' | 'reddit'): string {
    const url = encodeURIComponent(props.kit.canonical_url);
    const text = encodeURIComponent(props.kit.share_text);

    switch (network) {
        case 'x':
            return `https://twitter.com/intent/tweet?text=${text}&url=${url}`;
        case 'linkedin':
            return `https://www.linkedin.com/sharing/share-offsite/?url=${url}`;
        case 'reddit':
            return `https://www.reddit.com/submit?url=${url}&title=${text}`;
    }
}

function clickShareIntent(network: 'x' | 'linkedin' | 'reddit'): void {
    recordProductEvent('share_intent_clicked', {
        projectId: props.project.id,
        network,
    });
}

async function copyValue(
    value: string,
    event: 'share_text_copied' | 'card_link_copied' | 'manifest_link_copied',
): Promise<void> {
    try {
        await navigator.clipboard.writeText(value);
        recordProductEvent(event, { projectId: props.project.id });
        toast.success('Link copied.');
    } catch {
        shareInput.value?.focus();
        toast('Copy is unavailable — select the text and copy manually.');
    }
}
</script>

<template>
    <PublicShell :title="`Launch Kit — ${project.name}`">
        <section
            class="page-enter mx-auto flex w-full max-w-[90rem] min-w-0 flex-1 flex-col border-x border-b border-foreground"
        >
            <SectionHeader label="Creator studio / Launch kit">
                <h1 class="display-type text-[clamp(3rem,7vw,7rem)]">
                    Everything you share.
                </h1>
                <p class="mt-6 max-w-xl text-muted-foreground">
                    One place for the shareable assets of
                    {{ project.name }}: the README badge, the launch card, the
                    Ship Manifest, and a ready-to-paste share line. Every asset
                    links back to your canonical launch page.
                </p>
            </SectionHeader>

            <section class="border-t border-foreground">
                <div class="grid p-5 sm:grid-cols-[.45fr_1.55fr] sm:p-8">
                    <p class="technical-label text-primary">Share text</p>
                    <div class="mt-8 flex flex-col gap-3 sm:mt-0">
                        <p class="text-sm leading-6 text-muted-foreground">
                            A ready-to-paste line for social posts, changelogs,
                            or messages. It carries your launch page URL, so
                            every paste is measurable.
                        </p>
                        <div class="flex gap-2">
                            <input
                                ref="shareInput"
                                :value="kit.share_text"
                                type="text"
                                readonly
                                class="h-9 min-w-0 flex-1 rounded-none border border-foreground bg-background px-3 font-mono text-xs text-foreground focus-visible:outline-2 focus-visible:outline-ring"
                                data-test="share-text"
                                @click="shareInput?.select()"
                            />
                            <Button
                                variant="outline"
                                class="shrink-0"
                                data-test="copy-share-text"
                                @click="
                                    copyValue(
                                        kit.share_text,
                                        'share_text_copied',
                                    )
                                "
                            >
                                <Copy class="size-4" aria-hidden="true" />
                                Copy share text
                            </Button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-t border-foreground">
                <div class="grid p-5 sm:grid-cols-[.45fr_1.55fr] sm:p-8">
                    <p class="technical-label text-primary">Share it</p>
                    <div class="mt-8 flex flex-col gap-3 sm:mt-0">
                        <p class="text-sm leading-6 text-muted-foreground">
                            Post your launch where Laravel builders already
                            gather. Each button opens the network with your
                            launch page prefilled.
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-for="intent in shareIntents"
                                :key="intent.network"
                                as-child
                                variant="outline"
                                :data-test="`share-${intent.network}`"
                            >
                                <a
                                    :href="shareIntentUrl(intent.network)"
                                    target="_blank"
                                    rel="noopener"
                                    @click="clickShareIntent(intent.network)"
                                >
                                    <ExternalLink
                                        class="size-4"
                                        aria-hidden="true"
                                    />
                                    {{ intent.label }}
                                </a>
                            </Button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-t border-foreground">
                <div class="grid p-5 sm:grid-cols-[.45fr_1.55fr] sm:p-8">
                    <p class="technical-label text-primary">Launch card</p>
                    <div
                        v-if="kit.is_discoverable && kit.card_url"
                        class="mt-8 flex flex-col gap-4 sm:mt-0"
                    >
                        <p class="text-sm leading-6 text-muted-foreground">
                            The social preview rendered from your launch record.
                            Link it anywhere a preview image is welcome.
                        </p>
                        <img
                            :src="kit.card_url"
                            :alt="`Launch card for ${project.name}`"
                            class="w-full max-w-xl border border-foreground"
                            data-test="launch-card-preview"
                        />
                        <div>
                            <Button
                                variant="outline"
                                data-test="copy-card-link"
                                @click="
                                    copyValue(kit.card_url, 'card_link_copied')
                                "
                            >
                                <Copy class="size-4" aria-hidden="true" />
                                Copy card link
                            </Button>
                        </div>
                    </div>
                    <div
                        v-else
                        class="mt-8 flex flex-col gap-4 border border-foreground p-5 text-sm text-muted-foreground sm:mt-0"
                        data-test="launch-kit-locked"
                    >
                        <p class="flex items-center gap-2">
                            <Lock class="size-4" aria-hidden="true" />
                            <span class="technical-label text-foreground"
                                >Locked until filing</span
                            >
                        </p>
                        <p>
                            The launch card renders once
                            {{ project.name }} is verified and filed to the
                            public registry. The cover plate below is used until
                            then.
                        </p>
                        <img
                            :src="kit.cover_url"
                            :alt="`Cover plate for ${project.name}`"
                            class="w-full max-w-xl border border-foreground"
                        />
                        <Button as-child variant="outline" class="w-fit">
                            <Link
                                :href="projectEdit({ project: project.slug })"
                            >
                                Finish the record
                                <ArrowUpRight class="size-4" />
                            </Link>
                        </Button>
                    </div>
                </div>
            </section>

            <section class="border-t border-foreground">
                <div class="grid p-5 sm:grid-cols-[.45fr_1.55fr] sm:p-8">
                    <p class="technical-label text-primary">Ship Manifest</p>
                    <div
                        v-if="kit.is_discoverable && kit.manifest_url"
                        class="mt-8 flex flex-col gap-4 sm:mt-0"
                    >
                        <p class="text-sm leading-6 text-muted-foreground">
                            The collectible record of your filing — serial
                            number, stack, and launch facts as a single
                            self-contained SVG.
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <Button as-child variant="outline">
                                <a
                                    :href="kit.manifest_url"
                                    :download="`${project.slug}-manifest.svg`"
                                    data-test="save-manifest-kit"
                                >
                                    <Download
                                        class="size-4"
                                        aria-hidden="true"
                                    />
                                    Save manifest
                                </a>
                            </Button>
                            <Button
                                variant="outline"
                                data-test="copy-manifest-link-kit"
                                @click="
                                    copyValue(
                                        kit.manifest_url,
                                        'manifest_link_copied',
                                    )
                                "
                            >
                                <Copy class="size-4" aria-hidden="true" />
                                Copy link
                            </Button>
                        </div>
                    </div>
                    <div
                        v-else
                        class="mt-8 flex flex-col gap-4 border border-foreground p-5 text-sm text-muted-foreground sm:mt-0"
                    >
                        <p class="flex items-center gap-2">
                            <Lock class="size-4" aria-hidden="true" />
                            <span class="technical-label text-foreground"
                                >Locked until filing</span
                            >
                        </p>
                        <p>
                            Your Ship Manifest is issued when
                            {{ project.name }} is filed to the public registry.
                        </p>
                    </div>
                </div>
            </section>

            <BadgeSnippet
                v-if="kit.badge_markdown"
                :markdown="kit.badge_markdown"
                @copied="
                    recordProductEvent('badge_snippet_copied', {
                        projectId: project.id,
                    })
                "
            />
            <section v-else class="border-t border-foreground">
                <div class="grid p-5 sm:grid-cols-[.45fr_1.55fr] sm:p-8">
                    <p class="technical-label text-primary">README badge</p>
                    <div
                        class="mt-8 flex flex-col gap-4 border border-foreground p-5 text-sm text-muted-foreground sm:mt-0"
                    >
                        <p class="flex items-center gap-2">
                            <Lock class="size-4" aria-hidden="true" />
                            <span class="technical-label text-foreground"
                                >Locked until filing</span
                            >
                        </p>
                        <p>
                            The live-on-Cloud README badge is generated when
                            {{ project.name }} is verified and filed. It then
                            updates itself as your verification state changes.
                        </p>
                    </div>
                </div>
            </section>
        </section>
    </PublicShell>
</template>
