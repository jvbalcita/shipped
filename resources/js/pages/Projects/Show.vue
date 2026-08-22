<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import {
    Cloud,
    CornerUpLeft,
    Download,
    ExternalLink,
    GitFork,
    Heart,
    Link2,
    MessageSquare,
    Pencil,
    Star,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import CheerWall from '@/components/shipped/CheerWall.vue';
import FollowButton from '@/components/shipped/FollowButton.vue';
import PublicShell from '@/components/shipped/PublicShell.vue';
import ScreenshotLightbox from '@/components/shipped/ScreenshotLightbox.vue';
import SectionHeader from '@/components/shipped/SectionHeader.vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { defaultCoverUrl } from '@/lib/cover';
import { store as storeCommentCheer } from '@/routes/comments/cheers';
import { show as creatorShow } from '@/routes/creators';
import {
    destroy as destroyComment,
    store as storeComment,
    update as updateComment,
} from '@/routes/projects/comments';
import { destroy as destroyFollow, store as storeFollow } from '@/routes/projects/follow';
import {
    destroy as destroyReview,
    store as storeReview,
    update as updateReview,
} from '@/routes/projects/reviews';
import { show as releaseShow } from '@/routes/releases';

const props = defineProps<{
    project: any;
    manifestUrl: string | null;
    cheers: {
        name: string | null;
        username: string | null;
        avatar_url: string | null;
        cheered_at: string | null;
    }[] | null;
    hasCheered: boolean;
    canCheer: boolean;
}>();

const reviewForm = useForm({
    rating: props.project.user_review?.rating ?? 5,
    body: props.project.user_review?.body ?? '',
});

const commentForm = useForm({ body: '' });
const replyForm = useForm({ body: '', parent_id: null as number | null });
const replyTo = ref<number | null>(null);
const editingId = ref<number | null>(null);
const editForm = useForm({ body: '' });
// Delete confirmation is a two-ref flow: closing the dialog must not clear
// the target before the delete request reads it (the action click closes
// the dialog first, then fires the handler).
const deleteDialogOpen = ref(false);
const commentPendingDelete = ref<any>(null);
const activeScreenshot = ref<number | null>(null);

function formatTimestamp(iso: string | null): string {
    if (!iso) {
        return '';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(iso));
}

const topLevelComments = computed(() =>
    (props.project.comments ?? []).filter((c: any) => c.parent_id === null),
);
const repliesFor = (id: number) =>
    (props.project.comments ?? []).filter((c: any) => c.parent_id === id);

function submitReview(): void {
    const { project } = props;

    if (project.user_review) {
        reviewForm.patch(
            updateReview({ project, review: project.user_review }).url,
            { preserveScroll: true },
        );
    } else {
        reviewForm.post(storeReview(project).url, { preserveScroll: true });
    }
}

function removeReview(): void {
    useForm({})
        .delete(
            destroyReview({ project: props.project, review: props.project.user_review }).url,
            { preserveScroll: true },
        );
}

function submitComment(): void {
    commentForm.post(storeComment(props.project).url, {
        preserveScroll: true,
        onSuccess: () => commentForm.reset(),
    });
}

function submitReply(): void {
    replyForm.post(storeComment(props.project).url, {
        preserveScroll: true,
        onSuccess: () => {
            replyForm.reset();
            replyForm.parent_id = null;
            replyTo.value = null;
        },
    });
}

function startReply(comment: any): void {
    replyTo.value = comment.id;
    replyForm.reset();
    replyForm.parent_id = comment.id;
}

function startEdit(comment: any): void {
    editingId.value = comment.id;
    editForm.body = comment.body;
}

function saveEdit(comment: any): void {
    editForm.patch(
        updateComment({ project: props.project, comment }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                editingId.value = null;
            },
        },
    );
}

function deleteComment(): void {
    const comment = commentPendingDelete.value;

    if (!comment) {
        return;
    }

    useForm({}).delete(
        destroyComment({ project: props.project, comment }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                commentPendingDelete.value = null;
            },
        },
    );
}

function cancelDelete(): void {
    deleteDialogOpen.value = false;
    commentPendingDelete.value = null;
}

function requestDelete(comment: any): void {
    commentPendingDelete.value = comment;
    deleteDialogOpen.value = true;
}

function cheerComment(comment: any): void {
    useForm({}).post(storeCommentCheer(comment).url, { preserveScroll: true });
}

async function copyManifestLink(): Promise<void> {
    if (props.manifestUrl === null) {
        return;
    }

    try {
        await navigator.clipboard.writeText(props.manifestUrl);
        toast.success('Manifest link copied.');
    } catch {
        toast('Copy the manifest link from your browser.');
    }
}
</script>

<template>
    <PublicShell :title="project.name">
        <section
            class="page-enter mx-auto w-full max-w-[90rem] min-w-0 border-x border-foreground"
        >
            <div class="relative border-b border-foreground bg-secondary">
                <img
                    :src="project.cover_image_url ?? defaultCoverUrl(project)"
                    :alt="`${project.name} cover image`"
                    class="media-reveal aspect-[8/3] max-h-96 w-full object-cover"
                    :class="{ grayscale: !!project.cover_image_url }"
                    data-test="project-cover"
                />
                <span
                    v-if="!project.cover_image_url"
                    class="technical-label absolute top-6 left-6 text-primary"
                    >Cover pending</span
                >
            </div>
            <div class="border-b border-foreground">
                <div class="min-w-0 p-5 sm:p-8">
                    <div class="flex flex-wrap items-center gap-3">
                        <Badge variant="outline">{{
                            project.category.name
                        }}</Badge>
                        <span
                            v-if="project.filed_serial"
                            class="technical-label tabular-nums text-muted-foreground"
                            >{{ project.filed_serial }}</span
                        >
                        <span
                            v-if="project.verification_status === 'verified'"
                            class="technical-label inline-flex items-center gap-1 text-primary"
                            ><Cloud class="size-4" />Live on Laravel Cloud</span
                        >
                    </div>
                    <div class="mt-12 flex items-center gap-5 sm:gap-8">
                        <img
                            v-if="project.logo_url"
                            :src="project.logo_url"
                            :alt="`${project.name} logo`"
                            class="size-20 shrink-0 border border-foreground object-cover sm:size-28"
                            data-test="project-logo"
                        />
                        <h1
                            class="display-type launch-name text-[clamp(3.5rem,9vw,9rem)]"
                        >
                            {{ project.name }}
                        </h1>
                    </div>
                    <p class="mt-8 max-w-2xl text-lg leading-8">
                        {{ project.tagline }}
                    </p>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <span
                            v-if="project.pricing_label || project.pricing"
                            class="technical-label text-primary"
                            data-test="project-pricing"
                            >{{
                                project.pricing_label ||
                                String(project.pricing).replaceAll('_', ' ')
                            }}</span
                        >
                        <span
                            v-if="project.launch_date"
                            class="technical-label text-muted-foreground"
                            data-test="project-launch-date"
                            >Launched
                            {{
                                new Date(project.launch_date).toLocaleDateString(
                                    undefined,
                                    {
                                        year: 'numeric',
                                        month: 'short',
                                        day: 'numeric',
                                    },
                                )
                            }}</span
                        >
                    </div>
                    <ul
                        v-if="project.tags?.length"
                        class="mt-4 flex flex-wrap gap-2"
                        data-test="project-tags"
                    >
                        <li
                            v-for="tag in project.tags"
                            :key="tag.id ?? tag.slug ?? tag.name"
                            class="technical-label border border-foreground px-2 py-0.5"
                        >
                            {{ tag.name }}
                        </li>
                    </ul>
                    <div
                        class="rich-text mt-8 max-w-2xl whitespace-pre-line"
                        v-html="project.description"
                    ></div>
                    <div
                        v-if="project.screenshots && project.screenshots.length"
                        class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4"
                    >
                        <button
                            v-for="(screenshot, index) in project.screenshots"
                            :key="screenshot.id"
                            type="button"
                            class="border border-foreground text-left hover:bg-secondary"
                            data-test="screenshot-thumb"
                            :aria-label="
                                screenshot.caption
                                    ? `Open preview: ${screenshot.caption}`
                                    : 'Open screenshot preview'
                            "
                            @click="activeScreenshot = index"
                        >
                            <img
                                :src="screenshot.url"
                                :alt="screenshot.caption ?? ''"
                                class="aspect-[4/3] w-full object-cover"
                                loading="lazy"
                                decoding="async"
                            />
                            <span
                                class="block border-t border-foreground px-3 py-2 text-xs"
                            >
                                Fig. {{ String(index + 1).padStart(2, '0')
                                }}{{ screenshot.caption ? ` — ${screenshot.caption}` : '' }}
                            </span>
                        </button>
                    </div>
                    <ScreenshotLightbox
                        v-model="activeScreenshot"
                        :screenshots="project.screenshots ?? []"
                    />
                    <div class="mt-10 flex flex-wrap gap-2">
                        <Button v-if="project.live_url" as-child
                            ><a
                                :href="project.live_url"
                                target="_blank"
                                rel="noreferrer"
                                >Visit product
                                <ExternalLink class="size-4" /></a
                        ></Button>
                        <Button
                            v-if="project.github_url"
                            as-child
                            variant="outline"
                            ><a
                                :href="project.github_url"
                                target="_blank"
                                rel="noreferrer"
                                ><GitFork class="size-4" />Source</a
                            ></Button
                        >
                        <FollowButton
                            :key="`project-follow-${project.id}`"
                            :count="project.followers_count"
                            :following="project.followed_by_viewer"
                            :action="
                                project.followed_by_viewer
                                    ? { ...destroyFollow(project), method: 'delete' as const }
                                    : { ...storeFollow(project), method: 'post' as const }
                            "
                        />
                        <Button
                            v-if="manifestUrl"
                            as-child
                            variant="outline"
                        >
                            <a
                                :href="manifestUrl"
                                :download="`${project.slug}-manifest.svg`"
                                data-test="save-manifest"
                                ><Download class="size-4" />Save manifest</a
                            >
                        </Button>
                        <Button
                            v-if="manifestUrl"
                            variant="outline"
                            data-test="copy-manifest-link"
                            @click="copyManifestLink"
                            ><Link2 class="size-4" />Copy link</Button
                        >
                    </div>
                    <p class="technical-label mt-12">
                        Made by
                        <Link
                            :href="creatorShow(project.creator)"
                            class="text-primary underline underline-offset-4"
                            >@{{ project.creator.username }}</Link
                        >
                        <span class="text-muted-foreground">
                            /
                            {{ project.followers_count }}
                            {{
                                project.followers_count === 1
                                    ? 'follower'
                                    : 'followers'
                            }}</span
                        >
                    </p>
                </div>
            </div>
            <CheerWall
                :cheers="cheers"
                :has-cheered="hasCheered"
                :can-cheer="canCheer"
                :project="project"
            />
            <SectionHeader label="Release chronology">
                <ol
                    class="mt-10 divide-y divide-foreground border-y border-foreground sm:mt-0"
                >
                    <li
                        v-for="release in project.releases"
                        :key="release.id"
                        class="grid gap-4 py-5 sm:grid-cols-[10rem_1fr]"
                    >
                        <time class="technical-label text-muted-foreground">{{
                            new Date(release.published_at).toLocaleDateString(
                                undefined,
                                {
                                    year: 'numeric',
                                    month: 'short',
                                    day: 'numeric',
                                },
                            )
                        }}</time>
                        <div>
                            <h2 class="font-semibold">
                                <Link
                                    :href="
                                        releaseShow({
                                            creator: project.creator,
                                            project,
                                            release,
                                        })
                                    "
                                    class="underline decoration-primary underline-offset-4"
                                    >{{ release.title }}</Link
                                >
                            </h2>
                            <p
                                class="font-prose mt-3 text-sm leading-7 whitespace-pre-line"
                            >
                                {{ release.notes }}
                            </p>
                        </div>
                    </li>
                    <li
                        v-if="!project.releases.length"
                        class="py-5 text-sm text-muted-foreground"
                    >
                        No public release notes yet.
                    </li>
                </ol>
            </SectionHeader>
            <SectionHeader :label="`Discussion / ${topLevelComments.length}`">
                <form
                    v-if="$page.props.auth.user"
                    novalidate
                    class="mb-8 grid gap-3"
                    @submit.prevent="submitComment"
                >
                    <Textarea
                        v-model="commentForm.body"
                        placeholder="Join the discussion (max 500 characters)"
                    />
                    <p
                        v-if="commentForm.errors.body"
                        class="text-sm text-destructive"
                    >
                        {{ commentForm.errors.body }}
                    </p>
                    <div>
                        <Button type="submit" :disabled="commentForm.processing"
                            >Post comment</Button
                        >
                    </div>
                </form>
                <ul
                    v-if="topLevelComments.length"
                    class="divide-y divide-foreground border-y border-foreground"
                >
                    <li
                        v-for="comment in topLevelComments"
                        :key="comment.id"
                        class="group py-5"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                    <p class="technical-label">{{
                                        comment.user?.username ?? comment.user?.name
                                    }}</p>
                                    <time
                                        class="technical-label text-muted-foreground"
                                        >{{ formatTimestamp(comment.created_at) }}</time
                                    >
                                </div>
                                <p
                                    v-if="comment.is_deleted"
                                    class="technical-label mt-1 italic text-muted-foreground"
                                >
                                    [deleted]
                                </p>
                                <template v-else-if="editingId === comment.id">
                                    <Textarea
                                        v-model="editForm.body"
                                        class="mt-2 min-h-32"
                                    />
                                    <div class="mt-2 flex gap-2">
                                        <Button
                                            size="sm"
                                            :disabled="editForm.processing"
                                            @click="saveEdit(comment)"
                                            >Save</Button
                                        >
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            type="button"
                                            @click="editingId = null"
                                            >Cancel</Button
                                        >
                                    </div>
                                </template>
                                <p
                                    v-else
                                    class="mt-2 whitespace-pre-line text-sm leading-6"
                                >
                                    {{ comment.body }}
                                </p>
                            </div>
                            <div
                                v-if="!comment.is_deleted"
                                class="flex shrink-0 flex-col items-end gap-2"
                            >
                                <button
                                    type="button"
                                    class="technical-label inline-flex items-center gap-1"
                                    :class="
                                        comment.cheered_by_viewer
                                            ? 'text-primary'
                                            : 'text-muted-foreground'
                                    "
                                    @click="cheerComment(comment)"
                                >
                                    <Heart class="size-4" />{{
                                        comment.cheers_count
                                    }}
                                </button>
                                <div
                                    v-if="$page.props.auth.user"
                                    class="flex gap-1 transition-opacity sm:opacity-0 sm:group-hover:opacity-100 sm:group-focus-within:opacity-100"
                                >
                                    <Button
                                        v-if="comment.can_edit"
                                        size="sm"
                                        variant="ghost"
                                        :aria-label="`Edit comment by ${comment.user?.username}`"
                                        @click="startEdit(comment)"
                                        ><Pencil class="size-4" /></Button
                                    >
                                    <Button
                                        v-if="comment.can_delete"
                                        size="sm"
                                        variant="ghost"
                                        :aria-label="`Delete comment by ${comment.user?.username}`"
                                        @click="requestDelete(comment)"
                                        ><Trash2 class="size-4" /></Button
                                    >
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        :aria-label="`Reply to ${comment.user?.username}`"
                                        @click="startReply(comment)"
                                        ><CornerUpLeft class="size-4" /></Button
                                    >
                                </div>
                            </div>
                        </div>
                        <ul
                            v-if="repliesFor(comment.id).length"
                            class="mt-4 space-y-4 border-l border-foreground pl-4"
                        >
                            <li
                                v-for="reply in repliesFor(comment.id)"
                                :key="reply.id"
                                class="group"
                            >
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                            <p class="technical-label">{{
                                                reply.user?.username ??
                                                reply.user?.name
                                            }}</p>
                                            <time
                                                class="technical-label text-muted-foreground"
                                                >{{
                                                    formatTimestamp(reply.created_at)
                                                }}</time
                                            >
                                        </div>
                                        <p
                                            v-if="reply.is_deleted"
                                            class="technical-label mt-1 italic text-muted-foreground"
                                        >
                                            [deleted]
                                        </p>
                                        <template
                                            v-else-if="editingId === reply.id"
                                        >
                                            <Textarea
                                                v-model="editForm.body"
                                                class="mt-2 min-h-32"
                                            />
                                            <div class="mt-2 flex gap-2">
                                                <Button
                                                    size="sm"
                                                    :disabled="editForm.processing"
                                                    @click="saveEdit(reply)"
                                                    >Save</Button
                                                >
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    type="button"
                                                    @click="editingId = null"
                                                    >Cancel</Button
                                                >
                                            </div>
                                        </template>
                                        <p
                                            v-else
                                            class="mt-2 whitespace-pre-line text-sm leading-6"
                                        >
                                            {{ reply.body }}
                                        </p>
                                    </div>
                                    <div
                                        v-if="!reply.is_deleted"
                                        class="flex shrink-0 flex-col items-end gap-2"
                                    >
                                        <button
                                            type="button"
                                            class="technical-label inline-flex items-center gap-1"
                                            :class="
                                                reply.cheered_by_viewer
                                                    ? 'text-primary'
                                                    : 'text-muted-foreground'
                                            "
                                            @click="cheerComment(reply)"
                                        >
                                            <Heart class="size-4" />{{
                                                reply.cheers_count
                                            }}
                                        </button>
                                        <div
                                            v-if="$page.props.auth.user"
                                            class="flex gap-1 transition-opacity sm:opacity-0 sm:group-hover:opacity-100 sm:group-focus-within:opacity-100"
                                        >
                                            <Button
                                                v-if="reply.can_edit"
                                                size="sm"
                                                variant="ghost"
                                                :aria-label="`Edit reply by ${reply.user?.username}`"
                                                @click="startEdit(reply)"
                                                ><Pencil class="size-4" /></Button
                                            >
                                            <Button
                                                v-if="reply.can_delete"
                                                size="sm"
                                                variant="ghost"
                                                :aria-label="`Delete reply by ${reply.user?.username}`"
                                                @click="requestDelete(reply)"
                                                ><Trash2 class="size-4" /></Button
                                            >
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                        <form
                            v-if="replyTo === comment.id && $page.props.auth.user"
                            class="mt-4 grid gap-2 border-l border-foreground pl-4"
                            @submit.prevent="submitReply"
                        >
                            <Textarea
                                v-model="replyForm.body"
                                placeholder="Your reply"
                            />
                            <p
                                v-if="replyForm.errors.body"
                                class="text-sm text-destructive"
                            >
                                {{ replyForm.errors.body }}
                            </p>
                            <div class="flex gap-2">
                                <Button
                                    size="sm"
                                    :disabled="replyForm.processing"
                                    >Reply</Button
                                >
                                <Button
                                    size="sm"
                                    variant="outline"
                                    type="button"
                                    @click="replyTo = null"
                                    >Cancel</Button
                                >
                            </div>
                        </form>
                    </li>
                </ul>
                <p v-else class="technical-label text-muted-foreground">
                    <MessageSquare class="mr-1 inline size-4" />No comments yet.
                </p>
            </SectionHeader>
            <SectionHeader :label="`Reviews / ${project.reviews?.length ?? 0}`">
                <div
                    v-if="project.rating_average !== null"
                    class="mb-6 flex items-center gap-3"
                >
                    <span
                        class="flex items-center gap-0.5 text-primary"
                        :aria-label="`Average ${project.rating_average} out of 5`"
                    >
                        <Star
                            v-for="value in 5"
                            :key="value"
                            class="size-6"
                            :class="
                                value <= Math.round(project.rating_average)
                                    ? 'fill-current'
                                    : 'text-muted-foreground'
                            "
                        />
                    </span>
                    <span class="font-display text-2xl tabular-nums">{{
                        project.rating_average
                    }}</span>
                    <span class="technical-label text-muted-foreground"
                        >/ 5</span
                    >
                </div>
                <form
                    v-if="$page.props.auth.user"
                    novalidate
                    class="mb-8 grid gap-3"
                    @submit.prevent="submitReview"
                >
                    <div class="flex items-center gap-3">
                        <span class="technical-label">Your rating</span>
                        <div class="flex gap-1">
                            <button
                                v-for="value in 5"
                                :key="value"
                                type="button"
                                class="p-0.5"
                                :class="
                                    reviewForm.rating >= value
                                        ? 'text-primary'
                                        : 'text-muted-foreground'
                                "
                                :aria-label="`${value} out of 5`"
                                @click="reviewForm.rating = value"
                            >
                                <Star
                                    class="size-5"
                                    :class="{
                                        'fill-current':
                                            reviewForm.rating >= value,
                                    }"
                                />
                            </button>
                        </div>
                    </div>
                    <Textarea
                        v-model="reviewForm.body"
                        placeholder="Optional notes (max 1000 characters)"
                    />
                    <div class="flex items-center gap-3">
                        <Button
                            type="submit"
                            :disabled="reviewForm.processing"
                            >{{
                                project.user_review
                                    ? 'Update review'
                                    : 'Post review'
                            }}</Button
                        >
                        <Button
                            v-if="project.user_review"
                            type="button"
                            variant="outline"
                            :disabled="reviewForm.processing"
                            @click="removeReview"
                            ><Trash2 class="size-4" />Delete</Button
                        >
                    </div>
                    <p
                        v-if="reviewForm.errors.rating"
                        class="text-sm text-destructive"
                    >
                        {{ reviewForm.errors.rating }}
                    </p>
                </form>
                <ul
                    v-if="project.reviews?.length"
                    class="divide-y divide-foreground border-y border-foreground"
                >
                    <li
                        v-for="review in project.reviews"
                        :key="review.id"
                        class="py-4"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex flex-wrap items-baseline gap-x-3">
                                <span class="technical-label">{{
                                    review.user?.username ?? review.user?.name
                                }}</span>
                                <time
                                    class="technical-label text-muted-foreground"
                                    >{{
                                        formatTimestamp(review.created_at)
                                    }}</time
                                >
                            </div>
                            <span
                                class="flex items-center gap-0.5 text-primary"
                                :aria-label="`${review.rating} out of 5`"
                            >
                                <Star
                                    v-for="value in 5"
                                    :key="value"
                                    class="size-5"
                                    :class="
                                        value <= review.rating
                                            ? 'fill-current'
                                            : 'text-muted-foreground'
                                    "
                                />
                            </span>
                        </div>
                        <p
                            v-if="review.body"
                            class="mt-2 text-sm leading-6"
                        >
                            {{ review.body }}
                        </p>
                    </li>
                </ul>
                <p v-else class="technical-label text-muted-foreground">
                    No reviews yet.
                </p>
            </SectionHeader>
        </section>

        <AlertDialog v-model:open="deleteDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete this comment?</AlertDialogTitle>
                    <AlertDialogDescription>
                        The comment will be removed. If it has replies, a
                        [deleted] placeholder keeps the thread intact.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="cancelDelete"
                        >Cancel</AlertDialogCancel
                    >
                    <AlertDialogAction
                        data-test="confirm-delete-comment"
                        @click="deleteComment()"
                        >Delete</AlertDialogAction
                    >
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </PublicShell>
</template>
